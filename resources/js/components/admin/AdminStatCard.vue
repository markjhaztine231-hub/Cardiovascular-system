<template>
    <div
        class="relative overflow-hidden rounded-2xl border border-gray-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
    >
        <div class="flex items-start justify-between gap-3">
            <span
                v-if="icon"
                class="flex h-10 w-10 items-center justify-center rounded-xl"
                :class="tone.iconClass"
            >
                <component :is="icon" class="h-5 w-5" />
            </span>
            <span
                v-if="hint"
                class="rounded-full px-2.5 py-1 text-[10px] font-black tracking-wide uppercase"
                :class="tone.badgeClass"
            >
                {{ hint }}
            </span>
        </div>

        <p class="mt-4 text-2xl font-black tracking-tight text-slate-800">
            {{ value }}
        </p>
        <p
            class="mt-1 text-[10px] font-black tracking-[0.16em] text-slate-400 uppercase"
        >
            {{ label }}
        </p>

        <span
            class="absolute inset-x-0 bottom-0 h-1"
            :class="tone.accentClass"
        ></span>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import type { Component } from 'vue';

type Tone = 'indigo' | 'emerald' | 'sky' | 'amber' | 'red' | 'slate';

const props = withDefaults(
    defineProps<{
        label: string;
        value: string | number;
        hint?: string;
        icon?: Component;
        tone?: Tone;
    }>(),
    {
        tone: 'indigo',
    },
);

const tones: Record<
    Tone,
    { iconClass: string; badgeClass: string; accentClass: string }
> = {
    indigo: {
        iconClass: 'bg-indigo-50 text-indigo-600',
        badgeClass: 'bg-indigo-50 text-indigo-600',
        accentClass: 'bg-indigo-500',
    },
    emerald: {
        iconClass: 'bg-emerald-50 text-emerald-600',
        badgeClass: 'bg-emerald-50 text-emerald-600',
        accentClass: 'bg-emerald-500',
    },
    sky: {
        iconClass: 'bg-sky-50 text-sky-600',
        badgeClass: 'bg-sky-50 text-sky-600',
        accentClass: 'bg-sky-500',
    },
    amber: {
        iconClass: 'bg-amber-50 text-amber-600',
        badgeClass: 'bg-amber-50 text-amber-700',
        accentClass: 'bg-amber-400',
    },
    red: {
        iconClass: 'bg-red-50 text-red-600',
        badgeClass: 'bg-red-50 text-red-600',
        accentClass: 'bg-red-500',
    },
    slate: {
        iconClass: 'bg-slate-100 text-slate-600',
        badgeClass: 'bg-slate-100 text-slate-600',
        accentClass: 'bg-slate-400',
    },
};

const tone = computed(() => tones[props.tone]);
</script>
