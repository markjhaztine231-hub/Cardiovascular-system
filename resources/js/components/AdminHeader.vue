<template>
    <header
        class="flex h-20 shrink-0 items-center justify-between border-b border-gray-200 bg-white px-4 shadow-sm sm:px-6 lg:px-8"
    >
        <div class="flex items-center gap-3">
            <button
                type="button"
                class="inline-flex items-center justify-center rounded-md p-2 text-gray-600 transition-colors hover:bg-gray-100 hover:text-gray-900"
                @click="toggle"
                aria-label="Open sidebar"
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
                        d="M4 6h16M4 12h16M4 18h16"
                    />
                </svg>
            </button>
            <h2 class="text-xl font-black tracking-tight text-slate-800">
                {{ title }}
            </h2>
        </div>
        <div class="flex items-center gap-4">
            <span
                class="hidden rounded-lg border border-indigo-100 bg-indigo-50 px-3 py-1.5 text-xs font-extrabold tracking-widest text-indigo-700 uppercase sm:inline-flex"
                >Super Admin</span
            >
            <Link
                href="/profile"
                class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-600 font-bold text-white shadow-md transition-colors hover:bg-indigo-700"
                :title="user?.name"
            >
                {{ user?.name?.charAt(0) ?? '?' }}
            </Link>
        </div>
    </header>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { useSidebar } from '@/composables/useSidebar';

defineProps<{
    title: string;
    user?: any;
}>();

const { toggle } = useSidebar('admin');
</script>
