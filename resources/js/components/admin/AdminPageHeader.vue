<template>
    <div
        class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
    >
        <div>
            <p
                v-if="eyebrow"
                class="text-[10px] font-black tracking-[0.22em] text-indigo-500 uppercase"
            >
                {{ eyebrow }}
            </p>
            <h1 class="mt-1 text-2xl font-black tracking-tight text-slate-800">
                {{ title }}
            </h1>
            <p
                v-if="description"
                class="mt-1.5 max-w-2xl text-sm leading-relaxed text-gray-500"
            >
                {{ description }}
            </p>
        </div>

        <div v-if="$slots.actions" class="flex flex-wrap items-center gap-2">
            <slot name="actions" />
        </div>
    </div>
</template>

<script setup lang="ts">
defineProps<{
    title: string;
    eyebrow?: string;
    description?: string;
}>();
</script>
