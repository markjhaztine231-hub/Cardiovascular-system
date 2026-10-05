<template>
    <div
        class="flex min-h-screen font-sans"
        style="font-family: 'Segoe UI', sans-serif"
    >
        <!-- Left Side: Branding -->
        <div
            class="relative hidden w-1/2 flex-col items-center justify-center overflow-hidden bg-linear-to-br from-[#0056b3] to-[#17a2b8] p-12 text-white lg:flex"
        >
            <div
                class="absolute inset-0 opacity-10"
                style="
                    background-image: radial-gradient(
                        circle at 2px 2px,
                        white 1px,
                        transparent 0
                    );
                    background-size: 32px 32px;
                "
            ></div>
            <div class="z-10 space-y-6 text-center">
                <div
                    class="mx-auto mb-6 flex h-24 w-24 items-center justify-center rounded-full bg-white text-4xl text-[#0056b3] shadow-2xl"
                >
                    ♥
                </div>
                <h1 class="text-4xl font-bold">Cardiovascular Care Center</h1>
                <p class="max-w-md text-lg opacity-90">
                    Secure, AI-Integrated Healthcare Management System.
                </p>
            </div>
        </div>

        <!-- Right Side: Auth Form -->
        <div
            class="relative flex w-full flex-col items-center justify-center bg-white p-8 lg:w-1/2"
        >
            <div class="w-full max-w-md space-y-8">
                <div class="text-center">
                    <h2 class="text-3xl font-bold text-[#2c3e50]">
                        {{
                            isRegistering
                                ? 'Patient Registration'
                                : 'System Login'
                        }}
                    </h2>
                    <p class="mt-2 text-gray-500">
                        Please authenticate to access the portal.
                    </p>
                </div>

                <form @submit.prevent="submit" class="space-y-5">
                    <div v-if="isRegistering">
                        <label
                            class="mb-1 block text-sm font-semibold text-[#2c3e50]"
                            >Full Name</label
                        >
                        <input
                            v-model="form.name"
                            type="text"
                            class="w-full rounded-lg border px-4 py-2 outline-none focus:ring-2 focus:ring-[#0056b3]"
                            required
                        />
                    </div>

                    <div>
                        <label
                            class="mb-1 block text-sm font-semibold text-[#2c3e50]"
                            >Username / Email</label
                        >
                        <input
                            v-model="form.email"
                            type="email"
                            class="w-full rounded-lg border px-4 py-2 outline-none focus:ring-2 focus:ring-[#0056b3]"
                            required
                        />
                    </div>

                    <div>
                        <label
                            class="mb-1 block text-sm font-semibold text-[#2c3e50]"
                            >Password</label
                        >
                        <input
                            v-model="form.password"
                            type="password"
                            class="w-full rounded-lg border px-4 py-2 outline-none focus:ring-2 focus:ring-[#0056b3]"
                            required
                        />
                    </div>

                    <div v-if="!isRegistering">
                        <label
                            class="mb-1 block text-sm font-semibold text-[#2c3e50]"
                            >Access Role</label
                        >
                        <select
                            v-model="form.role"
                            class="w-full rounded-lg border bg-white px-4 py-2 outline-none focus:ring-2 focus:ring-[#0056b3]"
                        >
                            <option value="patient">Patient / Resident</option>
                            <option value="receptionist">
                                Admin / Head Nurse
                            </option>
                            <option value="doctor">Clinical Doctor</option>
                        </select>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full rounded-lg bg-[#0056b3] py-3 font-bold text-white shadow-md transition-colors hover:bg-blue-800 disabled:opacity-50"
                    >
                        {{
                            form.processing
                                ? 'Authenticating...'
                                : isRegistering
                                  ? 'Register Account'
                                  : 'Secure Login'
                        }}
                    </button>
                </form>

                <div class="mt-4 text-center">
                    <button
                        @click="isRegistering = !isRegistering"
                        class="font-semibold text-[#17a2b8] hover:underline"
                    >
                        {{
                            isRegistering
                                ? 'Already have an account? Login'
                                : 'New patient? Register here'
                        }}
                    </button>
                </div>
            </div>

            <!-- SECURITY: Privacy Disclaimer -->
            <div
                class="absolute bottom-6 mx-auto max-w-xs text-center text-xs text-gray-400"
            >
                🔒 Compliant with Data Privacy Act of 2012. Unauthorized access
                is strictly prohibited and monitored.
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const isRegistering = ref(false);

const form = useForm({
    name: '',
    email: '',
    password: '',
    role: 'patient',
});

const submit = () => {
    // SECURITY: Utilizing Inertia useForm. Protects against CSRF out of the box.
    form.post('/login', {
        preserveScroll: true,
    });
};
</script>
