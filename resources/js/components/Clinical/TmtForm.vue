<template>
    <div
        class="animate-fade-in-up mt-4 mb-8 overflow-hidden rounded-xl border-2 border-[#1e8996] bg-[#f8fafc] shadow-sm"
    >
        <!-- Header -->
        <div
            class="flex items-center justify-between bg-[#1e8996] px-6 py-4 text-white"
        >
            <h3 class="text-base font-black tracking-widest uppercase">
                Treadmill Exercise Test Report
            </h3>
            <button
                @click.prevent="exportToWord"
                class="flex items-center rounded bg-white px-4 py-1.5 text-xs font-bold text-[#1e8996] shadow-sm transition-colors hover:bg-gray-100"
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
                Export TMT to Docs
            </button>
        </div>

        <div class="space-y-6 p-6">
            <!-- SECTION 1: CONSENT DATA -->
            <div
                class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm"
            >
                <h4
                    class="mb-4 border-b border-gray-100 pb-2 text-xs font-black tracking-widest text-[#1e8996] uppercase"
                >
                    Page 1: Informed Consent Meta
                </h4>
                <div class="grid grid-cols-2 gap-4 text-xs md:grid-cols-4">
                    <div>
                        <label class="block font-bold text-gray-500 uppercase"
                            >Age</label
                        ><input
                            type="text"
                            v-model="tmtData.age"
                            class="w-full rounded border border-gray-300 bg-white px-2 py-1.5 text-gray-900"
                        />
                    </div>
                    <div>
                        <label class="block font-bold text-gray-500 uppercase"
                            >Height (cm)</label
                        ><input
                            type="text"
                            v-model="tmtData.height"
                            class="w-full rounded border border-gray-300 bg-white px-2 py-1.5 text-gray-900"
                        />
                    </div>
                    <div>
                        <label class="block font-bold text-gray-500 uppercase"
                            >Weight (kg)</label
                        ><input
                            type="text"
                            v-model="tmtData.weight"
                            class="w-full rounded border border-gray-300 bg-white px-2 py-1.5 text-gray-900"
                        />
                    </div>
                    <div>
                        <label class="block font-bold text-gray-500 uppercase"
                            >Cell No</label
                        ><input
                            type="text"
                            v-model="tmtData.cell_no"
                            class="w-full rounded border border-gray-300 bg-white px-2 py-1.5 text-gray-900"
                        />
                    </div>
                    <div>
                        <label class="block font-bold text-gray-500 uppercase"
                            >Birthday</label
                        ><input
                            type="text"
                            v-model="tmtData.birthday"
                            class="w-full rounded border border-gray-300 bg-white px-2 py-1.5 text-gray-900"
                        />
                    </div>
                    <div>
                        <label class="block font-bold text-gray-500 uppercase"
                            >PMHR</label
                        ><input
                            type="text"
                            v-model="tmtData.pmhr"
                            class="w-full rounded border border-gray-300 bg-white px-2 py-1.5 text-gray-900"
                        />
                    </div>
                    <div>
                        <label class="block font-bold text-gray-500 uppercase"
                            >BP</label
                        ><input
                            type="text"
                            v-model="tmtData.bp"
                            class="w-full rounded border border-gray-300 bg-white px-2 py-1.5 text-gray-900"
                        />
                    </div>
                </div>
                <div class="mt-4 grid grid-cols-1 gap-4 text-xs md:grid-cols-3">
                    <div class="md:col-span-3">
                        <label class="block font-bold text-gray-500 uppercase"
                            >Medicines</label
                        ><input
                            type="text"
                            v-model="tmtData.medicines"
                            class="w-full rounded border border-gray-300 bg-white px-2 py-1.5 text-gray-900"
                        />
                    </div>
                    <div>
                        <label class="block font-bold text-gray-500 uppercase"
                            >Requesting MD</label
                        ><input
                            type="text"
                            v-model="tmtData.requesting_physician"
                            class="w-full rounded border border-gray-300 bg-white px-2 py-1.5 text-gray-900"
                        />
                    </div>
                    <div>
                        <label class="block font-bold text-gray-500 uppercase"
                            >Attending MD</label
                        ><input
                            type="text"
                            v-model="tmtData.attending_physician"
                            class="w-full rounded border border-gray-300 bg-white px-2 py-1.5 text-gray-900"
                        />
                    </div>
                    <div>
                        <label class="block font-bold text-gray-500 uppercase"
                            >Consent To</label
                        ><input
                            type="text"
                            v-model="tmtData.consent_to"
                            class="w-full rounded border border-gray-300 bg-white px-2 py-1.5 text-gray-900"
                        />
                    </div>
                    <div class="md:col-span-3">
                        <label class="block font-bold text-gray-500 uppercase"
                            >Patient Residence</label
                        ><input
                            type="text"
                            v-model="tmtData.residence"
                            class="w-full rounded border border-gray-300 bg-white px-2 py-1.5 text-gray-900"
                        />
                    </div>
                </div>
            </div>

            <!-- SECTION 2: PROTOCOL STAGES TABLE -->
            <div
                class="overflow-x-auto rounded-lg border border-gray-200 bg-white p-5 shadow-sm"
            >
                <h4
                    class="mb-4 border-b border-gray-100 pb-2 text-xs font-black tracking-widest text-[#1e8996] uppercase"
                >
                    Page 2: Exercise Testing Stages
                </h4>
                <div class="mb-4 flex gap-4 text-xs">
                    <div class="flex-1">
                        <label class="block font-bold text-gray-500 uppercase"
                            >Protocol</label
                        ><input
                            type="text"
                            v-model="tmtData.protocol"
                            class="w-full rounded border border-gray-300 bg-white px-2 py-1.5 text-gray-900"
                        />
                    </div>
                    <div class="flex-1">
                        <label class="block font-bold text-gray-500 uppercase"
                            >Indication</label
                        ><input
                            type="text"
                            v-model="tmtData.indication"
                            class="w-full rounded border border-gray-300 bg-white px-2 py-1.5 text-gray-900"
                        />
                    </div>
                </div>
                <table
                    class="min-w-full border border-gray-200 text-center text-xs"
                >
                    <thead class="bg-gray-100 font-bold text-gray-600">
                        <tr>
                            <th class="border p-2">Event</th>
                            <th class="border p-2">Speed (mph)</th>
                            <th class="border p-2">Grade (%)</th>
                            <th class="border p-2">METS</th>
                            <th class="border p-2">BP</th>
                            <th class="border p-2">HR</th>
                            <th class="border p-2">Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(stage, idx) in tmtData.stages" :key="idx">
                            <td
                                class="border bg-gray-50 p-2 font-bold text-gray-700"
                            >
                                {{ stage.name }}
                            </td>
                            <td class="border p-1">
                                <input
                                    type="text"
                                    v-model="stage.speed"
                                    class="w-12 border-none bg-transparent text-center text-gray-900 outline-none"
                                />
                            </td>
                            <td class="border p-1">
                                <input
                                    type="text"
                                    v-model="stage.grade"
                                    class="w-12 border-none bg-transparent text-center text-gray-900 outline-none"
                                />
                            </td>
                            <td class="border p-1">
                                <input
                                    type="text"
                                    v-model="stage.mets"
                                    class="w-12 border-none bg-transparent text-center text-gray-900 outline-none"
                                />
                            </td>
                            <td class="border p-1">
                                <input
                                    type="text"
                                    v-model="stage.bp"
                                    class="w-full rounded border border-gray-200 bg-white px-1 py-1 text-gray-900"
                                />
                            </td>
                            <td class="border p-1">
                                <input
                                    type="text"
                                    v-model="stage.hr"
                                    class="w-full rounded border border-gray-200 bg-white px-1 py-1 text-gray-900"
                                />
                            </td>
                            <td class="border p-1">
                                <input
                                    type="text"
                                    v-model="stage.remarks"
                                    class="w-full rounded border border-gray-200 bg-white px-1 py-1 text-gray-900"
                                />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- SECTION 3: RESULTS & INTERPRETATION -->
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div
                    class="space-y-4 rounded-lg border border-gray-200 bg-white p-5 shadow-sm"
                >
                    <h4
                        class="border-b border-gray-100 pb-2 text-xs font-black tracking-widest text-[#1e8996] uppercase"
                    >
                        Peak & ST Segment
                    </h4>
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <div>
                            <label class="block font-bold text-gray-500"
                                >Peak Stage</label
                            ><input
                                type="text"
                                v-model="tmtData.peak_stage"
                                class="w-full rounded border border-gray-300 bg-white px-2 py-1.5 text-gray-900"
                            />
                        </div>
                        <div>
                            <label class="block font-bold text-gray-500"
                                >Peak Minutes</label
                            ><input
                                type="text"
                                v-model="tmtData.peak_minutes"
                                class="w-full rounded border border-gray-300 bg-white px-2 py-1.5 text-gray-900"
                            />
                        </div>
                        <div>
                            <label class="block font-bold text-gray-500"
                                >Peak SBP</label
                            ><input
                                type="text"
                                v-model="tmtData.peak_sbp"
                                class="w-full rounded border border-gray-300 bg-white px-2 py-1.5 text-gray-900"
                            />
                        </div>
                        <div>
                            <label class="block font-bold text-gray-500"
                                >Peak HR</label
                            ><input
                                type="text"
                                v-model="tmtData.peak_hr"
                                class="w-full rounded border border-gray-300 bg-white px-2 py-1.5 text-gray-900"
                            />
                        </div>
                        <div>
                            <label class="block font-bold text-gray-500"
                                >Peak METS</label
                            ><input
                                type="text"
                                v-model="tmtData.peak_mets"
                                class="w-full rounded border border-gray-300 bg-white px-2 py-1.5 text-gray-900"
                            />
                        </div>
                        <div>
                            <label class="block font-bold text-gray-500"
                                >Termination Reason</label
                            ><input
                                type="text"
                                v-model="tmtData.termination_reason"
                                class="w-full rounded border border-gray-300 bg-white px-2 py-1.5 text-gray-900"
                            />
                        </div>
                        <div class="col-span-2 mt-2 border-t pt-2">
                            <label class="block font-bold text-gray-500"
                                >ST Shape</label
                            ><input
                                type="text"
                                v-model="tmtData.st_shape"
                                placeholder="Upsloping/Downsloping/Horizontal..."
                                class="w-full rounded border border-gray-300 bg-white px-2 py-1.5 text-gray-900"
                            />
                        </div>
                        <div>
                            <label class="block font-bold text-gray-500"
                                >ST Disp. (mm)</label
                            ><input
                                type="text"
                                v-model="tmtData.st_displacement"
                                class="w-full rounded border border-gray-300 bg-white px-2 py-1.5 text-gray-900"
                            />
                        </div>
                        <div>
                            <label class="block font-bold text-gray-500"
                                >ST Dur. (min)</label
                            ><input
                                type="text"
                                v-model="tmtData.st_duration"
                                class="w-full rounded border border-gray-300 bg-white px-2 py-1.5 text-gray-900"
                            />
                        </div>
                    </div>
                </div>
                <div
                    class="space-y-4 rounded-lg border border-gray-200 bg-white p-5 text-xs shadow-sm"
                >
                    <h4
                        class="border-b border-gray-100 pb-2 text-xs font-black tracking-widest text-[#1e8996] uppercase"
                    >
                        Interpretation
                    </h4>
                    <div>
                        <label class="block font-bold text-gray-500"
                            >A. Resting ECG</label
                        ><input
                            type="text"
                            v-model="tmtData.resting_ecg"
                            class="w-full rounded border border-gray-300 bg-white px-2 py-1 text-gray-900"
                        />
                    </div>
                    <div>
                        <label class="block font-bold text-gray-500"
                            >B. Response to Exercise</label
                        ><input
                            type="text"
                            v-model="tmtData.response_to_exercise"
                            class="w-full rounded border border-gray-300 bg-white px-2 py-1 text-gray-900"
                        />
                    </div>
                    <div>
                        <label class="block font-bold text-gray-500"
                            >C. Exercise Capacity</label
                        ><input
                            type="text"
                            v-model="tmtData.exercise_capacity"
                            class="w-full rounded border border-gray-300 bg-white px-2 py-1 text-gray-900"
                        />
                    </div>
                    <div>
                        <label class="block font-bold text-gray-500"
                            >D. Arrhythmias</label
                        ><input
                            type="text"
                            v-model="tmtData.arrhythmias"
                            class="w-full rounded border border-gray-300 bg-white px-2 py-1 text-gray-900"
                        />
                    </div>
                    <div>
                        <label class="block font-bold text-gray-500"
                            >E. Probability of CAD</label
                        ><input
                            type="text"
                            v-model="tmtData.probability_cad"
                            class="w-full rounded border border-gray-300 bg-white px-2 py-1 text-gray-900"
                        />
                    </div>
                    <div class="grid grid-cols-2 gap-2 border-t pt-3">
                        <div class="flex items-center gap-2">
                            <label class="font-bold text-gray-500"
                                >PRE EF:</label
                            ><input
                                type="text"
                                v-model="tmtData.pre_ef"
                                class="w-full rounded border border-gray-300 bg-white px-2 py-1 text-gray-900"
                            />
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="font-bold text-gray-500"
                                >POST EF:</label
                            ><input
                                type="text"
                                v-model="tmtData.post_ef"
                                class="w-full rounded border border-gray-300 bg-white px-2 py-1 text-gray-900"
                            />
                        </div>
                    </div>
                    <div>
                        <label
                            class="mt-2 mb-1 block font-bold tracking-widest text-[#1e8996] uppercase"
                            >Conclusion</label
                        >
                        <p
                            v-if="restricted"
                            class="mb-1 text-[10px] font-bold text-amber-600 uppercase"
                        >
                            Doctor only — the physician completes this section.
                        </p>
                        <textarea
                            v-model="tmtData.conclusion"
                            :disabled="restricted"
                            class="h-16 w-full resize-none rounded border border-gray-300 bg-white px-2 py-1.5 text-gray-900 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500"
                        ></textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import type { TMTData } from '@/types/appointment';
import { buildDocument, buildTmtDocument } from '@/utils/docExport';

const tmtData = defineModel<TMTData>('tmtData', { required: true });
const props = withDefaults(
    defineProps<{
        echoSex: string;
        patientName: string;
        restricted?: boolean;
    }>(),
    { restricted: false },
);

const exportToWord = () => {
    const date = new Date().toLocaleDateString();
    const pagesHtml = buildTmtDocument(
        tmtData.value,
        props.patientName,
        props.echoSex,
        date,
    );
    const blob = new Blob(
        ['\ufeff', buildDocument('Treadmill Exercise Test', pagesHtml)],
        { type: 'application/msword' },
    );
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `TMT_Report_${props.patientName.replace(/\s+/g, '_')}.doc`;
    link.click();
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
