const DB_NAME = 'CardioCareOffline';
const DB_VERSION = 1;
const STORE_NAME = 'pending_consultations';

export interface PendingConsultation {
    id?: number;
    appointmentId: string | number;
    formData: Record<string, any>;
    createdAt: string;
    retryCount: number;
    /**
     * Set when the server rejects the payload (or retries are exhausted).
     * The record is kept in IndexedDB so the doctor's work is never silently
     * discarded; automatic sync skips it until it is reviewed.
     */
    needsReview?: boolean;
    lastError?: string;
}

export type SyncStatusListener = (pending: number, online: boolean) => void;

let dbPromise: Promise<IDBDatabase> | null = null;
const listeners: Set<SyncStatusListener> = new Set();

function openDB(): Promise<IDBDatabase> {
    if (dbPromise) {
        return dbPromise;
    }

    dbPromise = new Promise((resolve, reject) => {
        const request = indexedDB.open(DB_NAME, DB_VERSION);
        request.onupgradeneeded = () => {
            const db = request.result;

            if (!db.objectStoreNames.contains(STORE_NAME)) {
                const store = db.createObjectStore(STORE_NAME, {
                    keyPath: 'id',
                    autoIncrement: true,
                });
                store.createIndex('appointmentId', 'appointmentId', {
                    unique: false,
                });
                store.createIndex('createdAt', 'createdAt', { unique: false });
            }
        };
        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });

    return dbPromise;
}

export async function savePendingConsultation(
    appointmentId: string | number,
    formData: Record<string, any>,
): Promise<number> {
    const db = await openDB();

    return new Promise((resolve, reject) => {
        const tx = db.transaction(STORE_NAME, 'readwrite');
        const store = tx.objectStore(STORE_NAME);
        const existing = store.index('appointmentId').getAll(appointmentId);

        existing.onsuccess = () => {
            // One pending record per appointment: a later offline save replaces
            // the earlier snapshot instead of syncing duplicates.
            for (const item of existing.result as PendingConsultation[]) {
                if (item.id !== undefined) {
                    store.delete(item.id);
                }
            }

            const req = store.add({
                appointmentId,
                formData,
                createdAt: new Date().toISOString(),
                retryCount: 0,
            } as PendingConsultation);
            req.onsuccess = () => {
                notify();
                resolve(req.result as number);
            };
            req.onerror = () => reject(req.error);
        };
        existing.onerror = () => reject(existing.error);
    });
}

export async function getAllPendingConsultations(): Promise<
    PendingConsultation[]
> {
    const db = await openDB();

    return new Promise((resolve, reject) => {
        const tx = db.transaction(STORE_NAME, 'readonly');
        const store = tx.objectStore(STORE_NAME);
        const req = store.getAll();
        req.onsuccess = () => resolve(req.result as PendingConsultation[]);
        req.onerror = () => reject(req.error);
    });
}

export async function getPendingCount(): Promise<number> {
    const db = await openDB();

    return new Promise((resolve, reject) => {
        const tx = db.transaction(STORE_NAME, 'readonly');
        const store = tx.objectStore(STORE_NAME);
        const req = store.count();
        req.onsuccess = () => resolve(req.result);
        req.onerror = () => reject(req.error);
    });
}

export async function deletePendingConsultation(id: number): Promise<void> {
    const db = await openDB();

    return new Promise((resolve, reject) => {
        const tx = db.transaction(STORE_NAME, 'readwrite');
        const store = tx.objectStore(STORE_NAME);
        const req = store.delete(id);
        req.onsuccess = () => {
            notify();
            resolve();
        };
        req.onerror = () => reject(req.error);
    });
}

export async function updateRetryCount(
    id: number,
    retryCount: number,
): Promise<void> {
    const db = await openDB();

    return new Promise((resolve, reject) => {
        const tx = db.transaction(STORE_NAME, 'readwrite');
        const store = tx.objectStore(STORE_NAME);
        const getReq = store.get(id);
        getReq.onsuccess = () => {
            const data = getReq.result as PendingConsultation;

            if (data) {
                data.retryCount = retryCount;
                store.put(data);
            }

            resolve();
        };
        getReq.onerror = () => reject(getReq.error);
    });
}

async function markNeedsReview(id: number, reason: string): Promise<void> {
    const db = await openDB();

    return new Promise((resolve, reject) => {
        const tx = db.transaction(STORE_NAME, 'readwrite');
        const store = tx.objectStore(STORE_NAME);
        const getReq = store.get(id);
        getReq.onsuccess = () => {
            const data = getReq.result as PendingConsultation;

            if (data) {
                data.needsReview = true;
                data.lastError = reason;
                store.put(data);
            }

            resolve();
        };
        getReq.onerror = () => reject(getReq.error);
    });
}

function getCsrfToken(): string {
    const meta = document.querySelector<HTMLMetaElement>(
        'meta[name="csrf-token"]',
    );

    return meta?.content ?? '';
}

export async function syncPendingConsultation(
    item: PendingConsultation,
): Promise<boolean> {
    if (item.needsReview) {
        return false;
    }

    if (item.retryCount >= 5) {
        await markNeedsReview(
            item.id!,
            'Automatic sync stopped after 5 attempts. The consultation is kept for review.',
        );
        item.needsReview = true;

        return false;
    }

    try {
        const response = await fetch(
            `/appointments/${item.appointmentId}/complete`,
            {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    Accept: 'application/json',
                },
                body: JSON.stringify(item.formData),
            },
        );

        if (response.ok) {
            await deletePendingConsultation(item.id!);

            return true;
        }

        if (response.status >= 400 && response.status < 500) {
            // Never delete the doctor's work: a rejected payload may be a
            // recoverable validation problem (or the patient was since closed).
            await markNeedsReview(
                item.id!,
                `Server rejected the saved consultation (HTTP ${response.status}). It was kept for manual review.`,
            );
            item.needsReview = true;

            return false;
        }

        await updateRetryCount(item.id!, item.retryCount + 1);

        return false;
    } catch {
        await updateRetryCount(item.id!, item.retryCount + 1);

        return false;
    }
}

export async function syncAllPending(): Promise<{
    synced: number;
    failed: number;
    needsReview: number;
}> {
    const items = await getAllPendingConsultations();
    let synced = 0;
    let failed = 0;
    let needsReview = 0;

    for (const item of items) {
        const ok = await syncPendingConsultation(item);

        if (ok) {
            synced++;
        } else if (item.needsReview) {
            needsReview++;
        } else {
            failed++;
        }
    }

    return { synced, failed, needsReview };
}

export function isOnline(): boolean {
    return navigator.onLine;
}

export function subscribeToSyncStatus(
    listener: SyncStatusListener,
): () => void {
    listeners.add(listener);

    return () => listeners.delete(listener);
}

function notify() {
    const online = isOnline();
    getPendingCount().then((count) => {
        listeners.forEach((fn) => fn(count, online));
    });
}

export function initOfflineListeners(): void {
    window.addEventListener('online', () => {
        notify();
        syncAllPending().then(({ synced, failed, needsReview }) => {
            if (synced > 0 || failed > 0 || needsReview > 0) {
                const parts: string[] = [];

                if (synced > 0) {
                    parts.push(`${synced} synced`);
                }

                if (failed > 0) {
                    parts.push(`${failed} failed`);
                }

                if (needsReview > 0) {
                    parts.push(`${needsReview} needs review`);
                }

                window.dispatchEvent(
                    new CustomEvent('offline-sync-complete', {
                        detail: {
                            synced,
                            failed,
                            needsReview,
                            message: parts.join(', '),
                        },
                    }),
                );
            }
        });
    });

    window.addEventListener('offline', () => {
        notify();
    });

    notify();
}
