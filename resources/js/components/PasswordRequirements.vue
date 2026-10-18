<script setup lang="ts">
import { Check, Circle } from 'lucide-vue-next';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        password?: string;
        min?: number;
        full?: boolean;
    }>(),
    {
        password: '',
        min: 12,
        full: true,
    },
);

interface Rule {
    label: string;
    ok: boolean;
}

const checks = computed<Rule[]>(() => {
    const value = props.password ?? '';

    const rules: Rule[] = [
        {
            label: `At least ${props.min} characters`,
            ok: value.length >= props.min,
        },
    ];

    if (props.full) {
        rules.push(
            { label: 'An uppercase letter (A-Z)', ok: /[A-Z]/.test(value) },
            { label: 'A lowercase letter (a-z)', ok: /[a-z]/.test(value) },
            { label: 'A number (0-9)', ok: /\d/.test(value) },
            {
                label: 'A symbol (e.g. !@#$%)',
                ok: /[^A-Za-z0-9]/.test(value),
            },
        );
    }

    return rules;
});
</script>

<template>
    <ul class="mt-2 grid gap-1 text-[11px] font-semibold sm:grid-cols-2">
        <li
            v-for="rule in checks"
            :key="rule.label"
            class="flex items-center gap-1.5 transition-colors"
            :class="rule.ok ? 'text-emerald-600' : 'text-gray-400'"
        >
            <Check
                v-if="rule.ok"
                class="h-3.5 w-3.5 shrink-0"
                aria-hidden="true"
            />
            <Circle v-else class="h-3.5 w-3.5 shrink-0" aria-hidden="true" />
            {{ rule.label }}
        </li>
    </ul>
</template>
