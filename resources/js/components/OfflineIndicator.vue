<template>
    <div
        v-if="!online || pendingCount > 0"
        :class="[
            'fixed right-4 z-50 flex items-center gap-2 rounded-xl px-3 py-2 text-xs font-bold shadow-lg transition-all duration-300',
            online && pendingCount > 0
                ? 'bottom-4 border border-amber-200 bg-amber-50 text-amber-700'
                : '',
            !online
                ? 'bottom-16 border border-red-200 bg-red-50 text-red-700'
                : '',
        ]"
    >
        <span
            :class="[
                'h-2 w-2 rounded-full',
                online ? 'bg-amber-500' : 'bg-red-500',
            ]"
        ></span>
        <span v-if="!online">Offline</span>
        <span v-if="!online && pendingCount > 0"> &middot; </span>
        <span v-if="pendingCount > 0">
            {{ pendingCount }} pending
            <button
                v-if="online && pendingCount > 0"
                @click="syncNow"
                :disabled="syncing"
                class="ml-1 underline hover:no-underline disabled:opacity-50"
            >
                {{ syncing ? 'Syncing...' : 'Sync' }}
            </button>
        </span>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { useToast } from '@/composables/useToast';
import {
    isOnline,
    subscribeToSyncStatus,
    syncAllPending,
} from '@/utils/offlineSync';
import type { SyncStatusListener } from '@/utils/offlineSync';

const { success: showSuccess, error: showError } = useToast();

const online = ref(isOnline());
const pendingCount = ref(0);
const syncing = ref(false);

const listener: SyncStatusListener = (count, isOnlineStatus) => {
    pendingCount.value = count;
    online.value = isOnlineStatus;
};

let unsub: (() => void) | null = null;

onMounted(() => {
    unsub = subscribeToSyncStatus(listener);

    window.addEventListener('offline-sync-complete', ((e: CustomEvent) => {
        const { synced, failed, needsReview, message } = e.detail;

        if (synced > 0 && failed === 0 && !needsReview) {
            showSuccess(`Offline sync complete: ${message}`);
        } else if (failed > 0 || needsReview > 0) {
            showError(`Sync issues: ${message}`);
        }
    }) as EventListener);
});

onUnmounted(() => {
    if (unsub) {
        unsub();
    }
});

async function syncNow() {
    if (syncing.value) {
        return;
    }

    syncing.value = true;
    const { synced, failed, needsReview } = await syncAllPending();
    syncing.value = false;

    if (synced > 0 && failed === 0 && !needsReview) {
        showSuccess(
            `${synced} consultation${synced !== 1 ? 's' : ''} synced successfully`,
        );
    } else if (failed > 0 || needsReview > 0) {
        showError(
            `${failed + needsReview} failed to sync — the saved consultations were kept for review`,
        );
    }
}
</script>
