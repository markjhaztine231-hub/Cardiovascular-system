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
            <div>
                <label
                    class="mb-2 block border-t border-gray-200 pt-4 text-xs font-black tracking-widest text-[#146b8a] uppercase"
                    >Echocardiographic Findings</label
                >
                <p
                    v-if="restricted"
                    class="mb-1 text-[10px] font-bold text-amber-600 uppercase"
                >
                    Doctor only — the physician completes this section.
                </p>
                <textarea
                    v-model="echoData.findings"
                    :disabled="restricted"
                    placeholder="Type findings here..."
                    class="h-32 w-full resize-none rounded border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm outline-none focus:border-[#0f8c8e] disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500"
                ></textarea>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import type { EchoData } from '@/types/appointment';
import { buildDocument, buildEchoHTML } from '@/utils/docExport';

const echoData = defineModel<EchoData>('echoData', { required: true });
const props = withDefaults(
    defineProps<{
        patientName: string;
        restricted?: boolean;
    }>(),
    { restricted: false },
);

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
