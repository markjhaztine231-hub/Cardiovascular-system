<script setup lang="ts">
import { Eye, EyeOff } from 'lucide-vue-next';
import { computed, ref, useAttrs } from 'vue';
import { cn } from '@/lib/utils';

defineOptions({ inheritAttrs: false });

const model = defineModel<string>();

const attrs = useAttrs();
const show = ref(false);

const inputClass = computed(() => cn(attrs.class as never, 'pr-11'));

const inputAttrs = computed(() => {
    const rest: Record<string, unknown> = {};

    for (const [key, value] of Object.entries(attrs)) {
        if (key !== 'class') {
            rest[key] = value;
        }
    }

    return rest;
});
</script>

<template>
    <div class="relative">
        <input
            v-model="model"
            :type="show ? 'text' : 'password'"
            :class="inputClass"
            v-bind="inputAttrs"
        />
        <button
            type="button"
            :tabindex="-1"
            :aria-label="show ? 'Hide password' : 'Show password'"
            class="absolute inset-y-0 right-0 flex w-9 items-center justify-center text-gray-400 transition-colors hover:text-gray-600 focus:outline-none"
            @click="show = !show"
        >
            <EyeOff v-if="show" class="h-4 w-4" />
            <Eye v-else class="h-4 w-4" />
        </button>
    </div>
</template>
