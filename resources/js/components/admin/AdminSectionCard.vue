<template>
    <section
        class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
    >
        <header
            v-if="title || $slots.actions"
            class="flex flex-wrap items-start justify-between gap-3 border-b border-gray-100 px-5 py-4 sm:px-6"
        >
            <div>
                <h2
                    v-if="title"
                    class="text-base font-black tracking-tight text-slate-800"
                >
                    {{ title }}
                </h2>
                <p
                    v-if="subtitle"
                    class="mt-0.5 text-xs font-medium text-gray-500"
                >
                    {{ subtitle }}
                </p>
            </div>
            <slot name="actions" />
        </header>

        <div v-if="padded" class="p-5 sm:p-6">
            <slot />
        </div>
        <slot v-else />
    </section>
</template>

<script setup lang="ts">
withDefaults(
    defineProps<{
        title?: string;
        subtitle?: string;
        padded?: boolean;
    }>(),
    {
        padded: true,
    },
);
</script>
