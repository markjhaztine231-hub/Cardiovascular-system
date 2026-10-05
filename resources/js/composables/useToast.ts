/**
 * Toast notification composable - replaces browser alerts
 * Deduplicates identical toasts and auto-removes after 5 seconds.
 */

import { ref } from 'vue';

export interface Toast {
    id: string;
    message: string;
    type: 'success' | 'error' | 'info' | 'warning';
    duration?: number;
}

const toasts = ref<Toast[]>([]);

export function useToast() {
    function addToast(
        message: string,
        type: 'success' | 'error' | 'info' | 'warning' = 'info',
        duration = 5000,
    ) {
        const existing = toasts.value.find((t) => t.message === message && t.type === type);

        if (existing) return existing.id;

        const id = `toast-${Date.now()}-${Math.random().toString(36).slice(2, 6)}`;
        const toast: Toast = { id, message, type, duration };

        toasts.value.push(toast);

        if (duration > 0) {
            setTimeout(() => removeToast(id), duration);
        }

        return id;
    }

    function removeToast(id: string) {
        const index = toasts.value.findIndex((t) => t.id === id);

        if (index !== -1) {
            toasts.value.splice(index, 1);
        }
    }

    function success(message: string, duration = 5000) {
        return addToast(message, 'success', duration);
    }

    function error(message: string, duration = 5000) {
        return addToast(message, 'error', duration);
    }

    function info(message: string, duration = 5000) {
        return addToast(message, 'info', duration);
    }

    return {
        toasts,
        addToast,
        removeToast,
        success,
        error,
        info,
    };
}
