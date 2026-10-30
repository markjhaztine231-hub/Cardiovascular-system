<template>
    <nav
        v-if="lastPage > 1"
        class="flex flex-wrap items-center justify-center gap-1.5"
    >
        <Link
            v-if="currentPage > 1"
            :href="urlFor(currentPage - 1)"
            class="inline-flex items-center gap-1 rounded-xl border border-gray-200 bg-white px-3 py-1.5 text-xs font-bold text-gray-700 transition hover:bg-gray-50"
        >
            <ChevronLeft class="h-3.5 w-3.5" />
            Prev
        </Link>

        <template v-for="(item, index) in pageItems" :key="`${item}-${index}`">
            <span
                v-if="item === 'gap'"
                class="px-1.5 text-sm font-bold text-gray-400"
                >...</span
            >
            <Link
                v-else
                :href="urlFor(item as number)"
                class="min-w-8 rounded-xl border px-3 py-1.5 text-center text-xs font-bold transition"
                :class="
                    item === currentPage
                        ? 'border-indigo-500 bg-indigo-600 text-white'
                        : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'
                "
            >
                {{ item }}
            </Link>
        </template>

        <Link
            v-if="currentPage < lastPage"
            :href="urlFor(currentPage + 1)"
            class="inline-flex items-center gap-1 rounded-xl border border-gray-200 bg-white px-3 py-1.5 text-xs font-bold text-gray-700 transition hover:bg-gray-50"
        >
            Next
            <ChevronRight class="h-3.5 w-3.5" />
        </Link>
    </nav>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    currentPage: number;
    lastPage: number;
    urlFor: (page: number) => string;
}>();

/**
 * Windowed page list so a long directory or audit log does not render a button
 * for every page.
 */
const pageItems = computed<(number | 'gap')[]>(() => {
    const last = props.lastPage ?? 1;
    const current = props.currentPage ?? 1;

    if (last <= 7) {
        return Array.from({ length: last }, (_, index) => index + 1);
    }

    const pages = new Set<number>([1, last, current]);

    if (current - 1 > 1) {
        pages.add(current - 1);
    }

    if (current + 1 < last) {
        pages.add(current + 1);
    }

    const sorted = [...pages]
        .filter((page) => page >= 1 && page <= last)
        .sort((a, b) => a - b);

    const items: (number | 'gap')[] = [];
    let previous = 0;

    for (const page of sorted) {
        if (previous && page - previous > 1) {
            items.push('gap');
        }

        items.push(page);
        previous = page;
    }

    return items;
});
</script>
