<template>
    <div
        class="animate-fade-in-up mt-4 mb-8 overflow-hidden rounded-xl border-2 border-rose-600 bg-[#f8fafc] shadow-sm"
    >
        <!-- Header -->
        <div
            class="flex items-center justify-between bg-rose-600 px-6 py-4 text-white"
        >
            <h3 class="text-base font-black tracking-widest uppercase">
                Stress Echocardiogram
            </h3>
            <button
                @click.prevent="exportToWord"
                class="flex items-center rounded bg-white px-4 py-1.5 text-xs font-bold text-rose-700 shadow-sm transition-colors hover:bg-gray-100"
            >
                <svg
                    class="mr-2 h-4 w-4"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                    />
                </svg>
                Export to Docs
            </button>
        </div>

        <div class="space-y-5 p-6">
            <!-- Info banner -->
            <div
                class="flex items-center rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-800"
            >
                <svg
                    class="mr-2 h-5 w-5 shrink-0"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                    />
                </svg>
                Type 5 — exercise stress test with echocardiography. Fill in the
                blanks below; the PDF export prints them on the form.
            </div>

            <!-- Header Info -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <div>
                    <label
                        class="block text-[10px] font-bold text-gray-500 uppercase"
                        >Age</label
                    >
                    <input
                        type="text"
                        v-model="stressEchoData.age"
                        class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 outline-none focus:border-rose-600"
                    />
                </div>
                <div>
                    <label
                        class="block text-[10px] font-bold text-gray-500 uppercase"
                        >Sex</label
                    >
                    <input
                        type="text"
                        v-model="stressEchoData.sex"
                        class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 outline-none focus:border-rose-600"
                    />
                </div>
                <div>
                    <label
                        class="block text-[10px] font-bold text-gray-500 uppercase"
                        >File No.</label
                    >
                    <input
                        type="text"
                        v-model="stressEchoData.file_no"
                        class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 outline-none focus:border-rose-600"
                    />
                </div>
                <div>
                    <label
                        class="block text-[10px] font-bold text-gray-500 uppercase"
                        >Requesting Physician</label
                    >
                    <input
                        type="text"
                        v-model="stressEchoData.requesting_physician"
                        class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 outline-none focus:border-rose-600"
                    />
                </div>
            </div>

            <div>
                <label
                    class="block text-[10px] font-bold text-gray-500 uppercase"
                    >Indication</label
                >
                <input
                    type="text"
                    v-model="stressEchoData.indication"
                    class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 outline-none focus:border-rose-600"
                />
            </div>

            <div>
                <label
                    class="block text-[10px] font-bold text-gray-500 uppercase"
                    >Medications</label
                >
                <input
                    type="text"
                    v-model="stressEchoData.medications"
                    placeholder="NONE"
                    class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 outline-none focus:border-rose-600"
                />
            </div>

            <!-- Pre-test -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <div>
                    <label
                        class="block text-[10px] font-bold text-gray-500 uppercase"
                        >Pre-Test BP</label
                    >
                    <input
                        type="text"
                        v-model="stressEchoData.pre_bp"
                        placeholder="e.g. 120/80"
                        class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 outline-none focus:border-rose-600"
                    />
                </div>
                <div>
                    <label
                        class="block text-[10px] font-bold text-gray-500 uppercase"
                        >Pre-Test HR</label
                    >
                    <input
                        type="text"
                        v-model="stressEchoData.pre_hr"
                        class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 outline-none focus:border-rose-600"
                    />
                </div>
                <div>
                    <label
                        class="block text-[10px] font-bold text-gray-500 uppercase"
                        >Resting ECG</label
                    >
                    <input
                        type="text"
                        v-model="stressEchoData.pre_ecg"
                        class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 outline-none focus:border-rose-600"
                    />
                </div>
                <div>
                    <label
                        class="block text-[10px] font-bold text-gray-500 uppercase"
                        >Pre-Test Symptoms</label
                    >
                    <input
                        type="text"
                        v-model="stressEchoData.pre_symptoms"
                        class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 outline-none focus:border-rose-600"
                    />
                </div>
            </div>

            <div>
                <label
                    class="block text-[10px] font-bold text-gray-500 uppercase"
                    >Exercise Protocol</label
                >
                <input
                    type="text"
                    v-model="stressEchoData.protocol"
                    placeholder="e.g. BRUCE"
                    class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 outline-none focus:border-rose-600"
                />
            </div>

            <!-- Exercise stages -->
            <div>
                <div class="flex items-center justify-between">
                    <h4
                        class="mb-2 text-xs font-extrabold tracking-widest text-rose-700 uppercase"
                    >
                        Exercise Stages
                    </h4>
                    <button
                        type="button"
                        @click.prevent="addStage"
                        class="mb-2 rounded border border-rose-300 bg-rose-50 px-3 py-1 text-xs font-bold text-rose-700 hover:bg-rose-100"
                    >
                        + Add Stage
                    </button>
                </div>
                <div
                    v-if="stressEchoData.stages.length"
                    class="overflow-x-auto rounded-lg border border-gray-200"
                >
                    <table
                        class="w-full min-w-[760px] border-collapse bg-white text-sm"
                    >
                        <thead>
                            <tr
                                class="bg-rose-50 text-[10px] font-bold text-rose-800 uppercase"
                            >
                                <th class="border px-2 py-2 text-left">Stage</th>
                                <th class="border px-2 py-2">Time (min)</th>
                                <th class="border px-2 py-2">Speed</th>
                                <th class="border px-2 py-2">Grade (%)</th>
                                <th class="border px-2 py-2">HR</th>
                                <th class="border px-2 py-2">BP</th>
                                <th class="border px-2 py-2">ECG Changes</th>
                                <th class="border px-2 py-2">Symptoms</th>
                                <th class="border px-2 py-2"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(stage, index) in stressEchoData.stages"
                                :key="index"
                            >
                                <td class="border px-1 py-1">
                                    <input
                                        type="text"
                                        v-model="stage.stage"
                                        placeholder="STAGE 1"
                                        class="w-full rounded border border-gray-200 px-1.5 py-1 text-sm outline-none focus:border-rose-500"
                                    />
                                </td>
                                <td class="border px-1 py-1">
                                    <input
                                        type="text"
                                        v-model="stage.time"
                                        class="w-full rounded border border-gray-200 px-1.5 py-1 text-sm outline-none focus:border-rose-500"
                                    />
                                </td>
                                <td class="border px-1 py-1">
                                    <input
                                        type="text"
                                        v-model="stage.speed"
                                        class="w-full rounded border border-gray-200 px-1.5 py-1 text-sm outline-none focus:border-rose-500"
                                    />
                                </td>
                                <td class="border px-1 py-1">
                                    <input
                                        type="text"
                                        v-model="stage.grade"
                                        class="w-full rounded border border-gray-200 px-1.5 py-1 text-sm outline-none focus:border-rose-500"
                                    />
                                </td>
                                <td class="border px-1 py-1">
                                    <input
                                        type="text"
                                        v-model="stage.hr"
                                        class="w-full rounded border border-gray-200 px-1.5 py-1 text-sm outline-none focus:border-rose-500"
                                    />
                                </td>
                                <td class="border px-1 py-1">
                                    <input
                                        type="text"
                                        v-model="stage.bp"
                                        class="w-full rounded border border-gray-200 px-1.5 py-1 text-sm outline-none focus:border-rose-500"
                                    />
                                </td>
                                <td class="border px-1 py-1">
                                    <input
                                        type="text"
                                        v-model="stage.ecg"
                                        class="w-full rounded border border-gray-200 px-1.5 py-1 text-sm outline-none focus:border-rose-500"
                                    />
                                </td>
                                <td class="border px-1 py-1">
                                    <input
                                        type="text"
                                        v-model="stage.symptoms"
                                        class="w-full rounded border border-gray-200 px-1.5 py-1 text-sm outline-none focus:border-rose-500"
                                    />
                                </td>
                                <td class="border px-1 py-1 text-center">
                                    <button
                                        type="button"
                                        @click.prevent="removeStage(index)"
                                        title="Remove stage"
                                        class="rounded p-1 text-red-400 hover:bg-red-50 hover:text-red-600"
                                    >
                                        <svg
                                            class="h-4 w-4"
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
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p
                    v-else
                    class="rounded-lg border border-dashed border-rose-300 bg-rose-50/50 p-4 text-center text-[11px] text-gray-500"
                >
                    No stages yet. Click “+ Add Stage” to build the protocol grid.
                </p>
            </div>

            <!-- Peak data -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-5">
                <div>
                    <label
                        class="block text-[10px] font-bold text-gray-500 uppercase"
                        >Max HR</label
                    >
                    <input
                        type="text"
                        v-model="stressEchoData.peak_hr"
                        class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 outline-none focus:border-rose-600"
                    />
                </div>
                <div>
                    <label
                        class="block text-[10px] font-bold text-gray-500 uppercase"
                        >Target HR</label
                    >
                    <input
                        type="text"
                        v-model="stressEchoData.target_hr"
                        class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 outline-none focus:border-rose-600"
                    />
                </div>
                <div>
                    <label
                        class="block text-[10px] font-bold text-gray-500 uppercase"
                        >Peak BP</label
                    >
                    <input
                        type="text"
                        v-model="stressEchoData.peak_bp"
                        class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 outline-none focus:border-rose-600"
                    />
                </div>
                <div>
                    <label
                        class="block text-[10px] font-bold text-gray-500 uppercase"
                        >METs</label
                    >
                    <input
                        type="text"
                        v-model="stressEchoData.mets"
                        class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 outline-none focus:border-rose-600"
                    />
                </div>
                <div>
                    <label
                        class="block text-[10px] font-bold text-gray-500 uppercase"
                        >Reached Target</label
                    >
                    <input
                        type="text"
                        v-model="stressEchoData.reached_target"
                        placeholder="Yes / No"
                        class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 outline-none focus:border-rose-600"
                    />
                </div>
            </div>

            <!-- Echo findings -->
            <div>
                <h4
                    class="mb-2 text-xs font-extrabold tracking-widest text-rose-700 uppercase"
                >
                    Echocardiographic Findings
                </h4>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                    <div>
                        <label
                            class="block text-[10px] font-bold text-gray-500 uppercase"
                            >Rest EF (%)</label
                        >
                        <input
                            type="text"
                            v-model="stressEchoData.rest_ef"
                            class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 outline-none focus:border-rose-600"
                        />
                    </div>
                    <div>
                        <label
                            class="block text-[10px] font-bold text-gray-500 uppercase"
                            >Rest Wall Motion</label
                        >
                        <input
                            type="text"
                            v-model="stressEchoData.rest_wma"
                            class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 outline-none focus:border-rose-600"
                        />
                    </div>
                    <div>
                        <label
                            class="block text-[10px] font-bold text-gray-500 uppercase"
                            >Peak EF (%)</label
                        >
                        <input
                            type="text"
                            v-model="stressEchoData.peak_ef"
                            class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 outline-none focus:border-rose-600"
                        />
                    </div>
                    <div>
                        <label
                            class="block text-[10px] font-bold text-gray-500 uppercase"
                            >Peak Wall Motion</label
                        >
                        <input
                            type="text"
                            v-model="stressEchoData.peak_wma"
                            class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 outline-none focus:border-rose-600"
                        />
                    </div>
                </div>
                <div class="mt-3">
                    <label
                        class="block text-[10px] font-bold text-gray-500 uppercase"
                        >Ischaemia / Scar / Viability</label
                    >
                    <input
                        type="text"
                        v-model="stressEchoData.ischaemia"
                        class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 outline-none focus:border-rose-600"
                    />
                </div>
            </div>

            <!-- Narrative sections -->
            <div>
                <label
                    class="mb-1 block text-[10px] font-bold text-rose-700 uppercase"
                    >Findings / Interpretation</label
                >
                <textarea
                    v-model="stressEchoData.findings"
                    placeholder="Narrative findings and interpretation..."
                    class="h-28 w-full resize-none rounded border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 outline-none focus:border-rose-600"
                ></textarea>
            </div>
            <div>
                <label
                    class="mb-1 block text-[10px] font-bold text-rose-700 uppercase"
                    >Conclusion</label
                >
                <textarea
                    v-model="stressEchoData.conclusion"
                    placeholder="Optional conclusion / impression..."
                    class="h-24 w-full resize-none rounded border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 outline-none focus:border-rose-600"
                ></textarea>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import type { StressEchoData, StressEchoStage } from '@/types/appointment';
import { buildStressEchoDocument, exportAsDocx } from '@/utils/docExport';

const stressEchoData = defineModel<StressEchoData>('stressEchoData', {
    required: true,
});

const props = defineProps<{
    patientName: string;
}>();

function addStage(): void {
    stressEchoData.value.stages.push({
        stage: '',
        time: '',
        speed: '',
        grade: '',
        hr: '',
        bp: '',
        ecg: '',
        symptoms: '',
    } as StressEchoStage);
}

function removeStage(index: number): void {
    stressEchoData.value.stages.splice(index, 1);
}

const exportToWord = () => {
    const date = new Date().toLocaleDateString();
    const bodyHtml = buildStressEchoDocument(
        stressEchoData.value,
        props.patientName,
        date,
    );
    exportAsDocx(
        bodyHtml,
        `Stress_Echo_${props.patientName.replace(/\s+/g, '_')}`,
    );
};
</script>

<style scoped>
input,
select,
textarea {
    color: #1e293b !important;
    background-color: #ffffff !important;
}
label {
    color: #1e293b !important;
}
</style>