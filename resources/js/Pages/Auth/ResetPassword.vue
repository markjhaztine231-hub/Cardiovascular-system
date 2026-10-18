<template>
    <div
        class="flex min-h-screen flex-col justify-center bg-gray-50 py-12 transition-colors duration-300 sm:px-6 lg:px-8 dark:bg-gray-900"
    >
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <div class="flex justify-center">
                <div
                    class="flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-600 shadow-lg shadow-blue-500/30"
                >
                    <svg
                        class="h-8 w-8 text-white"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"
                        />
                    </svg>
                </div>
            </div>
            <h2
                class="mt-6 text-center text-3xl font-extrabold tracking-tight text-gray-900 dark:text-white"
            >
                Reset password
            </h2>
            <p
                class="mt-2 text-center text-sm text-gray-600 dark:text-gray-400"
            >
                Enter your new password below
            </p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <div
                class="border border-gray-100 bg-white px-4 py-8 shadow-xl shadow-gray-200/50 sm:rounded-2xl sm:px-10 dark:border-gray-700 dark:bg-gray-800 dark:shadow-none"
            >
                <form class="space-y-6" @submit.prevent="submit">
                    <div>
                        <label
                            for="email"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                        >
                            Email address
                        </label>
                        <div class="mt-1">
                            <input
                                id="email"
                                v-model="form.email"
                                type="email"
                                autocomplete="email"
                                readonly
                                required
                                class="block w-full appearance-none rounded-xl border border-gray-300 bg-gray-50 px-3 py-2.5 text-gray-500 shadow-sm sm:text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-400"
                            />
                        </div>
                        <p
                            v-if="form.errors.email"
                            class="mt-2 text-sm text-red-600 dark:text-red-400"
                        >
                            {{ form.errors.email }}
                        </p>
                    </div>

                    <div>
                        <label
                            for="password"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                        >
                            New password
                        </label>
                        <div class="mt-1">
                            <PasswordField
                                id="password"
                                v-model="form.password"
                                autocomplete="new-password"
                                required
                                autofocus
                                class="block w-full appearance-none rounded-xl border border-gray-300 px-3 py-2.5 placeholder-gray-400 shadow-sm transition-colors focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:outline-none sm:text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                                :class="{
                                    'border-red-300 text-red-900 focus:border-red-500 focus:ring-red-500':
                                        form.errors.password,
                                }"
                            />
                        </div>
                        <PasswordRequirements :password="form.password" :min="12" />
                        <p
                            v-if="form.errors.password"
                            class="mt-2 text-sm text-red-600 dark:text-red-400"
                        >
                            {{ form.errors.password }}
                        </p>
                    </div>

                    <div>
                        <label
                            for="password_confirmation"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                        >
                            Confirm new password
                        </label>
                        <div class="mt-1">
                            <PasswordField
                                id="password_confirmation"
                                v-model="form.password_confirmation"
                                autocomplete="new-password"
                                required
                                class="block w-full appearance-none rounded-xl border border-gray-300 px-3 py-2.5 placeholder-gray-400 shadow-sm transition-colors focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:outline-none sm:text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                                :class="{
                                    'border-red-300 text-red-900 focus:border-red-500 focus:ring-red-500':
                                        form.errors.password_confirmation,
                                }"
                            />
                        </div>
                        <p
                            v-if="form.errors.password_confirmation"
                            class="mt-2 text-sm text-red-600 dark:text-red-400"
                        >
                            {{ form.errors.password_confirmation }}
                        </p>
                    </div>

                    <div>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="flex w-full items-center justify-center rounded-xl border border-transparent bg-blue-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition-all duration-200 hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none disabled:cursor-not-allowed disabled:opacity-70"
                        >
                            <svg
                                v-if="form.processing"
                                class="mr-3 -ml-1 h-5 w-5 animate-spin text-white"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                            >
                                <circle
                                    class="opacity-25"
                                    cx="12"
                                    cy="12"
                                    r="10"
                                    stroke="currentColor"
                                    stroke-width="4"
                                ></circle>
                                <path
                                    class="opacity-75"
                                    fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                ></path>
                            </svg>
                            <span v-if="form.processing">Resetting...</span>
                            <span v-else>Reset password</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import PasswordField from '@/components/PasswordField.vue';
import PasswordRequirements from '@/components/PasswordRequirements.vue';

const props = defineProps<{
    token: string;
    email?: string;
}>();

const form = useForm({
    token: props.token,
    email: props.email ?? '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post('/reset-password', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>
