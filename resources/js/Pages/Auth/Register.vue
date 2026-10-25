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
                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                        />
                    </svg>
                </div>
            </div>
            <h2
                class="mt-6 text-center text-3xl font-extrabold tracking-tight text-gray-900 dark:text-white"
            >
                Create an account
            </h2>
            <p
                class="mt-2 text-center text-sm text-gray-600 dark:text-gray-400"
            >
                Enter your details below to create your account
            </p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <div
                class="border border-gray-100 bg-white px-4 py-8 shadow-xl shadow-gray-200/50 sm:rounded-2xl sm:px-10 dark:border-gray-700 dark:bg-gray-800 dark:shadow-none"
            >
                <form class="space-y-6" @submit.prevent="submit">
                    <div>
                        <label
                            for="name"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                        >
                            Name
                        </label>
                        <div class="mt-1">
                            <input
                                id="name"
                                v-model="form.name"
                                type="text"
                                autocomplete="name"
                                required
                                autofocus
                                placeholder="Full name"
                                class="block w-full appearance-none rounded-xl border border-gray-300 px-3 py-2.5 placeholder-gray-400 shadow-sm transition-colors focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:outline-none sm:text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                                :class="{
                                    'border-red-300 text-red-900 focus:border-red-500 focus:ring-red-500':
                                        form.errors.name,
                                }"
                            />
                        </div>
                        <p
                            v-if="form.errors.name"
                            class="mt-2 text-sm text-red-600 dark:text-red-400"
                        >
                            {{ form.errors.name }}
                        </p>
                    </div>

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
                                required
                                placeholder="email@example.com"
                                class="block w-full appearance-none rounded-xl border border-gray-300 px-3 py-2.5 placeholder-gray-400 shadow-sm transition-colors focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:outline-none sm:text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                                :class="{
                                    'border-red-300 text-red-900 focus:border-red-500 focus:ring-red-500':
                                        form.errors.email,
                                }"
                            />
                        </div>
                        <p
                            v-if="form.errors.email"
                            class="mt-2 text-sm text-red-600 dark:text-red-400"
                        >
                            {{ form.errors.email }}
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label
                                for="date_of_birth"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >
                                Date of Birth
                            </label>
                            <div class="mt-1">
                                <input
                                    id="date_of_birth"
                                    v-model="form.date_of_birth"
                                    type="date"
                                    class="block w-full appearance-none rounded-xl border border-gray-300 px-3 py-2.5 placeholder-gray-400 shadow-sm transition-colors focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:outline-none sm:text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                                    :class="{
                                        'border-red-300 text-red-900 focus:border-red-500 focus:ring-red-500':
                                            form.errors.date_of_birth,
                                    }"
                                />
                            </div>
                            <p
                                v-if="form.errors.date_of_birth"
                                class="mt-2 text-sm text-red-600 dark:text-red-400"
                            >
                                {{ form.errors.date_of_birth }}
                            </p>
                        </div>
                        <div>
                            <label
                                for="sex"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >
                                Sex
                            </label>
                            <div class="mt-1">
                                <select
                                    id="sex"
                                    v-model="form.sex"
                                    class="block w-full appearance-none rounded-xl border border-gray-300 px-3 py-2.5 text-gray-900 shadow-sm transition-colors focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:outline-none sm:text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                                    :class="{
                                        'border-red-300 text-red-900 focus:border-red-500 focus:ring-red-500':
                                            form.errors.sex,
                                    }"
                                >
                                    <option value="">Select...</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                            </div>
                            <p
                                v-if="form.errors.sex"
                                class="mt-2 text-sm text-red-600 dark:text-red-400"
                            >
                                {{ form.errors.sex }}
                            </p>
                        </div>
                    </div>

                    <div>
                        <label
                            for="password"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                        >
                            Password
                        </label>
                        <div class="mt-1">
                            <PasswordField
                                id="password"
                                v-model="form.password"
                                autocomplete="new-password"
                                required
                                placeholder="Password"
                                class="block w-full appearance-none rounded-xl border border-gray-300 px-3 py-2.5 placeholder-gray-400 shadow-sm transition-colors focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:outline-none sm:text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                            />
                        </div>
                        <PasswordRequirements
                            :password="form.password"
                            :min="12"
                        />
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
                            Confirm password
                        </label>
                        <div class="mt-1">
                            <PasswordField
                                id="password_confirmation"
                                v-model="form.password_confirmation"
                                autocomplete="new-password"
                                required
                                placeholder="Confirm password"
                                class="block w-full appearance-none rounded-xl border border-gray-300 px-3 py-2.5 placeholder-gray-400 shadow-sm transition-colors focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:outline-none sm:text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                            />
                        </div>
                        <p
                            v-if="form.errors.password_confirmation"
                            class="mt-2 text-sm text-red-600 dark:text-red-400"
                        >
                            {{ form.errors.password_confirmation }}
                        </p>
                    </div>

                    <div
                        class="border-t border-gray-100 pt-6 dark:border-gray-700"
                    >
                        <div class="flex items-start">
                            <div class="flex h-5 shrink-0 items-center">
                                <input
                                    id="terms_accepted"
                                    v-model="form.terms_accepted"
                                    type="checkbox"
                                    required
                                    class="h-4 w-4 rounded border-gray-300 text-blue-600 transition-colors focus:ring-2 focus:ring-blue-500 focus:ring-offset-0 dark:border-gray-600 dark:bg-gray-900"
                                    :class="{
                                        'border-red-300 focus:ring-red-500':
                                            form.errors.terms_accepted,
                                    }"
                                />
                            </div>
                            <div class="ml-3 text-sm">
                                <label
                                    for="terms_accepted"
                                    class="text-gray-700 dark:text-gray-300"
                                >
                                    I agree to the
                                    <a
                                        :href="terms.url"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="font-medium text-blue-600 underline transition-colors hover:text-blue-500 dark:text-blue-400"
                                        >{{ terms.title }}</a
                                    >
                                    <span class="text-red-500">*</span>
                                </label>
                                <p
                                    class="mt-1 text-xs text-gray-500 dark:text-gray-400"
                                >
                                    You must accept before creating an account.
                                </p>
                                <p
                                    v-if="form.errors.terms_accepted"
                                    class="mt-2 text-sm text-red-600 dark:text-red-400"
                                >
                                    {{ form.errors.terms_accepted }}
                                </p>
                            </div>
                        </div>
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
                            <span v-if="form.processing"
                                >Creating account...</span
                            >
                            <span v-else>Create account</span>
                        </button>
                    </div>

                    <div
                        class="text-center text-sm text-gray-600 dark:text-gray-400"
                    >
                        Already have an account?
                        <Link
                            href="/login"
                            class="font-medium text-blue-600 transition-colors hover:text-blue-500 dark:text-blue-400"
                        >
                            Log in
                        </Link>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import PasswordField from '@/components/PasswordField.vue';
import PasswordRequirements from '@/components/PasswordRequirements.vue';

interface TermsMeta {
    title: string;
    version: string;
    effective_date: string | null;
    url: string;
}

defineProps<{ terms: TermsMeta }>();

const form = useForm({
    name: '',
    email: '',
    date_of_birth: '',
    sex: '',
    password: '',
    password_confirmation: '',
    // Required, deliberately unchecked by default: Section 1.2 of the
    // Agreement treats this checkbox as the act that binds the patient.
    terms_accepted: false,
});

const submit = () => {
    form.post('/register', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>
