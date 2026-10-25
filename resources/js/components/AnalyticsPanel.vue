<template>
    <div class="rounded-2xl border border-gray-200 bg-white p-8 shadow-sm">
        <div
            @click="isOpen = !isOpen"
            class="flex cursor-pointer items-center justify-between"
        >
            <h3
                class="flex items-center text-lg font-black tracking-tight text-gray-800"
            >
                <svg
                    class="mr-2 h-5 w-5 text-emerald-600"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"
                    />
                </svg>
                Trends &amp; Analytics
            </h3>
            <div class="flex items-center gap-3">
                <span
                    v-if="loading"
                    class="h-4 w-4 animate-spin rounded-full border-2 border-emerald-500 border-t-transparent"
                ></span>
                <svg
                    :class="isOpen ? 'rotate-180' : ''"
                    class="h-5 w-5 text-gray-400 transition-transform"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M19 9l-7 7-7-7"
                    />
                </svg>
            </div>
        </div>

        <div v-if="isOpen" class="mt-6 grid grid-cols-1 gap-6 md:grid-cols-2">
            <!-- BP Chart -->
            <div
                v-if="bpData.labels.length > 0"
                class="rounded-xl border border-gray-100 bg-gray-50 p-4"
            >
                <h4
                    class="mb-3 text-xs font-bold tracking-wider text-gray-500 uppercase"
                >
                    Blood Pressure
                </h4>
                <div class="relative">
                    <canvas ref="bpCanvas"></canvas>
                </div>
            </div>

            <!-- HR Chart -->
            <div
                v-if="hrData.labels.length > 0"
                class="rounded-xl border border-gray-100 bg-gray-50 p-4"
            >
                <h4
                    class="mb-3 text-xs font-bold tracking-wider text-gray-500 uppercase"
                >
                    Heart Rate
                </h4>
                <div class="relative">
                    <canvas ref="hrCanvas"></canvas>
                </div>
            </div>

            <!-- LVEF Chart -->
            <div
                v-if="lvefData.labels.length > 0"
                class="rounded-xl border border-gray-100 bg-gray-50 p-4"
            >
                <h4
                    class="mb-3 text-xs font-bold tracking-wider text-gray-500 uppercase"
                >
                    Ejection Fraction (LVEF)
                </h4>
                <div class="relative">
                    <canvas ref="lvefCanvas"></canvas>
                </div>
            </div>

            <!-- Weight Chart -->
            <div
                v-if="weightData.labels.length > 0"
                class="rounded-xl border border-gray-100 bg-gray-50 p-4"
            >
                <h4
                    class="mb-3 text-xs font-bold tracking-wider text-gray-500 uppercase"
                >
                    Weight
                </h4>
                <div class="relative">
                    <canvas ref="weightCanvas"></canvas>
                </div>
            </div>

            <!-- Empty state -->
            <div
                v-if="!loading && !hasAnyData"
                class="col-span-full rounded-xl border border-dashed border-gray-200 bg-gray-50 p-8 text-center"
            >
                <p class="text-sm font-medium text-gray-400">
                    Not enough data to show trends. Complete consultations will
                    appear here.
                </p>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Chart, registerables } from 'chart.js';
import { ref, computed, onMounted, watch, nextTick, onUnmounted } from 'vue';

Chart.register(...registerables);

const props = defineProps<{
    patientId: number;
}>();

const isOpen = ref(false);
const loading = ref(false);
const data = ref<{
    vitals: any[];
    echo: any[];
    tmt: any[];
    doppler: any[];
}>({
    vitals: [],
    echo: [],
    tmt: [],
    doppler: [],
});

const bpCanvas = ref<HTMLCanvasElement | null>(null);
const hrCanvas = ref<HTMLCanvasElement | null>(null);
const lvefCanvas = ref<HTMLCanvasElement | null>(null);
const weightCanvas = ref<HTMLCanvasElement | null>(null);

let bpChart: Chart | null = null;
let hrChart: Chart | null = null;
let lvefChart: Chart | null = null;
let weightChart: Chart | null = null;

const bpData = computed(() => {
    const labels = data.value.vitals.map((v: any) => v.date);
    const systolic = data.value.vitals.map((v: any) => v.bp_systolic ?? null);
    const diastolic = data.value.vitals.map((v: any) => v.bp_diastolic ?? null);

    return { labels, systolic, diastolic };
});

const hrData = computed(() => {
    const labels = data.value.vitals.map((v: any) => v.date);
    const values = data.value.vitals.map((v: any) => v.hr ?? null);

    return { labels, values };
});

const lvefData = computed(() => {
    const labels = data.value.echo.map((v: any) => v.date);
    const values = data.value.echo.map((v: any) => v.lv_ef ?? null);

    return { labels, values };
});

const weightData = computed(() => {
    const map = new Map<string, number>();

    for (const v of data.value.vitals) {
        if (v.weight != null) {
            map.set(v.date, v.weight);
        }
    }

    for (const v of data.value.echo) {
        if (v.weight != null) {
            map.set(v.date, v.weight);
        }
    }

    for (const v of data.value.tmt) {
        if (v.weight != null) {
            map.set(v.date, v.weight);
        }
    }

    const sorted = [...map.entries()].sort((a, b) => {
        const da = new Date(a[0]);
        const db = new Date(b[0]);

        return da.getTime() - db.getTime();
    });

    return {
        labels: sorted.map(([d]) => d),
        values: sorted.map(([, w]) => w),
    };
});

const hasAnyData = computed(
    () =>
        bpData.value.labels.length > 0 ||
        hrData.value.labels.length > 0 ||
        lvefData.value.labels.length > 0 ||
        weightData.value.labels.length > 0,
);

function destroyCharts() {
    if (bpChart) {
        bpChart.destroy();
        bpChart = null;
    }

    if (hrChart) {
        hrChart.destroy();
        hrChart = null;
    }

    if (lvefChart) {
        lvefChart.destroy();
        lvefChart = null;
    }

    if (weightChart) {
        weightChart.destroy();
        weightChart = null;
    }
}

function buildChart(
    canvas: HTMLCanvasElement | null,
    existing: Chart | null,
    config: any,
): Chart | null {
    if (!canvas) {
        return null;
    }

    if (existing) {
        existing.destroy();
    }

    const ctx = canvas.getContext('2d');

    if (!ctx) {
        return null;
    }

    return new Chart(ctx, config);
}

function renderCharts() {
    nextTick(() => {
        destroyCharts();

        if (bpData.value.labels.length > 0 && bpCanvas.value) {
            const common = {
                responsive: true,
                maintainAspectRatio: true,
                plugins: { legend: { display: false } },
                scales: {
                    x: {
                        ticks: { font: { size: 9 }, maxTicksLimit: 6 },
                        grid: { display: false },
                    },
                    y: {
                        beginAtZero: false,
                        ticks: { font: { size: 9 } },
                        grid: { color: 'rgba(0,0,0,0.05)' },
                    },
                },
            };
            bpChart = buildChart(bpCanvas.value, bpChart, {
                type: 'line',
                data: {
                    labels: bpData.value.labels,
                    datasets: [
                        {
                            label: 'Systolic',
                            data: bpData.value.systolic,
                            borderColor: '#ef4444',
                            backgroundColor: 'rgba(239,68,68,0.1)',
                            fill: false,
                            tension: 0.3,
                            pointRadius: 3,
                            borderWidth: 2,
                        },
                        {
                            label: 'Diastolic',
                            data: bpData.value.diastolic,
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59,130,246,0.1)',
                            fill: false,
                            tension: 0.3,
                            pointRadius: 3,
                            borderWidth: 2,
                        },
                    ],
                },
                options: {
                    ...common,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'bottom',
                            labels: {
                                font: { size: 9 },
                                boxWidth: 10,
                                padding: 8,
                            },
                        },
                    },
                },
            });
        }

        if (hrData.value.labels.length > 0 && hrCanvas.value) {
            hrChart = buildChart(hrCanvas.value, hrChart, {
                type: 'line',
                data: {
                    labels: hrData.value.labels,
                    datasets: [
                        {
                            label: 'HR (bpm)',
                            data: hrData.value.values,
                            borderColor: '#8b5cf6',
                            backgroundColor: 'rgba(139,92,246,0.1)',
                            fill: false,
                            tension: 0.3,
                            pointRadius: 3,
                            borderWidth: 2,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: {
                            ticks: { font: { size: 9 }, maxTicksLimit: 6 },
                            grid: { display: false },
                        },
                        y: {
                            beginAtZero: false,
                            ticks: { font: { size: 9 } },
                            grid: { color: 'rgba(0,0,0,0.05)' },
                        },
                    },
                },
            });
        }

        if (lvefData.value.labels.length > 0 && lvefCanvas.value) {
            lvefChart = buildChart(lvefCanvas.value, lvefChart, {
                type: 'line',
                data: {
                    labels: lvefData.value.labels,
                    datasets: [
                        {
                            label: 'LVEF (%)',
                            data: lvefData.value.values,
                            borderColor: '#059669',
                            backgroundColor: 'rgba(5,150,105,0.1)',
                            fill: false,
                            tension: 0.3,
                            pointRadius: 4,
                            borderWidth: 2,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: {
                            ticks: { font: { size: 9 }, maxTicksLimit: 6 },
                            grid: { display: false },
                        },
                        y: {
                            beginAtZero: false,
                            ticks: { font: { size: 9 } },
                            grid: { color: 'rgba(0,0,0,0.05)' },
                        },
                    },
                },
            });
        }

        if (weightData.value.labels.length > 0 && weightCanvas.value) {
            weightChart = buildChart(weightCanvas.value, weightChart, {
                type: 'line',
                data: {
                    labels: weightData.value.labels,
                    datasets: [
                        {
                            label: 'Weight (kg)',
                            data: weightData.value.values,
                            borderColor: '#f59e0b',
                            backgroundColor: 'rgba(245,158,11,0.1)',
                            fill: false,
                            tension: 0.3,
                            pointRadius: 3,
                            borderWidth: 2,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: {
                            ticks: { font: { size: 9 }, maxTicksLimit: 6 },
                            grid: { display: false },
                        },
                        y: {
                            beginAtZero: false,
                            ticks: { font: { size: 9 } },
                            grid: { color: 'rgba(0,0,0,0.05)' },
                        },
                    },
                },
            });
        }
    });
}

async function fetchAnalytics() {
    loading.value = true;

    try {
        const res = await fetch(
            `/doctor/patients/${props.patientId}/analytics`,
        );

        if (!res.ok) {
            throw new Error('Failed to load analytics');
        }

        data.value = await res.json();
        await nextTick();
        renderCharts();
    } catch (e) {
        console.error('AnalyticsPanel: fetch failed', e);
    } finally {
        loading.value = false;
    }
}

watch(isOpen, (val) => {
    if (val && !data.value.vitals.length && !data.value.echo.length) {
        fetchAnalytics();
    } else if (val) {
        nextTick(() => renderCharts());
    }
});

onMounted(() => {
    if (isOpen.value) {
        fetchAnalytics();
    }
});

onUnmounted(() => {
    destroyCharts();
});
</script>
