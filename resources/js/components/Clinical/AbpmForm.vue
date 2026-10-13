<template>
    <div
        class="animate-fade-in-up mt-4 mb-8 overflow-hidden rounded-xl border-2 border-emerald-600 bg-[#f8fafc] shadow-sm"
    >
        <!-- Header -->
        <div
            class="flex items-center justify-between bg-emerald-600 px-6 py-4 text-white"
        >
            <h3 class="text-base font-black tracking-widest uppercase">
                Ambulatory Blood Pressure Monitoring (ABPM)
            </h3>
            <button
                @click.prevent="exportToWord"
                class="flex items-center rounded bg-white px-4 py-1.5 text-xs font-bold text-emerald-700 shadow-sm transition-colors hover:bg-gray-100"
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
                class="flex items-center rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800"
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
                Type 6 — 24-hour ambulatory BP monitoring report. Fill in the
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
                        v-model="abpmData.age"
                        class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 outline-none focus:border-emerald-600"
                    />
                </div>
                <div>
                    <label
                        class="block text-[10px] font-bold text-gray-500 uppercase"
                        >Sex</label
                    >
                    <input
                        type="text"
                        v-model="abpmData.sex"
                        class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 outline-none focus:border-emerald-600"
                    />
                </div>
                <div>
                    <label
                        class="block text-[10px] font-bold text-gray-500 uppercase"
                        >File No.</label
                    >
                    <input
                        type="text"
                        v-model="abpmData.file_no"
                        class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 outline-none focus:border-emerald-600"
                    />
                </div>
                <div>
                    <label
                        class="block text-[10px] font-bold text-gray-500 uppercase"
                        >Requesting Physician</label
                    >
                    <input
                        type="text"
                        v-model="abpmData.requesting_physician"
                        class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 outline-none focus:border-emerald-600"
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
                    v-model="abpmData.indication"
                    class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 outline-none focus:border-emerald-600"
                />
            </div>

            <!-- Monitor -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div>
                    <label
                        class="block text-[10px] font-bold text-gray-500 uppercase"
                        >Monitor Device</label
                    >
                    <input
                        type="text"
                        v-model="abpmData.monitor_device"
                        class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 outline-none focus:border-emerald-600"
                    />
                </div>
                <div>
                    <label
                        class="block text-[10px] font-bold text-gray-500 uppercase"
                        >Model</label
                    >
                    <input
                        type="text"
                        v-model="abpmData.monitor_model"
                        class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 outline-none focus:border-emerald-600"
                    />
                </div>
                <div>
                    <label
                        class="block text-[10px] font-bold text-gray-500 uppercase"
                        >Cuff Size</label
                    >
                    <input
                        type="text"
                        v-model="abpmData.cuff_size"
                        class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 outline-none focus:border-emerald-600"
                    />
                </div>
            </div>

            <div>
                <label
                    class="block text-[10px] font-bold text-gray-500 uppercase"
                    >Blood Pressure Medication(s)</label
                >
                <input
                    type="text"
                    v-model="abpmData.bp_medications"
                    placeholder="NONE"
                    class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 outline-none focus:border-emerald-600"
                />
            </div>

            <!-- Monitoring window -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-5">
                <div>
                    <label
                        class="block text-[10px] font-bold text-gray-500 uppercase"
                        >Started (date)</label
                    >
                    <input
                        type="text"
                        v-model="abpmData.start_date"
                        class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 outline-none focus:border-emerald-600"
                    />
                </div>
                <div>
                    <label
                        class="block text-[10px] font-bold text-gray-500 uppercase"
                        >Started (time)</label
                    >
                    <input
                        type="text"
                        v-model="abpmData.start_time"
                        class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 outline-none focus:border-emerald-600"
                    />
                </div>
                <div>
                    <label
                        class="block text-[10px] font-bold text-gray-500 uppercase"
                        >Ended (date)</label
                    >
                    <input
                        type="text"
                        v-model="abpmData.end_date"
                        class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 outline-none focus:border-emerald-600"
                    />
                </div>
                <div>
                    <label
                        class="block text-[10px] font-bold text-gray-500 uppercase"
                        >Ended (time)</label
                    >
                    <input
                        type="text"
                        v-model="abpmData.end_time"
                        class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 outline-none focus:border-emerald-600"
                    />
                </div>
                <div>
                    <label
                        class="block text-[10px] font-bold text-gray-500 uppercase"
                        >Total Duration</label
                    >
                    <input
                        type="text"
                        v-model="abpmData.total_duration"
                        class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 outline-none focus:border-emerald-600"
                    />
                </div>
            </div>

            <!-- BP data -->
            <div>
                <h4
                    class="mb-2 text-xs font-extrabold tracking-widest text-emerald-700 uppercase"
                >
                    Blood Pressure Data
                </h4>
                <div class="overflow-x-auto rounded-lg border border-gray-200">
                    <table
                        class="w-full min-w-[560px] border-collapse bg-white text-sm"
                    >
                        <thead>
                            <tr
                                class="bg-emerald-50 text-[10px] font-bold text-emerald-800 uppercase"
                            >
                                <th class="border px-2 py-2 text-left">Period</th>
                                <th class="border px-2 py-2">Mean SBP</th>
                                <th class="border px-2 py-2">Mean DBP</th>
                                <th class="border px-2 py-2">Mean HR</th>
                                <th class="border px-2 py-2">From</th>
                                <th class="border px-2 py-2">To</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td
                                    class="border px-2 py-1.5 font-bold"
                                >
                                    Daytime (Waking)
                                </td>
                                <td class="border px-1 py-1.5">
                                    <input
                                        type="text"
                                        v-model="abpmData.day_sbp"
                                        placeholder="SBP"
                                        class="w-full rounded border border-gray-200 px-1.5 py-1 text-sm outline-none focus:border-emerald-500"
                                    />
                                </td>
                                <td class="border px-1 py-1.5">
                                    <input
                                        type="text"
                                        v-model="abpmData.day_dbp"
                                        placeholder="DBP"
                                        class="w-full rounded border border-gray-200 px-1.5 py-1 text-sm outline-none focus:border-emerald-500"
                                    />
                                </td>
                                <td class="border px-1 py-1.5">
                                    <input
                                        type="text"
                                        v-model="abpmData.day_hr"
                                        placeholder="HR"
                                        class="w-full rounded border border-gray-200 px-1.5 py-1 text-sm outline-none focus:border-emerald-500"
                                    />
                                </td>
                                <td class="border px-1 py-1.5">
                                    <input
                                        type="text"
                                        v-model="abpmData.wake_from"
                                        class="w-full rounded border border-gray-200 px-1.5 py-1 text-sm outline-none focus:border-emerald-500"
                                    />
                                </td>
                                <td class="border px-1 py-1.5">
                                    <input
                                        type="text"
                                        v-model="abpmData.wake_to"
                                        class="w-full rounded border border-gray-200 px-1.5 py-1 text-sm outline-none focus:border-emerald-500"
                                    />
                                </td>
                            </tr>
                            <tr>
                                <td class="border px-2 py-1.5 font-bold">
                                    Nighttime (Sleeping)
                                </td>
                                <td class="border px-1 py-1.5">
                                    <input
                                        type="text"
                                        v-model="abpmData.night_sbp"
                                        placeholder="SBP"
                                        class="w-full rounded border border-gray-200 px-1.5 py-1 text-sm outline-none focus:border-emerald-500"
                                    />
                                </td>
                                <td class="border px-1 py-1.5">
                                    <input
                                        type="text"
                                        v-model="abpmData.night_dbp"
                                        placeholder="DBP"
                                        class="w-full rounded border border-gray-200 px-1.5 py-1 text-sm outline-none focus:border-emerald-500"
                                    />
                                </td>
                                <td class="border px-1 py-1.5">
                                    <input
                                        type="text"
                                        v-model="abpmData.night_hr"
                                        placeholder="HR"
                                        class="w-full rounded border border-gray-200 px-1.5 py-1 text-sm outline-none focus:border-emerald-500"
                                    />
                                </td>
                                <td class="border px-1 py-1.5">
                                    <input
                                        type="text"
                                        v-model="abpmData.sleep_from"
                                        class="w-full rounded border border-gray-200 px-1.5 py-1 text-sm outline-none focus:border-emerald-500"
                                    />
                                </td>
                                <td class="border px-1 py-1.5">
                                    <input
                                        type="text"
                                        v-model="abpmData.sleep_to"
                                        class="w-full rounded border border-gray-200 px-1.5 py-1 text-sm outline-none focus:border-emerald-500"
                                    />
                                </td>
                            </tr>
                            <tr>
                                <td class="border px-2 py-1.5 font-bold">
                                    24-Hour Average
                                </td>
                                <td class="border px-1 py-1.5">
                                    <input
                                        type="text"
                                        v-model="abpmData.avg_sbp"
                                        placeholder="SBP"
                                        class="w-full rounded border border-gray-200 px-1.5 py-1 text-sm outline-none focus:border-emerald-500"
                                    />
                                </td>
                                <td class="border px-1 py-1.5">
                                    <input
                                        type="text"
                                        v-model="abpmData.avg_dbp"
                                        placeholder="DBP"
                                        class="w-full rounded border border-gray-200 px-1.5 py-1 text-sm outline-none focus:border-emerald-500"
                                    />
                                </td>
                                <td class="border px-1 py-1.5">
                                    <input
                                        type="text"
                                        v-model="abpmData.avg_hr"
                                        placeholder="HR"
                                        class="w-full rounded border border-gray-200 px-1.5 py-1 text-sm outline-none focus:border-emerald-500"
                                    />
                                </td>
                                <td class="border px-2 py-1.5" colspan="2"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Evaluation -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <div>
                    <label
                        class="block text-[10px] font-bold text-gray-500 uppercase"
                        >Systolic Load (%)</label
                    >
                    <input
                        type="text"
                        v-model="abpmData.systolic_load"
                        class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 outline-none focus:border-emerald-600"
                    />
                </div>
                <div>
                    <label
                        class="block text-[10px] font-bold text-gray-500 uppercase"
                        >Diastolic Load (%)</label
                    >
                    <input
                        type="text"
                        v-model="abpmData.diastolic_load"
                        class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 outline-none focus:border-emerald-600"
                    />
                </div>
                <div>
                    <label
                        class="block text-[10px] font-bold text-gray-500 uppercase"
                        >Baseline BP</label
                    >
                    <input
                        type="text"
                        v-model="abpmData.baseline_bp"
                        class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 outline-none focus:border-emerald-600"
                    />
                </div>
                <div>
                    <label
                        class="block text-[10px] font-bold text-gray-500 uppercase"
                        >Dipping Status</label
                    >
                    <input
                        type="text"
                        v-model="abpmData.dipping"
                        class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 outline-none focus:border-emerald-600"
                    />
                </div>
            </div>

            <!-- Narrative sections -->
            <div>
                <label
                    class="mb-1 block text-[10px] font-bold text-emerald-700 uppercase"
                    >Analysis / Findings</label
                >
                <textarea
                    v-model="abpmData.findings"
                    placeholder="Narrative summary of the 24-hour readings..."
                    class="h-28 w-full resize-none rounded border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 outline-none focus:border-emerald-600"
                ></textarea>
            </div>
            <div>
                <label
                    class="mb-1 block text-[10px] font-bold text-emerald-700 uppercase"
                    >Interpretation</label
                >
                <textarea
                    v-model="abpmData.interpretation"
                    placeholder="Optional interpretation..."
                    class="h-24 w-full resize-none rounded border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 outline-none focus:border-emerald-600"
                ></textarea>
            </div>
            <div>
                <label
                    class="mb-1 block text-[10px] font-bold text-emerald-700 uppercase"
                    >Recommendations</label
                >
                <textarea
                    v-model="abpmData.recommendations"
                    placeholder="Optional recommendations..."
                    class="h-24 w-full resize-none rounded border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 outline-none focus:border-emerald-600"
                ></textarea>
            </div>
            <div>
                <label
                    class="mb-1 block text-[10px] font-bold text-emerald-700 uppercase"
                    >Conclusion</label
                >
                <textarea
                    v-model="abpmData.conclusion"
                    placeholder="Optional conclusion / impression..."
                    class="h-24 w-full resize-none rounded border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 outline-none focus:border-emerald-600"
                ></textarea>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import type { AbpmData } from '@/types/appointment';
import { buildAbpmDocument, exportAsDocx } from '@/utils/docExport';

const abpmData = defineModel<AbpmData>('abpmData', {
    required: true,
});

const props = defineProps<{
    patientName: string;
}>();

const exportToWord = () => {
    const date = new Date().toLocaleDateString();
    const bodyHtml = buildAbpmDocument(
        abpmData.value,
        props.patientName,
        date,
    );
    exportAsDocx(bodyHtml, `ABPM_${props.patientName.replace(/\s+/g, '_')}`);
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