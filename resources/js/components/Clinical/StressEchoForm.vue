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
            </div>

            <!-- (fields removed) -->


            <!-- (pre-test removed) -->

            <!-- (protocol removed) -->

            <!-- (stages removed) -->

            <!-- (peak data removed) -->

            <!-- (echo findings removed) -->

            <!-- (findings removed) -->
            <div>
                <div
                    class="mb-1 flex flex-wrap items-center justify-between gap-2"
                >
                    <label
                        class="block text-[10px] font-bold text-rose-700 uppercase"
                        >Conclusion</label
                    >
                    <button
                        v-if="!restricted"
                        type="button"
                        @click.prevent="applyConclusionTemplate"
                        class="rounded border border-rose-300 bg-rose-50 px-3 py-1 text-[10px] font-bold tracking-wide text-rose-800 uppercase transition-colors hover:bg-rose-100"
                        title="Insert the standard stress echo conclusion. Every word stays editable."
                    >
                        Insert conclusion template
                    </button>
                </div>
                <p
                    v-if="restricted"
                    class="mb-1 text-[10px] font-bold text-amber-600 uppercase"
                >
                    Doctor only — the physician completes this section.
                </p>
                <textarea
                    ref="conclusionInput"
                    v-model="stressEchoData.conclusion"
                    :disabled="restricted"
                    placeholder="Optional conclusion / impression..."
                    class="min-h-56 w-full resize-y rounded border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 outline-none focus:border-rose-600 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500"
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
import type { StressEchoData } from '@/types/appointment';
import { buildStressEchoDocument, exportAsDocx } from '@/utils/docExport';

/**
 * Standard stress echo conclusion offered as a starting point. The underscores
 * are fill-in blanks (the first one is selected automatically after insertion)
 * and the whole text stays editable.
 */
const STRESS_ECHO_CONCLUSION_TEMPLATE = `CONCLUSION:
EXERCISE STRESS ECG: __ for exercise-induced ischemia at _ METS

EXERCISE STRESS ECHO: __ exercise stress echocardiogram with no exercise-induced wall motion abnormality.
Physiologic blood pressure and heart rate response to exercise
__ exercise capacity for age.`;

const stressEchoData = defineModel<StressEchoData>('stressEchoData', {
    required: true,
});

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

const conclusionInput = ref<HTMLTextAreaElement | null>(null);

function applyConclusionTemplate(): void {
    if (props.restricted) {
        return;
    }

    const current = (stressEchoData.value.conclusion ?? '').trim();

    if (
        current !== '' &&
        current !== STRESS_ECHO_CONCLUSION_TEMPLATE.trim() &&
        !window.confirm('Replace the current conclusion with the template?')
    ) {
        return;
    }

    stressEchoData.value.conclusion = STRESS_ECHO_CONCLUSION_TEMPLATE;

    nextTick(() => {
        const textarea = conclusionInput.value;

        if (!textarea) {
            return;
        }

        textarea.focus();

        const start = STRESS_ECHO_CONCLUSION_TEMPLATE.indexOf('_');

        if (start === -1) {
            return;
        }

        let end = start;

        while (STRESS_ECHO_CONCLUSION_TEMPLATE[end] === '_') {
            end += 1;
        }

        textarea.setSelectionRange(start, end);
    });
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
