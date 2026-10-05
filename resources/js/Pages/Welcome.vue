<template>
    <div
        class="flex min-h-screen flex-col bg-[#f8fafc] font-sans selection:bg-[#005bb5] selection:text-white"
    >
        <!-- Premium Navigation Bar -->
        <header class="sticky top-0 z-50 bg-[#005bb5] text-white shadow-md">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between">
                    <!-- Brand Logo & Title -->
                    <div class="flex items-center gap-3">
                        <!--
                            The logo is an opaque rectangle (no alpha channel), so on
                            this blue bar it needs its own white plate or it reads as an
                            unfinished white block. The larger slot keeps the detail
                            legible, which a 20px icon would not.
                        -->
                        <span
                            class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-black/5"
                        >
                            <img
                                src="/logo.png"
                                alt="Cardiovascular Care Center logo"
                                class="h-full w-full object-contain"
                            />
                        </span>
                        <span class="text-xl font-bold tracking-wide">
                            Cardiovascular Care Center</span
                        >
                    </div>

                    <!-- SMART Portal Login Button -->
                    <div>
                        <button
                            @click="handleNavigation"
                            :disabled="isNavigating"
                            :class="
                                isAuthenticated
                                    ? 'bg-emerald-50 text-emerald-700 ring-emerald-500 hover:bg-emerald-100'
                                    : 'bg-white text-[#005bb5] ring-[#005bb5] hover:bg-gray-50'
                            "
                            class="inline-flex items-center justify-center rounded-lg px-5 py-2 text-sm font-semibold shadow-sm transition-all duration-200 focus:ring-2 focus:ring-offset-2 focus:ring-offset-[#005bb5] focus:outline-none disabled:cursor-not-allowed disabled:opacity-75"
                        >
                            <svg
                                v-if="isNavigating"
                                class="mr-2 -ml-1 h-4 w-4 animate-spin"
                                :class="
                                    isAuthenticated
                                        ? 'text-emerald-700'
                                        : 'text-[#005bb5]'
                                "
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

                            <!-- Dynamic Text based on Auth State -->
                            <span v-if="isNavigating">Connecting...</span>
                            <span v-else-if="isAuthenticated"
                                >Return to Dashboard &rarr;</span
                            >
                            <span v-else>Portal Login</span>
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Hero Section -->
        <main class="relative flex grow items-center overflow-hidden">
            <!-- Decorative background elements -->
            <div
                class="pointer-events-none absolute top-0 left-1/2 h-125 w-250 -translate-x-1/2 opacity-20"
            >
                <div
                    class="absolute inset-0 -translate-y-1/2 transform rounded-full bg-linear-to-b from-[#005bb5] to-transparent blur-3xl"
                ></div>
            </div>

            <div
                class="relative z-10 mx-auto w-full max-w-7xl px-4 py-12 sm:px-6 lg:px-8 lg:py-20"
            >
                <div
                    class="grid grid-cols-1 items-center gap-12 lg:grid-cols-2 lg:gap-8"
                >
                    <!-- Left Column: Text Content & Location -->
                    <div class="flex flex-col justify-center space-y-8">
                        <div class="space-y-4">
                            <h1
                                class="text-5xl leading-tight font-extrabold tracking-tight text-gray-900 lg:text-6xl"
                            >
                                Cardiovascular Care Center <br />
                                <span class="text-[#00abce]"
                                    >Within Your Reach.</span
                                >
                            </h1>
                            <p
                                class="max-w-xl text-lg leading-relaxed text-gray-600"
                            >
                                Located in the heart of Manila, we provide
                                state-of-the-art cardiovascular diagnostics,
                                automated patient tracking, and AI-driven
                                medical insights tailored to your health.
                            </p>
                        </div>

                        <!-- Location Card -->
                        <div
                            class="max-w-lg rounded-l-sm rounded-r-xl border-l-4 border-[#005bb5] bg-white p-6 shadow-md transition-shadow duration-300 hover:shadow-lg"
                        >
                            <div class="flex items-start gap-4">
                                <div class="mt-1 shrink-0">
                                    <svg
                                        class="h-6 w-6 text-red-500"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.243-4.243a8 8 0 1111.314 0z"
                                        />
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
                                        />
                                    </svg>
                                </div>
                                <div>
                                    <h3
                                        class="mb-1 text-lg font-semibold text-gray-900"
                                    >
                                        Location Details
                                    </h3>
                                    <address
                                        class="text-sm leading-relaxed text-gray-600 not-italic"
                                    >
                                        <span class="font-medium text-gray-800"
                                            >Mirasol Building, 3rd Floor</span
                                        ><br />
                                        854 Taft Ave, Ermita<br />
                                        Manila, 1004 Metro Manila<br />
                                        Philippines
                                    </address>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Building Image -->
                    <div
                        class="relative w-full max-w-lg lg:ml-auto lg:max-w-none"
                    >
                        <!-- Decorative offset background frame -->
                        <div
                            class="absolute inset-0 -z-10 translate-x-4 translate-y-4 transform rounded-3xl bg-[#e0e7ff] shadow-lg"
                        ></div>

                        <!-- Main Image -->
                        <div
                            class="group relative overflow-hidden rounded-3xl border-4 border-white bg-white shadow-xl"
                        >
                            <!-- Overlay gradient to make the image pop -->
                            <div
                                class="absolute inset-0 z-10 bg-linear-to-t from-black/20 to-transparent opacity-0 transition-opacity duration-300 group-hover:opacity-100"
                            ></div>

                            <img
                                src="/assets/building.jpg"
                                alt="Mirasol Building - Cardiovascular Care Center"
                                class="h-auto w-full transform object-cover transition-transform duration-700 ease-out group-hover:scale-105"
                                style="max-height: 500px"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>

<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

// Receive the authentication state from routes/web.php
const props = defineProps({
    isAuthenticated: {
        type: Boolean,
        default: false,
    },
    userRole: {
        type: String,
        default: null,
    },
});

const isNavigating = ref(false);

const handleNavigation = () => {
    if (isNavigating.value) {
return;
}

    isNavigating.value = true;

    // If the user is already logged in, route them straight to their specific dashboard!
    if (props.isAuthenticated) {
        let dashboardRoute = '/patient/dashboard'; // Fallback

        if (props.userRole === 'doctor') {
dashboardRoute = '/doctor/dashboard';
}

        if (props.userRole === 'receptionist' || props.userRole === 'admin') {
dashboardRoute = '/receptionist/dashboard';
}

        router.get(
            dashboardRoute,
            {},
            {
                onFinish: () => {
                    isNavigating.value = false;
                },
            },
        );
    } else {
        router.get(
            '/login',
            {},
            {
                onFinish: () => {
                    isNavigating.value = false;
                },
            },
        );
    }
};
</script>
