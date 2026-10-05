<template>
    <div
        class="min-h-screen bg-gray-50 py-10 transition-colors duration-300 sm:px-6 lg:px-8 dark:bg-gray-900"
    >
        <div class="mx-auto w-full max-w-3xl">
            <!-- Header -->
            <div
                class="border border-gray-100 bg-white px-6 py-8 shadow-xl shadow-gray-200/50 sm:rounded-2xl dark:border-gray-700 dark:bg-gray-800 dark:shadow-none"
            >
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h1
                            class="text-2xl font-extrabold tracking-tight text-gray-900 sm:text-3xl dark:text-white"
                        >
                            {{ titleCase(terms.title) }}
                        </h1>
                        <p
                            v-if="terms.entity"
                            class="mt-2 text-sm font-medium text-gray-700 dark:text-gray-300"
                        >
                            {{ terms.entity }}
                        </p>
                        <p
                            v-if="terms.service"
                            class="mt-1 text-sm text-gray-600 dark:text-gray-400"
                        >
                            {{ terms.service }}
                        </p>
                    </div>

                    <button
                        type="button"
                        class="hidden shrink-0 items-center gap-2 rounded-xl border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 focus:ring-2 focus:ring-blue-500 focus:outline-none sm:inline-flex dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
                        @click="printPage"
                    >
                        <svg
                            class="h-4 w-4"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                            stroke-width="2"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v8H6z"
                            />
                        </svg>
                        Print
                    </button>
                </div>

                <dl
                    class="mt-6 grid gap-3 border-t border-gray-100 pt-6 text-sm sm:grid-cols-2 dark:border-gray-700"
                >
                    <div v-if="terms.effective_date">
                        <dt
                            class="text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400"
                        >
                            Effective date
                        </dt>
                        <dd class="mt-0.5 text-gray-900 dark:text-white">
                            {{ terms.effective_date }}
                        </dd>
                    </div>
                    <div v-if="terms.governing_jurisdiction">
                        <dt
                            class="text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400"
                        >
                            Governing jurisdiction
                        </dt>
                        <dd class="mt-0.5 text-gray-900 dark:text-white">
                            {{ terms.governing_jurisdiction }}
                        </dd>
                    </div>
                </dl>

                <p
                    class="mt-4 text-xs text-gray-500 dark:text-gray-400"
                >
                    Version {{ version }}
                </p>
            </div>

            <!-- Body -->
            <div
                class="mt-6 border border-gray-100 bg-white px-6 py-8 shadow-xl shadow-gray-200/50 sm:rounded-2xl sm:px-10 dark:border-gray-700 dark:bg-gray-800 dark:shadow-none"
            >
                <div class="space-y-4 text-sm leading-relaxed text-gray-700 dark:text-gray-300">
                    <template v-for="(block, index) in terms.blocks" :key="index">
                        <h2
                            v-if="block.type === 'section'"
                            class="pt-4 text-lg font-bold text-gray-900 dark:text-white"
                        >
                            {{ block.heading }}
                        </h2>

                        <h3
                            v-else-if="block.type === 'subsection'"
                            class="pt-2 text-base font-semibold text-gray-900 dark:text-white"
                        >
                            {{ block.heading }}
                        </h3>

                        <p
                            v-else-if="block.type === 'paragraph'"
                            class="text-justify"
                        >
                            {{ block.text }}
                        </p>

                        <!--
                            All-caps notices in the source document, such as the
                            Section 4.1 emergency disclaimer, are surfaced as
                            distinct blocks so they read as warnings rather than
                            ordinary body copy.
                        -->
                        <div
                            v-else-if="block.type === 'warning'"
                            role="note"
                            class="rounded-xl border-l-4 border-red-500 bg-red-50 px-4 py-3 text-sm font-bold tracking-wide text-red-800 dark:bg-red-950/40 dark:text-red-200"
                        >
                            <svg
                                class="mr-2 inline h-4 w-4 align-[-2px]"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                                stroke-width="2"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"
                                />
                            </svg>
                            {{ block.text }}
                        </div>

                        <ul
                            v-else-if="block.type === 'bullets'"
                            class="list-disc space-y-2 pl-5 marker:text-blue-500"
                        >
                            <li
                                v-for="(item, itemIndex) in block.items"
                                :key="itemIndex"
                                class="text-justify"
                            >
                                {{ item }}
                            </li>
                        </ul>
                    </template>
                </div>
            </div>

            <!-- Footer navigation -->
            <div
                class="mt-6 flex flex-wrap items-center justify-between gap-3 border border-gray-100 bg-white px-6 py-5 shadow-xl shadow-gray-200/50 sm:rounded-2xl dark:border-gray-700 dark:bg-gray-800 dark:shadow-none"
            >
                <Link
                    href="/register"
                    class="text-sm font-medium text-blue-600 transition-colors hover:text-blue-500 dark:text-blue-400"
                >
                    &larr; Back to registration
                </Link>
                <Link
                    href="/"
                    class="text-sm font-medium text-gray-600 transition-colors hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200"
                >
                    {{ terms.entity || 'Home' }}
                </Link>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

type TermsBlock =
    | { type: 'section'; heading: string }
    | { type: 'subsection'; heading: string }
    | { type: 'paragraph'; text: string }
    | { type: 'warning'; text: string }
    | { type: 'bullets'; items: string[] };

interface TermsPayload {
    title: string;
    entity?: string;
    service?: string;
    effective_date?: string;
    governing_jurisdiction?: string;
    blocks: TermsBlock[];
}

defineProps<{
    terms: TermsPayload;
    version: string;
}>();

/**
 * The document's cover renders the title in all caps. Fold it to sentence case
 * for display, leaving connecting words lower-case.
 */
function titleCase(value: string): string {
    const small = [
        'a', 'an', 'and', 'as', 'at', 'but', 'by', 'for', 'in', 'nor',
        'of', 'on', 'or', 'per', 'the', 'to', 'via', 'vs',
    ];

    return value
        .toLowerCase()
        .split(/\s+/)
        .map((word, index) =>
            index > 0 && small.includes(word) ? word : word.charAt(0).toUpperCase() + word.slice(1),
        )
        .join(' ');
}

function printPage(): void {
    window.print();
}
</script>