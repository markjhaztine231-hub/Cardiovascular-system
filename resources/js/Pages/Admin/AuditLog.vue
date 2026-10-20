<template>
    <div
        class="flex h-screen bg-[#f1f5f9] font-sans selection:bg-indigo-500 selection:text-white"
    >
        <AdminSidebar />

        <main class="flex h-screen flex-1 flex-col overflow-hidden">
            <AdminHeader title="Audit Log" :user="user" />

            <div class="relative flex-1 overflow-y-auto p-8">
                <div class="mx-auto max-w-7xl space-y-6">
                    <div class="flex items-center gap-4">
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Search actions or user name..."
                            class="w-80 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-800 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                            @input="debouncedSearch"
                        />
                        <input
                            v-model="date"
                            type="date"
                            class="rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-800 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                            @change="fetchLogs"
                        />
                        <button
                            @click="clearFilters"
                            class="rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-bold text-gray-600 transition-colors hover:bg-gray-50"
                        >
                            Clear
                        </button>
                        <span class="ml-auto text-sm font-medium text-gray-500">
                            <template v-if="logs.total > 0">
                                Showing {{ rangeStart }}–{{ rangeEnd }} of
                                {{ logs.total }} entries
                            </template>
                            <template v-else>No entries</template>
                        </span>
                    </div>

                    <div
                        class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
                    >
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-gray-100 bg-gray-50">
                                    <th
                                        class="px-6 py-4 text-left text-[10px] font-extrabold tracking-widest text-gray-500 uppercase"
                                    >
                                        User
                                    </th>
                                    <th
                                        class="px-6 py-4 text-left text-[10px] font-extrabold tracking-widest text-gray-500 uppercase"
                                    >
                                        Action
                                    </th>
                                    <th
                                        class="px-6 py-4 text-right text-[10px] font-extrabold tracking-widest text-gray-500 uppercase"
                                    >
                                        Timestamp
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr
                                    v-for="log in logs.data"
                                    :key="log.id ?? log.timestamp"
                                    class="transition-colors hover:bg-gray-50"
                                >
                                    <td
                                        class="px-6 py-4 text-sm font-bold text-gray-700"
                                    >
                                        {{ log.user }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        {{ log.action }}
                                    </td>
                                    <td
                                        class="px-6 py-4 text-right text-sm text-gray-500"
                                        :title="log.timestamp"
                                    >
                                        {{ log.time }}
                                    </td>
                                </tr>
                                <tr v-if="logs.data.length === 0">
                                    <td
                                        colspan="3"
                                        class="px-6 py-12 text-center text-sm font-medium text-gray-400"
                                    >
                                        No audit log entries found.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div
                        v-if="logs.last_page > 1"
                        class="flex items-center justify-center gap-1.5"
                    >
                        <Link
                            v-if="logs.current_page > 1"
                            :href="pageUrl(logs.current_page - 1)"
                            class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-sm font-bold text-gray-700 transition-colors hover:bg-gray-50"
                        >
                            Previous
                        </Link>

                        <template
                            v-for="(item, index) in pageItems"
                            :key="`${item}-${index}`"
                        >
                            <span
                                v-if="item === 'gap'"
                                class="px-1.5 text-sm font-bold text-gray-400"
                                >…</span
                            >
                            <Link
                                v-else
                                :href="pageUrl(item as number)"
                                class="rounded-lg border px-3 py-1.5 text-sm font-bold transition-colors"
                                :class="
                                    item === logs.current_page
                                        ? 'border-indigo-500 bg-indigo-600 text-white'
                                        : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'
                                "
                            >
                                {{ item }}
                            </Link>
                        </template>

                        <Link
                            v-if="logs.current_page < logs.last_page"
                            :href="pageUrl(logs.current_page + 1)"
                            class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-sm font-bold text-gray-700 transition-colors hover:bg-gray-50"
                        >
                            Next
                        </Link>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>

<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AdminHeader from '@/components/AdminHeader.vue';
import AdminSidebar from '@/components/AdminSidebar.vue';

const props = defineProps<{
    user: any;
    logs: any;
    filters: { search?: string; date?: string };
}>();

const PER_PAGE = 20;

const search = ref(props.filters.search || '');
const date = ref(props.filters.date || '');

const rangeStart = computed(() =>
    props.logs.total === 0 ? 0 : (props.logs.current_page - 1) * PER_PAGE + 1,
);

const rangeEnd = computed(() =>
    Math.min(props.logs.current_page * PER_PAGE, props.logs.total),
);

/** Keeps active filters on every page link. */
const pageUrl = (page: number): string => {
    const params = new URLSearchParams({ page: String(page) });

    if (props.filters.search) {
        params.set('search', props.filters.search);
    }

    if (props.filters.date) {
        params.set('date', props.filters.date);
    }

    return `${props.logs.path}?${params.toString()}`;
};

/**
 * A windowed page list with gaps, so a log with thousands of entries does not
 * render thousands of buttons.
 */
const pageItems = computed<(number | 'gap')[]>(() => {
    const last = props.logs.last_page ?? 1;
    const current = props.logs.current_page ?? 1;

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

let debounceTimer: ReturnType<typeof setTimeout> | null = null;
const debouncedSearch = () => {
    if (debounceTimer) {
        clearTimeout(debounceTimer);
    }

    debounceTimer = setTimeout(fetchLogs, 300);
};

const fetchLogs = () => {
    router.get(
        '/admin/audit-log',
        { search: search.value, date: date.value },
        { preserveState: true, replace: true },
    );
};

const clearFilters = () => {
    search.value = '';
    date.value = '';
    router.get('/admin/audit-log', {}, { preserveState: true, replace: true });
};
</script>
