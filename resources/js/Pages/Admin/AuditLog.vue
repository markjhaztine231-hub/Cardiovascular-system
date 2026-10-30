<template>
    <div
        class="flex h-screen bg-[#f1f5f9] font-sans selection:bg-indigo-500 selection:text-white"
    >
        <AdminSidebar :user="user" />

        <main class="flex h-screen flex-1 flex-col overflow-hidden">
            <AdminHeader title="Audit Log" :user="user" />

            <div class="relative flex-1 overflow-y-auto">
                <div class="mx-auto max-w-7xl space-y-6 p-5 lg:p-8">
                    <AdminPageHeader
                        eyebrow="Security & records"
                        title="Audit log"
                        description="A chronological record of administrative and clinical actions. Search by what happened or who performed it."
                    />

                    <div
                        class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4"
                    >
                        <AdminStatCard
                            v-for="card in summaryCards"
                            :key="card.label"
                            :label="card.label"
                            :value="card.value"
                            :hint="card.hint"
                            :icon="card.icon"
                            :tone="card.tone"
                        />
                    </div>

                    <AdminSectionCard
                        title="Activity stream"
                        :subtitle="resultSummary"
                        :padded="false"
                    >
                        <template #actions>
                            <span
                                class="rounded-full bg-indigo-50 px-3 py-1 text-[10px] font-black tracking-wide text-indigo-600 uppercase ring-1 ring-indigo-100"
                            >
                                {{ logs.total }} entr{{
                                    logs.total === 1 ? 'y' : 'ies'
                                }}
                            </span>
                        </template>

                        <div class="border-b border-gray-100 p-4 sm:p-5">
                            <div
                                class="flex flex-col gap-3 lg:flex-row lg:items-center"
                            >
                                <div class="relative flex-1 lg:max-w-md">
                                    <Search
                                        class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-slate-400"
                                    />
                                    <input
                                        v-model="search"
                                        type="text"
                                        placeholder="Search actions or user name..."
                                        class="w-full rounded-xl border border-gray-200 bg-slate-50 py-2.5 pr-3 pl-9 text-sm font-medium text-slate-800 transition outline-none placeholder:text-slate-400 focus:border-indigo-400 focus:bg-white focus:ring-2 focus:ring-indigo-500/15"
                                        @input="debouncedSearch"
                                    />
                                </div>

                                <div class="flex flex-wrap items-center gap-2">
                                    <input
                                        v-model="date"
                                        type="date"
                                        class="rounded-xl border border-gray-200 bg-slate-50 px-3.5 py-2.5 text-sm font-semibold text-slate-700 transition outline-none focus:border-indigo-400 focus:bg-white focus:ring-2 focus:ring-indigo-500/15"
                                        aria-label="Filter by date"
                                        @change="fetchLogs"
                                    />
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-sm font-bold text-gray-600 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40"
                                        :disabled="!hasFilters"
                                        @click="clearFilters"
                                    >
                                        <X class="h-3.5 w-3.5" />
                                        Clear
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="max-h-[calc(100vh-30rem)] overflow-auto">
                            <table class="w-full min-w-[52rem]">
                                <thead class="sticky top-0 z-10 bg-slate-50">
                                    <tr class="border-b border-gray-100">
                                        <th
                                            class="px-6 py-3.5 text-left text-[10px] font-extrabold tracking-widest text-gray-500 uppercase"
                                        >
                                            Actor
                                        </th>
                                        <th
                                            class="px-6 py-3.5 text-left text-[10px] font-extrabold tracking-widest text-gray-500 uppercase"
                                        >
                                            Action
                                        </th>
                                        <th
                                            class="px-6 py-3.5 text-right text-[10px] font-extrabold tracking-widest text-gray-500 uppercase"
                                        >
                                            When
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <tr
                                        v-for="log in logs.data"
                                        :key="log.id ?? log.timestamp"
                                        class="transition-colors hover:bg-indigo-50/40"
                                    >
                                        <td class="px-6 py-3.5">
                                            <div
                                                class="flex items-center gap-3"
                                            >
                                                <span
                                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-xs font-black text-slate-600"
                                                    >{{
                                                        log.user
                                                            .charAt(0)
                                                            .toUpperCase()
                                                    }}</span
                                                >
                                                <div class="min-w-0">
                                                    <p
                                                        class="truncate text-sm font-bold text-slate-700"
                                                    >
                                                        {{ log.user }}
                                                    </p>
                                                    <p
                                                        class="text-[10px] font-bold tracking-wide text-gray-400 uppercase"
                                                    >
                                                        {{
                                                            log.user ===
                                                            'System'
                                                                ? 'Automated event'
                                                                : 'User action'
                                                        }}
                                                    </p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-3.5">
                                            <div
                                                class="flex items-start gap-2.5"
                                            >
                                                <span
                                                    class="mt-1.5 h-2 w-2 shrink-0 rounded-full ring-4"
                                                    :class="
                                                        actionTone(log.action)
                                                    "
                                                ></span>
                                                <p
                                                    class="text-sm leading-relaxed font-medium text-slate-600"
                                                >
                                                    {{ log.action }}
                                                </p>
                                            </div>
                                        </td>
                                        <td
                                            class="px-6 py-3.5 text-right whitespace-nowrap"
                                            :title="log.timestamp"
                                        >
                                            <span
                                                class="inline-flex items-center gap-1.5 text-xs font-semibold text-gray-500"
                                            >
                                                <Clock
                                                    class="h-3.5 w-3.5 text-gray-400"
                                                />
                                                {{ log.time }}
                                            </span>
                                        </td>
                                    </tr>

                                    <tr v-if="logs.data.length === 0">
                                        <td colspan="3" class="px-6 py-16">
                                            <div
                                                class="flex flex-col items-center gap-3 text-center"
                                            >
                                                <span
                                                    class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400"
                                                >
                                                    <ScrollText
                                                        class="h-5 w-5"
                                                    />
                                                </span>
                                                <div>
                                                    <p
                                                        class="text-sm font-bold text-slate-600"
                                                    >
                                                        No audit entries found
                                                    </p>
                                                    <p
                                                        class="mt-1 text-xs text-gray-400"
                                                    >
                                                        Try a different search
                                                        term or clear the date
                                                        filter.
                                                    </p>
                                                </div>
                                                <button
                                                    v-if="hasFilters"
                                                    type="button"
                                                    class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-bold text-gray-600 transition hover:bg-gray-50"
                                                    @click="clearFilters"
                                                >
                                                    Clear filters
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div
                            class="flex flex-col gap-3 border-t border-gray-100 p-4 sm:flex-row sm:items-center sm:justify-between sm:px-6"
                        >
                            <p class="text-xs font-medium text-gray-500">
                                <template v-if="logs.total > 0">
                                    Showing {{ rangeStart }}-{{ rangeEnd }} of
                                    {{ logs.total }} entries
                                </template>
                                <template v-else>No entries to show</template>
                            </p>
                            <AdminPagination
                                :current-page="logs.current_page"
                                :last-page="logs.last_page"
                                :url-for="pageUrl"
                            />
                        </div>
                    </AdminSectionCard>
                </div>
            </div>
        </main>
    </div>
</template>

<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import {
    CalendarDays,
    Clock,
    ScrollText,
    Search,
    Users,
    X,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import type { Component } from 'vue';
import AdminPageHeader from '@/components/admin/AdminPageHeader.vue';
import AdminPagination from '@/components/admin/AdminPagination.vue';
import AdminSectionCard from '@/components/admin/AdminSectionCard.vue';
import AdminStatCard from '@/components/admin/AdminStatCard.vue';
import AdminHeader from '@/components/AdminHeader.vue';
import AdminSidebar from '@/components/AdminSidebar.vue';

type Tone = 'indigo' | 'emerald' | 'sky' | 'amber' | 'red' | 'slate';

interface AuditEntry {
    id?: number;
    user: string;
    action: string;
    time: string;
    timestamp: string;
}

interface PaginatedLogs {
    data: AuditEntry[];
    total: number;
    current_page: number;
    last_page: number;
    path: string;
}

interface AuditSummary {
    total: number;
    today: number;
    actors: number;
    last: string;
}

interface SummaryCard {
    label: string;
    value: string | number;
    hint: string;
    icon: Component;
    tone: Tone;
}

const props = defineProps<{
    user: { name?: string };
    logs: PaginatedLogs;
    summary: AuditSummary;
    filters: { search?: string; date?: string };
}>();

const PER_PAGE = 20;

const search = ref(props.filters.search || '');
const date = ref(props.filters.date || '');

const hasFilters = computed(() => !!search.value || !!date.value);

const resultSummary = computed(() =>
    hasFilters.value
        ? 'Filtered activity matching your current search'
        : 'Complete history of recorded system activity',
);

const rangeStart = computed(() =>
    props.logs.total === 0 ? 0 : (props.logs.current_page - 1) * PER_PAGE + 1,
);

const rangeEnd = computed(() =>
    Math.min(props.logs.current_page * PER_PAGE, props.logs.total),
);

const summaryCards = computed<SummaryCard[]>(() => [
    {
        label: 'Total events',
        value: props.summary.total,
        hint: 'All time',
        icon: ScrollText,
        tone: 'indigo',
    },
    {
        label: 'Today',
        value: props.summary.today,
        hint: 'Last 24h',
        icon: CalendarDays,
        tone: 'emerald',
    },
    {
        label: 'Active actors',
        value: props.summary.actors,
        hint: 'Unique users',
        icon: Users,
        tone: 'sky',
    },
    {
        label: 'Last activity',
        value: props.summary.last,
        hint: 'Most recent',
        icon: Clock,
        tone: 'amber',
    },
]);

const actionTone = (action: string): string => {
    const normalized = action.toLowerCase();

    if (normalized.includes('delete') || normalized.includes('deleted')) {
        return 'bg-red-500 ring-red-100';
    }

    if (normalized.includes('create') || normalized.includes('created')) {
        return 'bg-emerald-500 ring-emerald-100';
    }

    if (normalized.includes('update') || normalized.includes('updated')) {
        return 'bg-sky-500 ring-sky-100';
    }

    return 'bg-indigo-500 ring-indigo-100';
};

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
