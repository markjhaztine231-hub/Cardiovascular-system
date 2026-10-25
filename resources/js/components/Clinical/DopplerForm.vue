<template>
    <div
        class="animate-fade-in-up mt-4 mb-8 overflow-hidden rounded-xl border-2 border-indigo-600 bg-[#f8fafc] shadow-sm"
    >
        <!-- Header -->
        <div
            class="flex items-center justify-between bg-indigo-600 px-6 py-4 text-white"
        >
            <h3 class="text-base font-black tracking-widest uppercase">
                Color Flow & Doppler Study (Page 1)
            </h3>
            <button
                @click.prevent="exportToWord"
                class="flex items-center rounded bg-white px-4 py-1.5 text-xs font-bold text-indigo-700 shadow-sm transition-colors hover:bg-gray-100"
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
                Export P1 & P2 to Docs
            </button>
        </div>

        <div class="space-y-6 p-6">
            <!-- Info banner: Type 3 uses Echo on Page 2 -->
            <div
                class="flex items-center rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm font-semibold text-blue-800"
            >
                <svg
                    class="mr-2 h-5 w-5"
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
                Type 3 includes the Doppler Study (Page 1) below, AND the
                standard Echocardiographic Report (Page 2). Please fill out the
                Echo fields (Type 1) as well!
            </div>

            <!-- Doppler Table Grid -->
            <div
                class="rounded-lg border border-gray-200 bg-white p-5 text-xs shadow-sm"
            >
                <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
                    <!-- LEFT SIDE DOPPLER -->
                    <div class="space-y-4">
                        <!-- Mitral Valve -->
                        <div>
                            <h4
                                class="mb-2 border-b pb-1 font-bold text-indigo-700 uppercase"
                            >
                                Mitral Valve
                            </h4>
                            <div class="grid grid-cols-2 items-center gap-2">
                                <label>Peak Vel E (cm/s):</label
                                ><input
                                    type="text"
                                    v-model="dopplerData.mv_peak_vel_e"
                                    class="rounded border border-gray-300 bg-white px-2 py-1 text-gray-900"
                                />
                                <label>Peak Vel A (cm/s):</label
                                ><input
                                    type="text"
                                    v-model="dopplerData.mv_peak_vel_a"
                                    class="rounded border border-gray-300 bg-white px-2 py-1 text-gray-900"
                                />
                                <label>Peak Grad E (mmHg):</label
                                ><input
                                    type="text"
                                    v-model="dopplerData.mv_peak_grad_e"
                                    class="rounded border border-gray-300 bg-white px-2 py-1 text-gray-900"
                                />
                                <label>Peak Grad A (mmHg):</label
                                ><input
                                    type="text"
                                    v-model="dopplerData.mv_peak_grad_a"
                                    class="rounded border border-gray-300 bg-white px-2 py-1 text-gray-900"
                                />
                                <label>IVRT (60-90 sec):</label
                                ><input
                                    type="text"
                                    v-model="dopplerData.mv_ivrt"
                                    class="rounded border border-gray-300 bg-white px-2 py-1 text-gray-900"
                                />
                                <label>Regurgitant Fraction (&lt;30%):</label
                                ><input
                                    type="text"
                                    v-model="dopplerData.mv_regurg_frac"
                                    class="rounded border border-gray-300 bg-white px-2 py-1 text-gray-900"
                                />
                                <label>MV DecTime (162-230):</label
                                ><input
                                    type="text"
                                    v-model="dopplerData.mv_dectime"
                                    class="rounded border border-gray-300 bg-white px-2 py-1 text-gray-900"
                                />
                                <label>E/A ratio:</label
                                ><input
                                    type="text"
                                    v-model="dopplerData.mv_ea_ratio"
                                    class="rounded border border-gray-300 bg-white px-2 py-1 text-gray-900"
                                />
                                <label>Area by PHT (&gt;2.0):</label
                                ><input
                                    type="text"
                                    v-model="dopplerData.mv_area_pht"
                                    class="rounded border border-gray-300 bg-white px-2 py-1 text-gray-900"
                                />
                                <label>Mean press grad (&lt;5):</label
                                ><input
                                    type="text"
                                    v-model="dopplerData.mv_mean_pressure"
                                    class="rounded border border-gray-300 bg-white px-2 py-1 text-gray-900"
                                />
                            </div>
                        </div>

                        <!-- Tissue Doppler -->
                        <div>
                            <h4
                                class="mb-2 border-b pb-1 font-bold text-indigo-700 uppercase"
                            >
                                Tissue Doppler (cm/s)
                            </h4>
                            <div
                                class="grid grid-cols-4 items-center gap-2 text-center"
                            >
                                <span class="text-left font-medium"
                                    >Septal</span
                                >
                                <input
                                    type="text"
                                    v-model="dopplerData.td_septal_e"
                                    placeholder="e'"
                                    class="w-full rounded border border-gray-300 bg-white px-2 py-1 text-gray-900"
                                />
                                <input
                                    type="text"
                                    v-model="dopplerData.td_septal_a"
                                    placeholder="a'"
                                    class="w-full rounded border border-gray-300 bg-white px-2 py-1 text-gray-900"
                                />
                                <input
                                    type="text"
                                    v-model="dopplerData.td_septal_ee"
                                    placeholder="E/e'"
                                    class="w-full rounded border border-gray-300 bg-white px-2 py-1 text-gray-900"
                                />
                                <span class="text-left font-medium"
                                    >Lateral</span
                                >
                                <input
                                    type="text"
                                    v-model="dopplerData.td_lateral_e"
                                    placeholder="e'"
                                    class="w-full rounded border border-gray-300 bg-white px-2 py-1 text-gray-900"
                                />
                                <input
                                    type="text"
                                    v-model="dopplerData.td_lateral_a"
                                    placeholder="a'"
                                    class="w-full rounded border border-gray-300 bg-white px-2 py-1 text-gray-900"
                                />
                                <input
                                    type="text"
                                    v-model="dopplerData.td_lateral_ee"
                                    placeholder="E/e'"
                                    class="w-full rounded border border-gray-300 bg-white px-2 py-1 text-gray-900"
                                />
                            </div>
                        </div>

                        <!-- Tricuspid Valve -->
                        <div>
                            <h4
                                class="mb-2 border-b pb-1 font-bold text-indigo-700 uppercase"
                            >
                                Tricuspid Valve
                            </h4>
                            <div class="grid grid-cols-2 items-center gap-2">
                                <label>E wave vel (cm/s 0.64):</label
                                ><input
                                    type="text"
                                    v-model="dopplerData.tv_e_wave"
                                    class="rounded border border-gray-300 bg-white px-2 py-1 text-gray-900"
                                />
                                <label>A wave vel (cm/s 1.65):</label
                                ><input
                                    type="text"
                                    v-model="dopplerData.tv_a_wave"
                                    class="rounded border border-gray-300 bg-white px-2 py-1 text-gray-900"
                                />
                                <label>Regurgitant Fraction (%):</label
                                ><input
                                    type="text"
                                    v-model="dopplerData.tv_regurg_frac"
                                    class="rounded border border-gray-300 bg-white px-2 py-1 text-gray-900"
                                />
                                <label>Peak TR jet vel (cm/s):</label
                                ><input
                                    type="text"
                                    v-model="dopplerData.tv_peak_tr"
                                    class="rounded border border-gray-300 bg-white px-2 py-1 text-gray-900"
                                />
                                <label>Sys PAP by TR (&gt;25):</label
                                ><input
                                    type="text"
                                    v-model="dopplerData.tv_sys_pap"
                                    class="rounded border border-gray-300 bg-white px-2 py-1 text-gray-900"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT SIDE DOPPLER -->
                    <div class="space-y-4">
                        <!-- Aortic Valve -->
                        <div>
                            <h4
                                class="mb-2 border-b pb-1 font-bold text-indigo-700 uppercase"
                            >
                                Aortic Valve
                            </h4>
                            <div
                                class="grid grid-cols-3 items-center gap-2 text-center"
                            >
                                <span class="text-left"></span
                                ><span class="font-bold">BEFORE</span
                                ><span class="font-bold">AFTER</span>
                                <span class="text-left font-medium"
                                    >Peak vel (cm/s)</span
                                >
                                <input
                                    type="text"
                                    v-model="dopplerData.av_peak_vel_before"
                                    class="rounded border border-gray-300 bg-white px-2 py-1 text-gray-900"
                                />
                                <input
                                    type="text"
                                    v-model="dopplerData.av_peak_vel_after"
                                    class="rounded border border-gray-300 bg-white px-2 py-1 text-gray-900"
                                />
                                <span class="text-left font-medium"
                                    >Press Grad (mmHg)</span
                                >
                                <input
                                    type="text"
                                    v-model="dopplerData.av_press_grad_before"
                                    class="rounded border border-gray-300 bg-white px-2 py-1 text-gray-900"
                                />
                                <input
                                    type="text"
                                    v-model="dopplerData.av_press_grad_after"
                                    class="rounded border border-gray-300 bg-white px-2 py-1 text-gray-900"
                                />
                            </div>
                            <div
                                class="mt-2 grid grid-cols-2 items-center gap-2"
                            >
                                <label>Area by Continuity (&gt;1.5):</label
                                ><input
                                    type="text"
                                    v-model="dopplerData.av_area"
                                    class="rounded border border-gray-300 bg-white px-2 py-1 text-gray-900"
                                />
                                <label>Regurgitant Fraction (&lt;30%):</label
                                ><input
                                    type="text"
                                    v-model="dopplerData.av_regurg_frac"
                                    class="rounded border border-gray-300 bg-white px-2 py-1 text-gray-900"
                                />
                                <label>AR PHT (&gt;500 msec):</label
                                ><input
                                    type="text"
                                    v-model="dopplerData.av_ar_pht"
                                    class="rounded border border-gray-300 bg-white px-2 py-1 text-gray-900"
                                />
                                <label>AV Mean Grad (&lt;20 mmHg):</label
                                ><input
                                    type="text"
                                    v-model="dopplerData.av_mean_grad"
                                    class="rounded border border-gray-300 bg-white px-2 py-1 text-gray-900"
                                />
                                <label>AR jet density:</label>
                                <select
                                    v-model="dopplerData.av_ar_jet"
                                    class="rounded border border-gray-300 bg-white px-2 py-1 text-gray-900"
                                >
                                    <option value="Faint">Faint</option>
                                    <option value="Dense">Dense</option>
                                </select>
                            </div>
                        </div>

                        <!-- Pulmonic Valve -->
                        <div>
                            <h4
                                class="mb-2 border-b pb-1 font-bold text-indigo-700 uppercase"
                            >
                                Pulmonic Valve
                            </h4>
                            <div class="grid grid-cols-2 items-center gap-2">
                                <label>Acceleration time (msec):</label
                                ><input
                                    type="text"
                                    v-model="dopplerData.pv_accel_time"
                                    class="rounded border border-gray-300 bg-white px-2 py-1 text-gray-900"
                                />
                                <label>Pressure Gradient (mmHg):</label
                                ><input
                                    type="text"
                                    v-model="dopplerData.pv_press_grad"
                                    class="rounded border border-gray-300 bg-white px-2 py-1 text-gray-900"
                                />
                                <label>Regurgitation (%):</label
                                ><input
                                    type="text"
                                    v-model="dopplerData.pv_regurg"
                                    class="rounded border border-gray-300 bg-white px-2 py-1 text-gray-900"
                                />
                                <label>Sys PAP by acc time (&gt;25):</label
                                ><input
                                    type="text"
                                    v-model="dopplerData.pv_sys_pap"
                                    class="rounded border border-gray-300 bg-white px-2 py-1 text-gray-900"
                                />
                                <label>PR jet density:</label>
                                <select
                                    v-model="dopplerData.pv_pr_jet"
                                    class="rounded border border-gray-300 bg-white px-2 py-1 text-gray-900"
                                >
                                    <option value="Faint">Faint</option>
                                    <option value="Dense">Dense</option>
                                </select>
                                <label>RVOT VTI (cm):</label
                                ><input
                                    type="text"
                                    v-model="dopplerData.pv_rvot_vti"
                                    class="rounded border border-gray-300 bg-white px-2 py-1 text-gray-900"
                                />
                            </div>
                        </div>

                        <!-- Qp/Qs -->
                        <div class="mt-4">
                            <div
                                class="flex items-center gap-2 rounded border border-gray-300 bg-gray-50 p-2"
                            >
                                <label class="font-bold text-indigo-700"
                                    >Qp/Qs =</label
                                >
                                <input
                                    type="text"
                                    v-model="dopplerData.qp_qs"
                                    class="w-24 rounded border border-gray-300 bg-white px-2 py-1 text-gray-900"
                                />
                                <span class="ml-2 text-[10px] text-gray-500"
                                    >Normal: 1.0 | Lt to Rt: &gt;1.0 | Rt to Lt:
                                    &lt;1.0</span
                                >
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Conclusion -->
                <div class="mt-6">
                    <label
                        class="mb-2 block text-xs font-black tracking-widest text-indigo-700 uppercase"
                        >Conclusion</label
                    >
                    <p
                        v-if="restricted"
                        class="mb-1 text-[10px] font-bold text-amber-600 uppercase"
                    >
                        Doctor only — the physician completes this section.
                    </p>
                    <textarea
                        v-model="dopplerData.conclusion"
                        :disabled="restricted"
                        class="h-16 w-full resize-none rounded border border-gray-300 bg-white px-3 py-2 text-gray-900 outline-none disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500"
                    ></textarea>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import type { PropType } from 'vue';
import type { DopplerData, EchoData } from '@/types/appointment';
import { buildDocument, buildDopplerDocument } from '@/utils/docExport';

const dopplerData = defineModel<DopplerData>('dopplerData', { required: true });
const props = defineProps({
    echoData: {
        type: Object as PropType<EchoData>,
        required: true,
    },
    patientName: {
        type: String,
        required: true,
    },
    restricted: {
        type: Boolean,
        default: false,
    },
});

const exportToWord = () => {
    const date = new Date().toLocaleDateString();
    const pagesHtml = buildDopplerDocument(
        dopplerData.value,
        props.echoData,
        props.patientName,
        date,
    );
    const blob = new Blob(
        ['\ufeff', buildDocument('Color Flow & Doppler Study', pagesHtml)],
        { type: 'application/msword' },
    );
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `Doppler_Echo_${props.patientName.replace(/\s+/g, '_')}.doc`;
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
label,
span {
    color: #1e293b !important;
}
</style>
