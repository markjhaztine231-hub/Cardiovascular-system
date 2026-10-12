<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import axios from 'axios';

type ScanStatus = 'pending' | 'processing' | 'completed' | 'failed';

interface ScanSummary {
    id: number;
    type: string;
    ocr_status: ScanStatus;
    ocr_error?: string | null;
}

const props = defineProps<{
    open: boolean;
    appointmentId: number | null;
    patientName?: string;
    scans: ScanSummary[];
}>();

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'uploaded'): void;
}>();

const ACCEPTED = [
    'image/jpeg',
    'image/png',
    'image/tiff',
    'image/bmp',
    'image/webp',
];

const MAX_BYTES = 10 * 1024 * 1024;

const DOC_TYPES = [
    { value: 'prescription', label: 'Prescription', hint: 'Medication list' },
    { value: 'echo', label: 'Echo', hint: 'Echocardiogram report' },
    { value: 'tmt', label: 'TMT', hint: 'Stress test report' },
    { value: 'doppler', label: 'Doppler', hint: 'Doppler study report' },
    { value: 'lab_result', label: 'Lab Result', hint: 'Bloodwork / reports' },
    { value: 'referral', label: 'Referral', hint: 'Referral letter' },
    { value: 'other', label: 'Other', hint: 'Any clinical image' },
];

const fileInput = ref<HTMLInputElement | null>(null);
const file = ref<File | null>(null);
const previewUrl = ref<string | null>(null);
const docType = ref<string>('prescription');

const dragActive = ref(false);
const uploading = ref(false);
const uploadError = ref<string | null>(null);
const localScans = ref<Record<number, ScanSummary>>({});
const retryingId = ref<number | null>(null);
const retryError = ref<string | null>(null);

let pollTimer: ReturnType<typeof setTimeout> | null = null;

/** Live view of server scans merged with anything uploaded in this modal. */
const liveScans = computed<ScanSummary[]>(() => {
    const merged: Record<number, ScanSummary> = {};

    for (const scan of props.scans ?? []) {
        merged[scan.id] = scan;
    }

    for (const [id, scan] of Object.entries(localScans.value)) {
        const key = Number(id);

        if (!merged[key] || merged[key].ocr_status !== 'completed') {
            merged[key] = scan;
        }
    }

    return Object.values(merged).sort((a, b) => b.id - a.id);
});

const busyScanIds = computed(() =>
    liveScans.value
        .filter((s) => s.ocr_status === 'pending' || s.ocr_status === 'processing')
        .map((s) => s.id),
);

const failedCount = computed(
    () => liveScans.value.filter((s) => s.ocr_status === 'failed').length,
);

const completedCount = computed(
    () => liveScans.value.filter((s) => s.ocr_status === 'completed').length,
);

/** Scans the OCR service never finished, which the receptionist can re-queue. */
const failedScans = computed(() => liveScans.value.filter((s) => s.ocr_status === 'failed'));

/**
 * The image is already stored, so a retry only re-queues OCR. This is the
 * escape hatch for a scan that failed because the OCR service was down.
 */
async function retryScan(scan: ScanSummary): Promise<void> {
    if (retryingId.value !== null) return;

    retryingId.value = scan.id;
    retryError.value = null;

    try {
        await axios.post(`/documents/${scan.id}/retry-ocr`);

        localScans.value = {
            ...localScans.value,
            [scan.id]: { ...scan, ocr_status: 'pending', ocr_error: null },
        };

        emit('uploaded');
        schedulePoll();
    } catch (error: any) {
        retryError.value =
            error?.response?.data?.message ?? 'Retry failed. Please try again.';
    } finally {
        retryingId.value = null;
    }
}

async function retryAllFailed(): Promise<void> {
    for (const scan of failedScans.value) {
        await retryScan(scan);
    }
}

function humanSize(bytes: number): string {
    if (bytes < 1024) return `${bytes} B`;

    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(0)} KB`;

    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

function typeLabel(value: string): string {
    return DOC_TYPES.find((t) => t.value === value)?.label ?? value;
}

function validate(candidate: File): string | null {
    if (!ACCEPTED.includes(candidate.type)) {
        return 'Use a JPG, PNG, TIFF, BMP or WEBP image.';
    }

    if (candidate.size > MAX_BYTES) {
        return `That image is ${humanSize(candidate.size)} — the limit is 10 MB.`;
    }

    return null;
}

function setFile(candidate: File | null): void {
    uploadError.value = null;

    if (!candidate) {
        clearFile();

        return;
    }

    const problem = validate(candidate);

    if (problem) {
        uploadError.value = problem;
        clearFile();

        return;
    }

    revokePreview();
    file.value = candidate;
    previewUrl.value = URL.createObjectURL(candidate);
}

function clearFile(): void {
    revokePreview();
    file.value = null;
    previewUrl.value = null;
}

function revokePreview(): void {
    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
        previewUrl.value = null;
    }
}

function onDrop(event: DragEvent): void {
    dragActive.value = false;
    setFile(event.dataTransfer?.files?.[0] ?? null);
}

function onPick(event: Event): void {
    const input = event.target as HTMLInputElement;
    setFile(input.files?.[0] ?? null);
}

async function upload(): Promise<void> {
    if (!file.value || !props.appointmentId || uploading.value) {
        return;
    }

    uploading.value = true;
    uploadError.value = null;

    const body = new FormData();
    body.append('document', file.value);
    body.append('type', docType.value);

    try {
        const { data } = await axios.post(
            `/appointments/${props.appointmentId}/documents`,
            body,
            { headers: { 'Content-Type': 'multipart/form-data' } },
        );

        const created = data?.document as { id: number; type: string; ocr_status: ScanStatus } | undefined;

        if (created) {
            localScans.value = {
                ...localScans.value,
                [created.id]: {
                    id: created.id,
                    type: created.type,
                    ocr_status: created.ocr_status,
                },
            };
        }

        clearFile();
        emit('uploaded');
        schedulePoll();
    } catch (error: any) {
        uploadError.value =
            error?.response?.data?.errors?.document?.[0] ??
            error?.response?.data?.errors?.type?.[0] ??
            error?.response?.data?.message ??
            'Upload failed. Check the file and try again.';
    } finally {
        uploading.value = false;
    }
}

/**
 * The OCR job runs on the queue, so status only resolves through polling.
 * Backs off as the wait grows and stops as soon as nothing is in flight.
 */
function schedulePoll(delay = 2000): void {
    stopPolling();

    if (!props.open || busyScanIds.value.length === 0) {
        return;
    }

    pollTimer = setTimeout(async () => {
        if (!props.open || props.appointmentId === null) return;

        for (const id of busyScanIds.value) {
            try {
                const { data } = await axios.get(`/documents/${id}`);

                const doc = data?.document as
                    | { id: number; type: string; ocr_status: ScanStatus; ocr_error?: string | null }
                    | undefined;

                if (doc) {
                    localScans.value = {
                        ...localScans.value,
                        [doc.id]: {
                            id: doc.id,
                            type: doc.type,
                            ocr_status: doc.ocr_status,
                            ocr_error: doc.ocr_error ?? null,
                        },
                    };
                }
            } catch {
                // A single failed poll should not abort the whole sweep.
            }
        }

        if (busyScanIds.value.length > 0) {
            schedulePoll(delay + 1000);
        } else {
            emit('uploaded');
        }
    }, delay);
}

function stopPolling(): void {
    if (pollTimer) {
        clearTimeout(pollTimer);
        pollTimer = null;
    }
}

function close(): void {
    stopPolling();
    clearFile();
    uploadError.value = null;
    retryError.value = null;
    emit('close');
}

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            uploadError.value = null;
            retryError.value = null;
            schedulePoll();
        } else {
            stopPolling();
            localScans.value = {};
            retryingId.value = null;
        }
    },
);

onBeforeUnmount(() => {
    stopPolling();
    revokePreview();
});
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            leave-active-class="transition duration-150 ease-in"
            leave-to-class="opacity-0"
        >
            <div
                v-if="open"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
                role="dialog"
                aria-modal="true"
                aria-label="Upload prescription image"
                @click.self="close"
                @keydown.esc="close"
            >
                <div
                    class="animate-fade-in-up flex max-h-[92vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl"
                >
                    <!-- Header -->
                    <div
                        class="flex shrink-0 items-center justify-between bg-[#1e293b] px-6 py-4 text-white"
                    >
                        <div class="flex items-center gap-3">
                            <span
                                class="flex h-9 w-9 items-center justify-center rounded-lg bg-teal-500"
                            >
                                <svg
                                    class="h-5 w-5"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M7 16a4 4 0 01-.88-7.9A5 5 0 0115.9 6M15 13l4 4m0-4l-4 4"
                                    />
                                </svg>
                            </span>
                            <div>
                                <h2 class="text-lg font-bold tracking-wide">
                                    Convert Image to Text
                                </h2>
                                <p class="text-xs font-medium text-gray-300">
                                    {{
                                        patientName
                                            ? `OCR scan for ${patientName}`
                                            : 'OCR scan for this appointment'
                                    }}
                                </p>
                            </div>
                        </div>
                        <button
                            type="button"
                            class="text-gray-400 transition-colors hover:text-white"
                            aria-label="Close"
                            @click="close"
                        >
                            <svg
                                class="h-6 w-6"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    </div>

                    <div class="min-h-0 flex-1 space-y-5 overflow-y-auto p-6">
                        <!-- Drop zone -->
                        <div
                            class="relative rounded-xl border-2 border-dashed p-6 text-center transition-colors"
                            :class="
                                dragActive
                                    ? 'border-teal-500 bg-teal-50'
                                    : 'border-gray-300 bg-gray-50 hover:border-teal-400'
                            "
                            @dragover.prevent="dragActive = true"
                            @dragleave.prevent="dragActive = false"
                            @drop.prevent="onDrop"
                        >
                            <input
                                ref="fileInput"
                                type="file"
                                class="sr-only"
                                accept="image/png,image/jpeg,image/tiff,image/bmp,image/webp"
                                @change="onPick"
                            />

                            <template v-if="!file">
                                <svg
                                    class="mx-auto mb-3 h-10 w-10 text-gray-400"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1.6"
                                        d="M4 16l4.5-5 3 3.5L15 10l5 6M4 6h16v12H4z"
                                    />
                                </svg>
                                <p class="text-sm font-bold text-gray-700">
                                    Drag a prescription photo here
                                </p>
                                <p class="mt-1 text-xs text-gray-500">
                                    JPG, PNG, TIFF, BMP, WEBP · up to 10 MB
                                </p>
                                <button
                                    type="button"
                                    class="mt-4 rounded-lg bg-[#0f8c8e] px-4 py-2 text-xs font-bold text-white shadow-sm transition-colors hover:bg-[#0c7072]"
                                    @click="fileInput?.click()"
                                >
                                    Browse Files
                                </button>
                            </template>

                            <template v-else>
                                <div class="flex items-center gap-4 text-left">
                                    <img
                                        :src="previewUrl ?? ''"
                                        alt="Selected scan preview"
                                        class="h-24 w-24 shrink-0 rounded-lg border border-gray-200 object-cover"
                                    />
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-bold text-gray-800">
                                            {{ file.name }}
                                        </p>
                                        <p class="mt-1 text-xs text-gray-500">
                                            {{ humanSize(file.size) }} · ready to scan
                                        </p>
                                        <button
                                            type="button"
                                            class="mt-3 rounded-lg border border-gray-300 px-3 py-1.5 text-[11px] font-bold text-gray-600 transition-colors hover:bg-gray-100"
                                            @click="clearFile"
                                        >
                                            Choose Different
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <p
                            v-if="uploadError"
                            class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-xs font-bold text-red-700"
                            role="alert"
                        >
                            {{ uploadError }}
                        </p>

                        <!-- Document type -->
                        <div>
                            <label
                                class="mb-2 block text-xs font-extrabold tracking-widest text-[#146b8a] uppercase"
                            >
                                Document Type
                            </label>
                            <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                                <button
                                    v-for="option in DOC_TYPES"
                                    :key="option.value"
                                    type="button"
                                    class="rounded-lg border px-3 py-2 text-left transition-all"
                                    :class="
                                        docType === option.value
                                            ? 'border-teal-500 bg-teal-50 ring-1 ring-teal-500'
                                            : 'border-gray-200 bg-white hover:border-teal-300'
                                    "
                                    @click="docType = option.value"
                                >
                                    <span
                                        class="block text-xs font-bold"
                                        :class="
                                            docType === option.value
                                                ? 'text-teal-800'
                                                : 'text-gray-700'
                                        "
                                    >
                                        {{ option.label }}
                                    </span>
                                    <span class="block text-[10px] text-gray-500">
                                        {{ option.hint }}
                                    </span>
                                </button>
                            </div>
                        </div>

                        <!-- Scan list -->
                        <div v-if="liveScans.length">
                            <div class="mb-2 flex items-center justify-between">
                                <h3
                                    class="text-xs font-extrabold tracking-widest text-[#146b8a] uppercase"
                                >
                                    Scans for this appointment
                                </h3>
                                <span class="text-[11px] font-bold text-gray-500">
                                    {{ completedCount }} ready ·
                                    {{ failedCount }} failed
                                </span>
                            </div>

                            <ul class="space-y-2">
                                <li
                                    v-for="scan in liveScans"
                                    :key="scan.id"
                                    class="flex items-center justify-between gap-3 rounded-lg border border-gray-200 bg-white px-4 py-2.5"
                                >
                                    <div class="flex min-w-0 items-center gap-2">
                                        <span class="text-xs font-bold text-gray-800">
                                            {{ typeLabel(scan.type) }}
                                        </span>
                                        <span class="text-[10px] text-gray-400">
                                            #{{ scan.id }}
                                        </span>
                                    </div>

                                    <div class="flex shrink-0 items-center gap-2">
                                        <span
                                            class="inline-flex items-center gap-1.5 rounded-md border px-2.5 py-1 text-[10px] font-bold tracking-wider uppercase"
                                            :class="
                                                scan.ocr_status === 'completed'
                                                    ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
                                                    : scan.ocr_status === 'failed'
                                                      ? 'border-red-200 bg-red-50 text-red-700'
                                                      : 'border-blue-200 bg-blue-50 text-blue-700'
                                            "
                                        >
                                            <svg
                                                v-if="
                                                    scan.ocr_status === 'pending' ||
                                                    scan.ocr_status === 'processing'
                                                "
                                                class="h-3 w-3 animate-spin"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                            >
                                                <circle
                                                    class="opacity-25"
                                                    cx="12"
                                                    cy="12"
                                                    r="10"
                                                    stroke="currentColor"
                                                    stroke-width="4"
                                                />
                                                <path
                                                    class="opacity-75"
                                                    fill="currentColor"
                                                    d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                                                />
                                            </svg>
                                            {{ scan.ocr_status }}
                                        </span>

                                        <button
                                            v-if="scan.ocr_status === 'failed'"
                                            type="button"
                                            :disabled="retryingId !== null"
                                            class="rounded-md border border-amber-300 bg-amber-50 px-2.5 py-1 text-[10px] font-bold tracking-wider text-amber-800 uppercase transition-colors hover:bg-amber-100 disabled:cursor-not-allowed disabled:opacity-50"
                                            @click="retryScan(scan)"
                                        >
                                            {{
                                                retryingId === scan.id
                                                    ? 'Retrying…'
                                                    : 'Retry OCR'
                                            }}
                                        </button>
                                    </div>
                                </li>
                            </ul>

                            <p
                                v-if="retryError"
                                class="mt-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-[11px] font-bold text-red-700"
                                role="alert"
                            >
                                {{ retryError }}
                            </p>

                            <button
                                v-if="failedScans.length > 1"
                                type="button"
                                :disabled="retryingId !== null"
                                class="mt-2 w-full rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-[11px] font-bold tracking-wider text-amber-800 uppercase transition-colors hover:bg-amber-100 disabled:cursor-not-allowed disabled:opacity-50"
                                @click="retryAllFailed"
                            >
                                Retry all {{ failedScans.length }} failed scans
                            </button>

                            <p
                                v-if="failedScans.length"
                                class="mt-2 text-[11px] text-amber-700"
                            >
                                OCR usually fails when the local OCR service or
                                the queue worker is not running. Starting them and
                                retrying will pick the scan back up.
                            </p>

                            <p
                                v-if="busyScanIds.length"
                                class="mt-2 text-[11px] text-gray-500"
                            >
                                OCR runs in the background — this list updates by
                                itself. Large, low-contrast photos take longer.
                            </p>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div
                        class="flex shrink-0 items-center justify-between gap-3 border-t border-gray-100 bg-gray-50 px-6 py-4"
                    >
                        <p class="text-[11px] text-gray-500">
                            The doctor reviews converted text during the
                            consultation.
                        </p>
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                class="rounded-lg px-4 py-2 text-xs font-bold text-gray-600 transition-colors hover:bg-gray-200"
                                @click="close"
                            >
                                Close
                            </button>
                            <button
                                type="button"
                                :disabled="!file || uploading"
                                class="inline-flex items-center gap-2 rounded-lg bg-[#0f8c8e] px-5 py-2 text-xs font-bold text-white shadow-sm transition-colors hover:bg-[#0c7072] disabled:cursor-not-allowed disabled:opacity-50"
                                @click="upload"
                            >
                                <svg
                                    v-if="uploading"
                                    class="h-4 w-4 animate-spin"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <circle
                                        class="opacity-25"
                                        cx="12"
                                        cy="12"
                                        r="10"
                                        stroke="currentColor"
                                        stroke-width="4"
                                    />
                                    <path
                                        class="opacity-75"
                                        fill="currentColor"
                                        d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                                    />
                                </svg>
                                {{ uploading ? 'Uploading…' : 'Upload & Scan' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>