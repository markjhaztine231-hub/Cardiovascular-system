<template>
    <div
        class="animate-fade-in-up mt-4 mb-8 overflow-hidden rounded-xl border-2 border-amber-600 bg-[#f8fafc] shadow-sm"
    >
        <!-- Header -->
        <div
            class="flex items-center justify-between bg-amber-600 px-6 py-4 text-white"
        >
            <h3 class="text-base font-black tracking-widest uppercase">
                Manual Draft
            </h3>
            <button
                @click.prevent="exportToWord"
                class="flex items-center rounded bg-white px-4 py-1.5 text-xs font-bold text-amber-700 shadow-sm transition-colors hover:bg-gray-100"
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
            <!-- Info banner -->
            <div
                class="flex items-center rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800"
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
                Type 4 is a free-form note. Drag a scan from the Scans panel
                (or drop an image file) to attach it, then draft the report.
            </div>

            <!-- Header Info -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label
                        class="block text-[10px] font-bold text-gray-500 uppercase"
                        >Requesting Physician</label
                    >
                    <input
                        type="text"
                        v-model="manualDraftData.requesting_physician"
                        class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 outline-none focus:border-amber-600"
                    />
                </div>
                <div>
                    <label
                        class="block text-[10px] font-bold text-gray-500 uppercase"
                        >Title</label
                    >
                    <input
                        type="text"
                        v-model="manualDraftData.title"
                        placeholder="e.g. Review of external echo report"
                        class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 outline-none focus:border-amber-600"
                    />
                </div>
            </div>

            <!-- Attached scans (drag & drop) -->
            <div>
                <label
                    class="mb-2 block text-[10px] font-bold text-gray-500 uppercase"
                    >Attached Scans</label
                >
                <div
                    @dragover.prevent
                    @drop.prevent="onDrop"
                    class="rounded-lg border-2 border-dashed border-amber-300 bg-amber-50/50 p-4 text-center transition-colors"
                >
                    <template v-if="manualDraftData.images.length">
                        <div
                            class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4"
                        >
                            <div
                                v-for="(img, index) in manualDraftData.images"
                                :key="`${img.url}-${index}`"
                                class="group relative overflow-hidden rounded-lg border border-gray-200 bg-white"
                            >
                                <img
                                    :src="img.url"
                                    :alt="img.name"
                                    class="h-24 w-full bg-gray-50 object-contain"
                                    loading="lazy"
                                />
                                <button
                                    type="button"
                                    @click.prevent="removeImage(index)"
                                    title="Remove image"
                                    class="absolute top-1 right-1 rounded bg-white/90 p-1 text-red-500 shadow-sm hover:bg-red-50"
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
                                <p
                                    class="truncate px-1.5 py-0.5 text-[9px] text-gray-500"
                                >
                                    {{ img.name }}
                                </p>
                            </div>
                        </div>
                        <p
                            class="mt-3 text-[11px] font-semibold text-amber-700"
                        >
                            Drop another scan or click to add more.
                        </p>
                    </template>
                    <template v-else>
                        <svg
                            class="mx-auto mb-2 h-8 w-8 text-amber-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.5"
                                d="M7 16a4 4 0 01-.88-7.9A5 5 0 0115.9 6M15 13l4 4m0-4l-4 4"
                            />
                        </svg>
                        <p class="text-xs font-bold text-gray-600">
                            Drag a scan here from the Scans panel
                        </p>
                        <p class="mt-1 text-[11px] text-gray-400">
                            Drop the OCR-collected image to attach it.
                        </p>
                    </template>
                </div>
            </div>

            <!-- Draft -->
            <div>
                <label
                    class="mb-2 block text-[10px] font-bold text-amber-700 uppercase"
                    >Draft Notes</label
                >
                <textarea
                    v-model="manualDraftData.draft"
                    placeholder="Type the draft report here..."
                    class="h-48 w-full resize-none rounded border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm outline-none focus:border-amber-600"
                ></textarea>
            </div>

            <!-- Conclusion -->
            <div>
                <label
                    class="mb-2 block text-[10px] font-bold text-amber-700 uppercase"
                    >Conclusion</label
                >
                <textarea
                    v-model="manualDraftData.conclusion"
                    placeholder="Optional conclusion / impression..."
                    class="h-24 w-full resize-none rounded border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm outline-none focus:border-amber-600"
                ></textarea>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import type { ManualDraftData, ManualDraftImage } from '@/types/appointment';
import { buildDocument, buildManualDraftDocument } from '@/utils/docExport';

const manualDraftData = defineModel<ManualDraftData>('manualDraftData', {
    required: true,
});

const props = defineProps<{
    patientName: string;
}>();

function addImage(image: ManualDraftImage): void {
    if (!image.url) {
        return;
    }

    // Avoid duplicates of the same document / URL.
    const exists = manualDraftData.value.images.some(
        (i) => i.url === image.url,
    );

    if (!exists) {
        manualDraftData.value.images.push(image);
    }
}

function removeImage(index: number): void {
    manualDraftData.value.images.splice(index, 1);
}

function onDrop(event: DragEvent): void {
    const dragged = event.dataTransfer?.getData('application/x-clinic-scan');

    if (!dragged) {
        return;
    }

    try {
        addImage(JSON.parse(dragged) as ManualDraftImage);
    } catch {
        // malformed payload -> ignore
    }
}

const exportToWord = () => {
    const date = new Date().toLocaleDateString();
    const bodyHtml = buildManualDraftDocument(
        manualDraftData.value,
        props.patientName,
        date,
    );
    const blob = new Blob(
        ['\ufeff', buildDocument('Manual Draft', bodyHtml)],
        {
            type: 'application/msword',
        },
    );
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `Manual_Draft_${props.patientName.replace(/\s+/g, '_')}.doc`;
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