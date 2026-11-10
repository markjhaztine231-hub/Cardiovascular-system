<template>
    <div
        class="animate-fade-in-up mt-4 mb-8 overflow-hidden rounded-xl border-2 border-[#146b8a] bg-[#f8fafc] shadow-sm"
    >
        <!-- Header -->
        <div
            class="flex items-center justify-between bg-[#146b8a] px-6 py-4 text-white"
        >
            <h3 class="text-base font-black tracking-widest uppercase">
                Echocardiographic Report
            </h3>
            <button
                @click.prevent="exportToWord"
                class="flex items-center rounded bg-white px-4 py-1.5 text-xs font-bold text-[#146b8a] shadow-sm transition-colors hover:bg-gray-100"
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

        <div class="space-y-6 p-6">
            <!-- Header Info -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label
                        class="block text-[10px] font-bold text-gray-500 uppercase"
                        >Requesting Physician</label
                    >
                    <input
                        type="text"
                        v-model="echoData.requesting_physician"
                        class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 outline-none focus:border-[#146b8a]"
                    />
                </div>
                <div>
                    <label
                        class="block text-[10px] font-bold text-gray-500 uppercase"
                        >Contact No</label
                    >
                    <input
                        type="text"
                        v-model="echoData.contact_no"
                        class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 outline-none focus:border-[#146b8a]"
                    />
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 md:grid-cols-6">
                <div>
                    <label
                        class="block text-[10px] font-bold text-[#146b8a] uppercase"
                        >File # (Auto)</label
                    >
                    <input
                        type="text"
                        v-model="echoData.file_no"
                        readonly
                        class="w-full cursor-not-allowed rounded border border-blue-200 bg-blue-50 px-3 py-2 text-sm font-bold text-[#146b8a]"
                    />
                </div>
                <div>
                    <label
                        class="block text-[10px] font-bold text-gray-500 uppercase"
                        >BSA</label
                    >
                    <input
                        type="text"
                        v-model="echoData.bsa"
                        class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900"
                    />
                </div>
                <div>
                    <label
                        class="block text-[10px] font-bold text-gray-500 uppercase"
                        >Age</label
                    >
                    <input
                        type="text"
                        v-model="echoData.age"
                        class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900"
                    />
                </div>
                <div>
                    <label
                        class="block text-[10px] font-bold text-gray-500 uppercase"
                        >Sex</label
                    >
                    <select
                        v-model="echoData.sex"
                        class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900"
                    >
                        <option value="">Select...</option>
                        <option value="M">Male</option>
                        <option value="F">Female</option>
                    </select>
                </div>
                <div>
                    <label
                        class="block text-[10px] font-bold text-gray-500 uppercase"
                        >Height (cm)</label
                    >
                    <input
                        type="text"
                        v-model="echoData.height"
                        class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900"
                    />
                </div>
                <div>
                    <label
                        class="block text-[10px] font-bold text-gray-500 uppercase"
                        >Weight (kgs)</label
                    >
                    <input
                        type="text"
                        v-model="echoData.weight"
                        class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900"
                    />
                </div>
            </div>

            <!-- 2-Column Echo Data Grid -->
            <div
                class="grid grid-cols-1 gap-8 border-t border-gray-200 pt-6 xl:grid-cols-2"
            >
                <!-- LEFT COLUMN -->
                <div class="space-y-4">
                    <!-- Mitral Valve -->
                    <div
                        class="rounded border border-gray-200 bg-white p-4 shadow-sm"
                    >
                        <h4
                            class="mb-3 border-b border-gray-100 pb-2 text-xs font-black tracking-widest text-[#146b8a] uppercase"
                        >
                            Mitral Valve
                        </h4>
                        <div
                            class="grid grid-cols-2 items-center gap-3 text-xs"
                        >
                            <label>EF Velocity (mm²):</label
                            ><input
                                type="text"
                                v-model="echoData.mitral_ef"
                                class="rounded border border-gray-300 bg-white px-2 py-1.5 text-gray-900"
                            />
                            <label>DE Excursion (mm):</label
                            ><input
                                type="text"
                                v-model="echoData.mitral_de"
                                class="rounded border border-gray-300 bg-white px-2 py-1.5 text-gray-900"
                            />
                            <label>EPSS (mm):</label
                            ><input
                                type="text"
                                v-model="echoData.mitral_epss"
                                class="rounded border border-gray-300 bg-white px-2 py-1.5 text-gray-900"
                            />
                            <label>Area by planimetry (cm²):</label
                            ><input
                                type="text"
                                v-model="echoData.mitral_area"
                                class="rounded border border-gray-300 bg-white px-2 py-1.5 text-gray-900"
                            />
                        </div>
                    </div>

                    <div
                        class="flex items-center justify-between rounded border border-gray-200 bg-white p-4 shadow-sm"
                    >
                        <h4
                            class="text-xs font-black tracking-widest text-[#146b8a] uppercase"
                        >
                            Tricuspid Valve
                        </h4>
                        <input
                            type="text"
                            v-model="echoData.tricuspid_valve"
                            placeholder="normal"
                            class="w-1/2 rounded border border-gray-300 bg-white px-3 py-1.5 text-xs text-gray-900"
                        />
                    </div>

                    <!-- Aortic Valve -->
                    <div
                        class="rounded border border-gray-200 bg-white p-4 shadow-sm"
                    >
                        <h4
                            class="mb-3 border-b border-gray-100 pb-2 text-xs font-black tracking-widest text-[#146b8a] uppercase"
                        >
                            Aortic Valve
                        </h4>
                        <div
                            class="grid grid-cols-2 items-center gap-3 text-xs"
                        >
                            <label>Root Diameter (mm):</label
                            ><input
                                type="text"
                                v-model="echoData.aortic_root"
                                class="rounded border border-gray-300 bg-white px-2 py-1.5 text-gray-900"
                            />
                            <label>Valve Opening (mm):</label
                            ><input
                                type="text"
                                v-model="echoData.aortic_valve"
                                class="rounded border border-gray-300 bg-white px-2 py-1.5 text-gray-900"
                            />
                            <label>Excursion (mm):</label
                            ><input
                                type="text"
                                v-model="echoData.aortic_excursion"
                                class="rounded border border-gray-300 bg-white px-2 py-1.5 text-gray-900"
                            />
                        </div>
                    </div>

                    <!-- Right Atrium & Pulmonic Valve -->
                    <div class="grid grid-cols-2 gap-4">
                        <div
                            class="rounded border border-gray-200 bg-white p-4 shadow-sm"
                        >
                            <h4
                                class="mb-3 border-b border-gray-100 pb-2 text-xs font-black tracking-widest text-[#146b8a] uppercase"
                            >
                                Right Atrium
                            </h4>
                            <div class="flex items-center gap-2 text-xs">
                                <label>RL Dia (mm):</label>
                                <input
                                    type="text"
                                    v-model="echoData.ra_rl"
                                    class="w-full rounded border border-gray-300 bg-white px-2 py-1.5 text-gray-900"
                                />
                            </div>
                        </div>
                        <div
                            class="rounded border border-gray-200 bg-white p-4 shadow-sm"
                        >
                            <h4
                                class="mb-3 border-b border-gray-100 pb-2 text-xs font-black tracking-widest text-[#146b8a] uppercase"
                            >
                                Pulmonic Valve
                            </h4>
                            <div class="space-y-2 text-xs">
                                <input
                                    type="text"
                                    v-model="echoData.pulmonic_root"
                                    placeholder="Root Dia (mm)"
                                    class="w-full rounded border border-gray-300 bg-white px-2 py-1.5 text-gray-900"
                                />
                                <input
                                    type="text"
                                    v-model="echoData.pulmonic_notching"
                                    placeholder="Systolic Notching"
                                    class="w-full rounded border border-gray-300 bg-white px-2 py-1.5 text-gray-900"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Left Atrium & Right Ventricle -->
                    <div class="grid grid-cols-2 gap-4">
                        <div
                            class="rounded border border-gray-200 bg-white p-4 shadow-sm"
                        >
                            <h4
                                class="mb-3 border-b border-gray-100 pb-2 text-xs font-black tracking-widest text-[#146b8a] uppercase"
                            >
                                Left Atrium
                            </h4>
                            <div class="grid grid-cols-1 gap-2 text-xs">
                                <input
                                    type="text"
                                    v-model="echoData.la_ap"
                                    placeholder="AP Dia (mm)"
                                    class="rounded border border-gray-300 bg-white px-2 py-1.5 text-gray-900"
                                />
                                <input
                                    type="text"
                                    v-model="echoData.la_rl"
                                    placeholder="RL Dia (mm)"
                                    class="rounded border border-gray-300 bg-white px-2 py-1.5 text-gray-900"
                                />
                                <input
                                    type="text"
                                    v-model="echoData.la_lavi"
                                    placeholder="LAVI (/mL m²)"
                                    class="rounded border border-gray-300 bg-white px-2 py-1.5 text-gray-900"
                                />
                            </div>
                        </div>
                        <div
                            class="rounded border border-gray-200 bg-white p-4 shadow-sm"
                        >
                            <h4
                                class="mb-3 border-b border-gray-100 pb-2 text-xs font-black tracking-widest text-[#146b8a] uppercase"
                            >
                                Right Ventricle
                            </h4>
                            <div class="grid grid-cols-1 gap-2 text-xs">
                                <input
                                    type="text"
                                    v-model="echoData.rv_ap"
                                    placeholder="AP Dia (mm)"
                                    class="rounded border border-gray-300 bg-white px-2 py-1.5 text-gray-900"
                                />
                                <input
                                    type="text"
                                    v-model="echoData.rv_rl"
                                    placeholder="RL Dia (mm)"
                                    class="rounded border border-gray-300 bg-white px-2 py-1.5 text-gray-900"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RIGHT ECHO COLUMN -->
                <div class="space-y-4">
                    <!-- Septum & LVPW -->
                    <div class="grid grid-cols-2 gap-4">
                        <div
                            class="rounded border border-gray-200 bg-white p-4 shadow-sm"
                        >
                            <h4
                                class="mb-3 border-b border-gray-100 pb-2 text-[10px] font-black tracking-widest text-[#146b8a] uppercase"
                            >
                                Interventricular Septum
                            </h4>
                            <div class="grid grid-cols-1 gap-2 text-xs">
                                <input
                                    type="text"
                                    v-model="echoData.ivs_dia"
                                    placeholder="Diastolic (mm)"
                                    class="rounded border border-gray-300 bg-white px-2 py-1.5 text-gray-900"
                                />
                                <input
                                    type="text"
                                    v-model="echoData.ivs_sys"
                                    placeholder="Systolic (mm)"
                                    class="rounded border border-gray-300 bg-white px-2 py-1.5 text-gray-900"
                                />
                                <input
                                    type="text"
                                    v-model="echoData.ivs_exc"
                                    placeholder="Excursion (mm)"
                                    class="rounded border border-gray-300 bg-white px-2 py-1.5 text-gray-900"
                                />
                            </div>
                        </div>
                        <div
                            class="rounded border border-gray-200 bg-white p-4 shadow-sm"
                        >
                            <h4
                                class="mb-3 border-b border-gray-100 pb-2 text-[10px] font-black tracking-widest text-[#146b8a] uppercase"
                            >
                                LV Posterior Wall
                            </h4>
                            <div class="grid grid-cols-1 gap-2 text-xs">
                                <input
                                    type="text"
                                    v-model="echoData.lvpw_dia"
                                    placeholder="Diastolic (mm)"
                                    class="rounded border border-gray-300 bg-white px-2 py-1.5 text-gray-900"
                                />
                                <input
                                    type="text"
                                    v-model="echoData.lvpw_sys"
                                    placeholder="Systolic (mm)"
                                    class="rounded border border-gray-300 bg-white px-2 py-1.5 text-gray-900"
                                />
                                <input
                                    type="text"
                                    v-model="echoData.lvpw_exc"
                                    placeholder="Excursion (mm)"
                                    class="rounded border border-gray-300 bg-white px-2 py-1.5 text-gray-900"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Left Ventricle -->
                    <div
                        class="rounded border border-gray-200 bg-white p-4 shadow-sm"
                    >
                        <h4
                            class="mb-3 border-b border-gray-100 pb-2 text-xs font-black tracking-widest text-[#146b8a] uppercase"
                        >
                            Left Ventricle
                        </h4>
                        <div
                            class="grid grid-cols-2 items-center gap-x-3 gap-y-2 text-xs"
                        >
                            <label class="font-medium text-gray-600"
                                >Diastolic dia (mm):</label
                            ><input
                                type="text"
                                v-model="echoData.lv_dia"
                                class="rounded border border-gray-300 bg-white px-2 py-1.5 text-gray-900"
                            />
                            <label class="font-medium text-gray-600"
                                >Systolic dia (mm):</label
                            ><input
                                type="text"
                                v-model="echoData.lv_sys"
                                class="rounded border border-gray-300 bg-white px-2 py-1.5 text-gray-900"
                            />
                            <label class="font-medium text-gray-600"
                                >LVEDV (mL):</label
                            ><input
                                type="text"
                                v-model="echoData.lv_lvedv"
                                class="rounded border border-gray-300 bg-white px-2 py-1.5 text-gray-900"
                            />
                            <label class="font-medium text-gray-600"
                                >LVESV (mL):</label
                            ><input
                                type="text"
                                v-model="echoData.lv_lvesv"
                                class="rounded border border-gray-300 bg-white px-2 py-1.5 text-gray-900"
                            />
                            <label class="font-medium text-gray-600"
                                >LV mass index (gm/m²):</label
                            ><input
                                type="text"
                                v-model="echoData.lv_mass"
                                class="rounded border border-gray-300 bg-white px-2 py-1.5 text-gray-900"
                            />
                            <label class="font-medium text-gray-600"
                                >Relative wall thickness (mm):</label
                            ><input
                                type="text"
                                v-model="echoData.lv_rwt"
                                class="rounded border border-gray-300 bg-white px-2 py-1.5 text-gray-900"
                            />
                            <label class="font-medium text-gray-600"
                                >Ejection Fraction (%):</label
                            ><input
                                type="text"
                                v-model="echoData.lv_ef"
                                class="rounded border border-gray-300 bg-white px-2 py-1.5 text-gray-900"
                            />
                            <label class="font-medium text-gray-600"
                                >Fractional Shortening (%):</label
                            ><input
                                type="text"
                                v-model="echoData.lv_fs"
                                class="rounded border border-gray-300 bg-white px-2 py-1.5 text-gray-900"
                            />
                            <label class="font-medium text-gray-600"
                                >Heart Rate (bpm):</label
                            ><input
                                type="text"
                                v-model="echoData.hr"
                                class="rounded border border-gray-300 bg-white px-2 py-1.5 text-gray-900"
                            />
                            <label class="font-medium text-gray-600"
                                >Simpson's (%):</label
                            ><input
                                type="text"
                                v-model="echoData.simpsons"
                                class="rounded border border-gray-300 bg-white px-2 py-1.5 text-gray-900"
                            />
                            <label class="font-medium text-gray-600"
                                >TAPSE (cm):</label
                            ><input
                                type="text"
                                v-model="echoData.tapse"
                                class="rounded border border-gray-300 bg-white px-2 py-1.5 text-gray-900"
                            />
                        </div>
                    </div>

                    <!-- Pericardial Space & RVOT/LVOT -->
                    <div class="grid grid-cols-2 gap-4">
                        <div
                            class="rounded border border-gray-200 bg-white p-4 shadow-sm"
                        >
                            <h4
                                class="mb-3 border-b border-gray-100 pb-2 text-[10px] font-black tracking-widest text-[#146b8a] uppercase"
                            >
                                Pericardial Space
                            </h4>
                            <div class="grid grid-cols-1 gap-2 text-xs">
                                <input
                                    type="text"
                                    v-model="echoData.peri_ant"
                                    placeholder="Anterior Effusion (mm)"
                                    class="rounded border border-gray-300 bg-white px-2 py-1.5 text-gray-900"
                                />
                                <input
                                    type="text"
                                    v-model="echoData.peri_post"
                                    placeholder="Posterior Effusion (mm)"
                                    class="rounded border border-gray-300 bg-white px-2 py-1.5 text-gray-900"
                                />
                            </div>
                        </div>
                        <div
                            class="flex flex-col justify-center gap-2 rounded border border-gray-200 bg-white p-4 shadow-sm"
                        >
                            <div class="flex items-center text-xs">
                                <label class="w-12 font-bold text-[#146b8a]"
                                    >RVOT</label
                                >
                                <input
                                    type="text"
                                    v-model="echoData.rvot"
                                    placeholder="mm"
                                    class="w-full rounded border border-gray-300 bg-white px-2 py-1.5 text-gray-900"
                                />
                            </div>
                            <div class="flex items-center text-xs">
                                <label class="w-12 font-bold text-[#146b8a]"
                                    >LVOT</label
                                >
                                <input
                                    type="text"
                                    v-model="echoData.lvot"
                                    placeholder="mm"
                                    class="w-full rounded border border-gray-300 bg-white px-2 py-1.5 text-gray-900"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Findings -->
            <div class="border-t border-gray-200 pt-4">
                <div
                    class="mb-2 flex flex-wrap items-center justify-between gap-2"
                >
                    <label
                        class="block text-xs font-black tracking-widest text-[#146b8a] uppercase"
                        >Echocardiographic Findings</label
                    >
                    <button
                        v-if="!restricted"
                        type="button"
                        @click.prevent="applyFindingsTemplate"
                        class="rounded border border-cyan-300 bg-cyan-50 px-3 py-1 text-[10px] font-bold tracking-wide text-cyan-800 uppercase transition-colors hover:bg-cyan-100"
                        title="Insert the standard echocardiographic report. Every word stays editable."
                    >
                        Insert echo template
                    </button>
                </div>
                <p
                    v-if="restricted"
                    class="mb-1 text-[10px] font-bold text-amber-600 uppercase"
                >
                    Doctor only — the physician completes this section.
                </p>
                <textarea
                    ref="findingsInput"
                    v-model="echoData.findings"
                    :disabled="restricted"
                    placeholder="Type findings here..."
                    class="min-h-72 w-full resize-y rounded border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm outline-none focus:border-[#0f8c8e] disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500"
                ></textarea>
                <p v-if="!restricted" class="mt-1 text-[10px] text-gray-500">
                    Tip: use the template, then type over the `_` blanks. All
                    text remains editable.
                </p>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { nextTick, ref } from 'vue';
import type { EchoData } from '@/types/appointment';
import { buildDocument, buildEchoHTML } from '@/utils/docExport';

/**
 * Standard 2D echo report offered as a starting point. The underscores are
 * fill-in blanks (the first one is selected automatically after insertion) and
 * the whole text stays editable.
 */
const ECHO_FINDINGS_TEMPLATE = `The left ventricle _ with left ventricular mass index of _ gm/m2 and relative wall thickness of __ mm with adequate wall motion and contractility with overall ejection fraction of __% by Teichholz technique.
The left atrium is normal in size with a left atrial volume index of __ ml/ m2.
The right atrium and right ventricle are normal in size.
The mitral, tricuspid, aortic and pulmonic valves appear normal without restriction in opening and closing.
The aortic root diameter is normal in size.
The interatrial and interventricular septa are intact.
No intracardiac thrombus
No significant pericardial abnormalities

CONCLUSION:
-____ left ventricular _ with adequate contractility with preserved systolic function
-Normal atrial chamber sizes
-Normal right ventricle size with adequate systolic function
-Structurally normal valves`;

const echoData = defineModel<EchoData>('echoData', { required: true });
const props = defineProps({
    patientName: {
        type: String,
        required: true,
    },
    restricted: {
        type: Boolean,
        default: false,
    },
});

const findingsInput = ref<HTMLTextAreaElement | null>(null);

function applyFindingsTemplate(): void {
    if (props.restricted) {
        return;
    }

    const current = (echoData.value.findings ?? '').trim();

    if (
        current !== '' &&
        current !== ECHO_FINDINGS_TEMPLATE.trim() &&
        !window.confirm('Replace the current findings with the template?')
    ) {
        return;
    }

    echoData.value.findings = ECHO_FINDINGS_TEMPLATE;

    nextTick(() => {
        const textarea = findingsInput.value;

        if (!textarea) {
            return;
        }

        textarea.focus();

        const start = ECHO_FINDINGS_TEMPLATE.indexOf('_');

        if (start === -1) {
            return;
        }

        let end = start;

        while (ECHO_FINDINGS_TEMPLATE[end] === '_') {
            end += 1;
        }

        textarea.setSelectionRange(start, end);
    });
}

const exportToWord = () => {
    const date = new Date().toLocaleDateString();
    const bodyHtml = buildEchoHTML(echoData.value, props.patientName, date);
    const blob = new Blob(
        ['\ufeff', buildDocument('Echocardiographic Report', bodyHtml)],
        {
            type: 'application/msword',
        },
    );
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `Echocardiogram_${props.patientName.replace(/\s+/g, '_')}.doc`;
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
