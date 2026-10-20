<template>
    <div class="min-h-screen bg-gray-50 font-sans">
        <!-- Top nav bar -->
        <header
            class="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-gray-200 bg-white px-6 shadow-sm"
        >
            <div class="flex items-center gap-3">
                <button
                    @click="goBack"
                    class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-bold text-black transition-colors hover:bg-gray-100 hover:text-gray-800"
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
                            d="M10 19l-7-7m0 0l7-7m-7 7h18"
                        />
                    </svg>
                    Back to Dashboard
                </button>
            </div>
            <h1 class="text-lg font-black tracking-tight text-black">
                My Profile
            </h1>
            <div
                class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-600 text-sm font-black text-white shadow"
            >
                {{ user.name.charAt(0).toUpperCase() }}
            </div>
        </header>

        <div class="mx-auto max-w-3xl space-y-6 p-8">
            <!-- Success flash -->
            <div
                v-if="flashSuccess"
                class="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-3 text-sm font-bold text-emerald-700"
            >
                <svg
                    class="h-5 w-5 shrink-0"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M5 13l4 4L19 7"
                    />
                </svg>
                {{ flashSuccess }}
            </div>

            <!-- ── PROFILE CARD ────────────────────────────────────── -->
            <div
                class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
            >
                <!-- Avatar header -->
                <div class="flex items-center gap-5 bg-white px-8 py-6">
                    <div
                        class="flex h-20 w-20 items-center justify-center rounded-2xl bg-indigo-500 text-3xl font-black text-white shadow-lg shadow-indigo-500/30"
                    >
                        {{ user.name.charAt(0).toUpperCase() }}
                    </div>
                    <div>
                        <p class="text-xl font-black text-black">
                            {{ user.name }}
                        </p>
                        <p class="mt-0.5 text-sm font-medium text-black">
                            {{ user.email }}
                        </p>
                        <span
                            class="mt-2 inline-block rounded-md border border-indigo-400/30 bg-indigo-500/20 px-2.5 py-0.5 text-xs font-bold tracking-widest text-black uppercase"
                        >
                            {{ user.role }}
                        </span>
                    </div>
                </div>

                <!-- Edit form -->
                <form @submit.prevent="submitProfile" class="space-y-5 p-8">
                    <h3
                        class="text-xs font-extrabold tracking-widest text-black uppercase"
                    >
                        Personal Information
                    </h3>

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <!-- Full Name -->
                        <div class="sm:col-span-2">
                            <label
                                class="mb-1.5 block text-xs font-extrabold tracking-widest text-black uppercase"
                                >Full Name</label
                            >
                            <input
                                type="text"
                                v-model="profileForm.name"
                                class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm font-medium text-black outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                                required
                            />
                            <p
                                v-if="profileForm.errors.name"
                                class="mt-1 text-xs font-bold text-red-500"
                            >
                                {{ profileForm.errors.name }}
                            </p>
                        </div>

                        <!-- Email -->
                        <div class="sm:col-span-2">
                            <label
                                class="mb-1.5 block text-xs font-extrabold tracking-widest text-black uppercase"
                                >Email Address</label
                            >
                            <input
                                type="email"
                                v-model="profileForm.email"
                                class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm font-medium text-black outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                                required
                            />
                            <p
                                v-if="profileForm.errors.email"
                                class="mt-1 text-xs font-bold text-red-500"
                            >
                                {{ profileForm.errors.email }}
                            </p>
                        </div>

                        <!-- Age -->
                        <div>
                            <label
                                class="mb-1.5 block text-xs font-extrabold tracking-widest text-black uppercase"
                                >Age</label
                            >
                            <input
                                type="number"
                                v-model="profileForm.age"
                                min="1"
                                max="130"
                                placeholder="e.g. 35"
                                class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm font-medium text-black outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                            />
                        </div>

                        <!-- Sex -->
                        <div>
                            <label
                                class="mb-1.5 block text-xs font-extrabold tracking-widest text-black uppercase"
                                >Sex</label
                            >
                            <select
                                v-model="profileForm.sex"
                                class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm font-medium text-black outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                            >
                                <option value="">Prefer not to say</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>

                        <!-- Contact -->
                        <div class="sm:col-span-2">
                            <label
                                class="mb-1.5 block text-xs font-extrabold tracking-widest text-black uppercase"
                                >Contact / Mobile</label
                            >
                            <input
                                type="text"
                                v-model="profileForm.contact"
                                placeholder="e.g. +63 912 345 6789"
                                class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm font-medium text-black outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                            />
                        </div>
                    </div>

                    <div class="flex justify-end pt-2">
                        <button
                            type="submit"
                            :disabled="profileForm.processing"
                            class="rounded-xl bg-indigo-600 px-8 py-3 text-sm font-bold text-white shadow-sm transition-all hover:bg-indigo-700 disabled:opacity-50"
                        >
                            {{
                                profileForm.processing
                                    ? 'Saving…'
                                    : 'Save Profile'
                            }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- ── CHANGE PASSWORD CARD ───────────────────────────── -->
            <div
                class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
            >
                <div class="border-b border-gray-100 bg-gray-50/60 px-8 py-5">
                    <h3
                        class="flex items-center gap-2 text-sm font-extrabold tracking-widest text-black uppercase"
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
                                d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                            />
                        </svg>
                        Change Password
                    </h3>
                </div>

                <form @submit.prevent="submitPassword" class="space-y-5 p-8">
                    <div>
                        <label
                            class="mb-1.5 block text-xs font-extrabold tracking-widest text-black uppercase"
                            >Current Password</label
                        >
                        <PasswordField
                            v-model="passwordForm.current_password"
                            class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm font-medium text-black outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                            required
                        />
                        <p
                            v-if="passwordForm.errors.current_password"
                            class="mt-1 text-xs font-bold text-red-500"
                        >
                            {{ passwordForm.errors.current_password }}
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div>
                            <label
                                class="mb-1.5 block text-xs font-extrabold tracking-widest text-black uppercase"
                                >New Password</label
                            >
                            <PasswordField
                                v-model="passwordForm.new_password"
                                class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm font-medium text-black outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                                required
                                minlength="8"
                            />
                            <PasswordRequirements
                                v-if="passwordForm.new_password"
                                :password="passwordForm.new_password"
                                :min="8"
                                :full="false"
                            />
                            <p
                                v-if="passwordForm.errors.new_password"
                                class="mt-1 text-xs font-bold text-red-500"
                            >
                                {{ passwordForm.errors.new_password }}
                            </p>
                        </div>
                        <div>
                            <label
                                class="mb-1.5 block text-xs font-extrabold tracking-widest text-black uppercase"
                                >Confirm New Password</label
                            >
                            <PasswordField
                                v-model="passwordForm.new_password_confirmation"
                                class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm font-medium text-black outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                                required
                            />
                        </div>
                    </div>

                    <div class="flex justify-end pt-2">
                        <button
                            type="submit"
                            :disabled="passwordForm.processing"
                            class="rounded-xl bg-slate-700 px-8 py-3 text-sm font-bold text-white shadow-sm transition-all hover:bg-slate-800 disabled:opacity-50"
                        >
                            {{
                                passwordForm.processing
                                    ? 'Updating…'
                                    : 'Update Password'
                            }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { useForm, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import PasswordField from '@/components/PasswordField.vue';
import PasswordRequirements from '@/components/PasswordRequirements.vue';

interface User {
    name: string;
    email: string;
    age?: string;
    sex?: string;
    contact?: string;
    role?: string;
}

interface Flash {
    success?: string;
}

const page = usePage();

const props = defineProps<{
    user: User;
}>();

// Type-safe access to flash messages
const flashSuccess = computed(() => {
    return (page.props.flash as Flash | null)?.success;
});

// Profile info form — pre-filled with current user data
const profileForm = useForm({
    name: props.user.name ?? '',
    email: props.user.email ?? '',
    age: props.user.age ?? '',
    sex: props.user.sex ?? '',
    contact: props.user.contact ?? '',
});

// Password change form
const passwordForm = useForm({
    current_password: '',
    new_password: '',
    new_password_confirmation: '',
});

const submitProfile = () => {
    profileForm.put('/profile', {
        preserveScroll: true,
        onSuccess: () => profileForm.reset(), // keep values, just clear error state
    });
};

const submitPassword = () => {
    passwordForm.put('/profile/password', {
        preserveScroll: true,
        onSuccess: () => passwordForm.reset(),
    });
};

// Navigate back to the role-appropriate dashboard
const goBack = () => {
    const role = props.user.role || 'patient';
    const routes: Record<string, string> = {
        admin: '/admin/dashboard',
        doctor: '/doctor/dashboard',
        receptionist: '/receptionist/dashboard',
        patient: '/patient/dashboard',
    };
    router.visit(routes[role] ?? '/');
};
</script>
