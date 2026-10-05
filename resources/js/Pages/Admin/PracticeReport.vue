<template>
    <div class="flex h-screen bg-[#f1f5f9] font-sans selection:bg-indigo-500 selection:text-white">
        <AdminSidebar />

        <main class="flex h-screen flex-1 flex-col overflow-hidden">
            <header class="flex h-20 shrink-0 items-center justify-between border-b border-gray-200 bg-white px-8 shadow-sm">
                <div class="flex items-center gap-4">
                    <h2 class="text-xl font-black tracking-tight text-slate-800">Practice-Wide Monthly Report</h2>
                    <span class="rounded-lg border border-indigo-100 bg-indigo-50 px-3 py-1.5 text-xs font-extrabold tracking-widest text-indigo-700 uppercase">
                        Admin only
                    </span>
                </div>
                <div class="flex items-center gap-4">
                    <select v-model="selectedMonth" class="rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-800 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20" @change="loadMonth">
                        <option v-for="m in monthOptions" :key="m" :value="m">{{ formatMonth(m) }}</option>
                    </select>
                    <Link href="/profile" class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-600 font-bold text-white shadow-md transition-colors hover:bg-indigo-700" :title="user.name">
                        {{ user.name.charAt(0) }}
                    </Link>
                </div>
            </header>

            <div class="relative flex-1 overflow-y-auto p-8">
                <div class="mx-auto max-w-7xl space-y-6">
                    <!-- Flash -->
                    <div
                        v-if="flash.success"
                        class="rounded-2xl border border-emerald-200 bg-emerald-50 px-6 py-4 text-sm font-bold text-emerald-800"
                    >
                        {{ flash.success }}
                    </div>
                    <div
                        v-if="flash.error"
                        class="rounded-2xl border border-rose-200 bg-rose-50 px-6 py-4 text-sm font-bold text-rose-800"
                    >
                        {{ flash.error }}
                    </div>

                    <!-- Generate -->
                    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                        <div class="flex flex-wrap items-end justify-between gap-4">
                            <div>
                                <h3 class="text-sm font-black tracking-widest text-slate-500 uppercase">Generate</h3>
                                <p class="mt-1 text-sm text-slate-500">
                                    Builds the all-doctors narrative and the per-doctor breakdown for one month.
                                </p>
                            </div>
                            <div class="flex items-end gap-3">
                                <input v-model="generateMonth" type="month" class="rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-800 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20" />
                                <button
                                    :disabled="generating"
                                    @click="generate"
                                    class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-extrabold text-white shadow-md transition-colors hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    {{ generating ? 'Generating…' : 'Generate' }}
                                </button>
                            </div>
                        </div>
                        <p v-if="generatedAt" class="mt-4 text-xs font-medium text-slate-400">
                            Showing the report generated at {{ generatedAt }}.
                        </p>
                    </div>

                    <!-- Practice totals -->
                    <div class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-6">
                        <div v-for="card in totalCards" :key="card.label" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                            <p class="text-[11px] font-extrabold tracking-widest text-slate-400 uppercase">{{ card.label }}</p>
                            <p class="mt-2 text-3xl font-black text-slate-800">{{ card.value }}</p>
                            <p v-if="card.hint" class="mt-1 text-xs font-medium text-slate-400">{{ card.hint }}</p>
                        </div>
                    </div>

                    <!-- Narrative -->
                    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
                        <div class="border-b border-gray-100 px-6 py-4">
                            <h3 class="text-sm font-black tracking-widest text-slate-500 uppercase">
                                Narrative — {{ formatMonth(month) }}
                            </h3>
                        </div>
                        <div class="px-6 py-5">
                            <p v-if="reportText" class="text-sm leading-relaxed whitespace-pre-line text-slate-700">{{ reportText }}</p>
                            <p v-else class="text-sm text-slate-400">
                                No narrative for this month. There may have been no appointments.
                            </p>
                        </div>
                    </div>

                    <!-- Age distribution -->
                    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
                        <div class="border-b border-gray-100 px-6 py-4">
                            <h3 class="text-sm font-black tracking-widest text-slate-500 uppercase">Age distribution</h3>
                            <p class="mt-1 text-xs text-slate-400">
                                All doctors combined. Each patient is counted once, banded on their first visit of the month.
                            </p>
                        </div>
                        <div class="space-y-3 px-6 py-5">
                            <div v-for="band in ageDistribution" :key="band.band" class="flex items-center gap-4">
                                <span class="w-28 shrink-0 text-xs font-bold text-slate-600">{{ band.label }}</span>
                                <div class="h-7 flex-1 overflow-hidden rounded-lg bg-gray-100">
                                    <div
                                        class="h-full rounded-lg bg-indigo-500 transition-all duration-500"
                                        :style="{ width: barWidth(band.pct) }"
                                    ></div>
                                </div>
                                <span class="w-24 shrink-0 text-right text-xs font-bold text-slate-500">
                                    {{ band.count }} · {{ band.pct.toFixed(1) }}%
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Per-doctor breakdown -->
                    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
                        <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                            <div>
                                <h3 class="text-sm font-black tracking-widest text-slate-500 uppercase">
                                    Per-doctor breakdown
                                </h3>
                                <p class="mt-1 text-xs text-slate-400">
                                    Volumes and age mix only — these figures are not a performance ranking.
                                </p>
                            </div>
                            <span class="rounded-lg bg-gray-100 px-3 py-1.5 text-xs font-extrabold text-slate-600">
                                {{ byProvider.length }} doctor{{ byProvider.length === 1 ? '' : 's' }}
                            </span>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm">
                                <thead class="bg-gray-50 text-[11px] font-extrabold tracking-widest text-slate-500 uppercase">
                                    <tr>
                                        <th class="px-6 py-3">Doctor</th>
                                        <th class="px-4 py-3 text-right">Appointments</th>
                                        <th class="px-4 py-3 text-right">Completed</th>
                                        <th class="px-4 py-3 text-right">No-shows</th>
                                        <th class="px-4 py-3 text-right">Patients</th>
                                        <th class="px-4 py-3 text-right">Avg age</th>
                                        <th class="px-4 py-3 text-right">&lt;30</th>
                                        <th class="px-6 py-3 text-left">Top diagnosis</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <tr v-for="row in byProvider" :key="row.provider_id" class="transition-colors hover:bg-gray-50">
                                        <td class="px-6 py-3">
                                            <p class="font-bold text-slate-800">{{ row.doctor_name }}</p>
                                            <p v-if="row.specialty" class="text-xs text-slate-400">{{ row.specialty }}</p>
                                        </td>
                                        <td class="px-4 py-3 text-right font-black text-slate-800">{{ row.total }}</td>
                                        <td class="px-4 py-3 text-right text-slate-600">{{ row.completed }}</td>
                                        <td class="px-4 py-3 text-right" :class="row.no_shows > 0 ? 'font-bold text-amber-600' : 'text-slate-600'">
                                            {{ row.no_shows }}
                                        </td>
                                        <td class="px-4 py-3 text-right text-slate-600">{{ row.unique_patients }}</td>
                                        <td class="px-4 py-3 text-right text-slate-600">{{ avgAge(row.avg_age) }}</td>
                                        <td class="px-4 py-3 text-right font-bold" :class="underThirty(row) > 0 ? 'text-amber-600' : 'text-slate-400'">
                                            {{ underThirty(row) }}
                                        </td>
                                        <td class="px-6 py-3 text-slate-600">{{ row.top_diagnosis ?? '—' }}</td>
                                    </tr>
                                    <tr v-if="byProvider.length === 0">
                                        <td colspan="8" class="px-6 py-10 text-center text-sm text-slate-400">
                                            No doctor activity recorded for {{ formatMonth(month) }}.
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot v-if="byProvider.length > 0" class="border-t-2 border-gray-200 bg-gray-50 text-xs font-black text-slate-700">
                                    <tr>
                                        <td class="px-6 py-3">Practice total</td>
                                        <td class="px-4 py-3 text-right">{{ stats.total ?? 0 }}</td>
                                        <td class="px-4 py-3 text-right">{{ stats.completed ?? 0 }}</td>
                                        <td class="px-4 py-3 text-right">{{ stats.no_shows ?? 0 }}</td>
                                        <td class="px-4 py-3 text-right">{{ uniquePatientsTotal }}</td>
                                        <td class="px-4 py-3 text-right">{{ avgAge(stats.demographics?.avg_age) }}</td>
                                        <td class="px-4 py-3 text-right">{{ underThirtyTotal }}</td>
                                        <td class="px-6 py-3"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- Top diagnoses -->
                    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
                        <div class="border-b border-gray-100 px-6 py-4">
                            <h3 class="text-sm font-black tracking-widest text-slate-500 uppercase">Top diagnoses</h3>
                        </div>
                        <div class="space-y-3 px-6 py-5">
                            <div v-for="d in topDiagnoses" :key="d.name" class="flex items-center gap-4">
                                <span class="w-64 shrink-0 truncate text-xs font-bold text-slate-600">{{ d.name }}</span>
                                <div class="h-2 flex-1 overflow-hidden rounded bg-gray-100">
                                    <div class="h-full rounded bg-slate-400" :style="{ width: diagnosisWidth(d.count) }"></div>
                                </div>
                                <span class="w-10 shrink-0 text-right text-xs font-bold text-slate-500">{{ d.count }}</span>
                            </div>
                            <p v-if="topDiagnoses.length === 0" class="text-sm text-slate-400">No diagnoses recorded.</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>

<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AdminSidebar from '@/components/AdminSidebar.vue';

const props = defineProps<{
    user: any;
    month: string;
    stats: any;
    reportText: string | null;
    generatedAt: string | null;
    byProvider: any[];
    availableMonths: string[];
}>();

const generating = ref(false);
const generateMonth = ref(props.month);
const selectedMonth = ref(props.month);

const flash = computed(() => (usePage().props as any).flash ?? {});

/** Always offers the current month plus whatever has already been generated. */
const monthOptions = computed(() => {
    const set = new Set<string>(props.availableMonths);
    set.add(props.month);
    return [...set].sort().reverse();
});

const ageDistribution = computed<any[]>(() => props.stats?.age_distribution ?? []);

const topDiagnoses = computed<any[]>(() => props.stats?.top_diagnoses ?? []);

/** Doctors' own patient counts can overlap, so this is a sum, not the practice total. */
const uniquePatientsTotal = computed(() =>
    props.byProvider.reduce((sum: number, r: any) => sum + (r.unique_patients ?? 0), 0),
);

const totalCards = computed(() => [
    { label: 'Appointments', value: props.stats?.total ?? 0 },
    { label: 'Completed', value: props.stats?.completed ?? 0 },
    { label: 'No-shows', value: props.stats?.no_shows ?? 0, hint: (props.stats?.no_shows ?? 0) > 0 ? 'reminder target' : '' },
    { label: 'Cancelled', value: props.stats?.cancelled ?? 0 },
    { label: 'Procedures', value: props.stats?.procedures?.total ?? 0 },
    { label: 'Avg age', value: avgAge(props.stats?.demographics?.avg_age), hint: `${props.stats?.demographics?.total_unique_patients ?? 0} patients` },
]);

const underThirty = (row: any): number => {
    const bands: any[] = row?.age_distribution ?? [];
    const band = bands.find((b) => b.band === '18-29');
    return band?.count ?? 0;
};

const underThirtyTotal = computed(() =>
    props.byProvider.reduce((sum: number, r: any) => sum + underThirty(r), 0),
);

const barWidth = (pct: number): string => `${Math.max(pct, pct > 0 ? 2 : 0)}%`;

const diagnosisWidth = (count: number): string => {
    const max = Math.max(...topDiagnoses.value.map((d) => d.count ?? 0), 1);
    return `${(count / max) * 100}%`;
};

const avgAge = (value: number | null | undefined): string =>
    value === null || value === undefined ? '—' : `${Number(value).toFixed(1)}`;

const formatMonth = (m: string): string => {
    if (!/^\d{4}-\d{2}$/.test(m)) return m;
    const [y, mo] = m.split('-');
    return `${new Date(Number(y), Number(mo) - 1, 1).toLocaleString('en-US', { month: 'long', year: 'numeric' })}`;
};

const loadMonth = () => {
    router.get('/admin/reports/practice', { month: selectedMonth.value }, { preserveState: false });
};

const generate = async () => {
    if (!/^\d{4}-\d{2}$/.test(generateMonth.value)) return;

    generating.value = true;

    try {
        await router.post(
            '/admin/reports/practice',
            { month: generateMonth.value },
            {
                preserveState: false,
                onSuccess: () => {
                    selectedMonth.value = generateMonth.value;
                    loadMonth();
                },
            },
        );
    } finally {
        generating.value = false;
    }
};
</script>