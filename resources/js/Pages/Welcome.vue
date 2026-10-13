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
        <main
            class="group relative flex grow items-center overflow-hidden lg:min-h-[640px]"
        >
            <!-- Full-bleed building photo as the hero background -->
            <div class="absolute inset-0">
                <img
                    src="/assets/building.jpg"
                    alt="Mirasol Building – home of Cardiovascular Care Center"
                    class="h-full w-full transform object-cover object-center transition-transform duration-700 ease-out group-hover:scale-105"
                />
                <!-- Legibility overlays so the headline stays readable -->
                <div
                    class="absolute inset-0 bg-linear-to-r from-[#003361]/95 via-[#005bb5]/75 to-[#00abce]/20"
                ></div>
                <div
                    class="absolute inset-0 bg-linear-to-t from-black/55 via-transparent to-black/25"
                ></div>
            </div>

            <div
                class="relative z-10 mx-auto w-full max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-24"
            >
                <div class="flex flex-col gap-10 lg:max-w-2xl lg:gap-12">
                    <div class="space-y-5">
                        <span
                            class="inline-flex items-center rounded-full bg-white/15 px-4 py-1.5 text-xs font-bold tracking-widest text-white uppercase ring-1 ring-white/30 backdrop-blur-sm"
                        >
                            Heart diagnostics · Patient tracking · AI insights
                        </span>

                        <h1
                            class="text-5xl leading-tight font-extrabold tracking-tight text-white lg:text-6xl"
                        >
                            Cardiovascular Care Center <br />
                            <span class="text-[#7ee8ff]"
                                >Within Your Reach.</span
                            >
                        </h1>

                        <p
                            class="max-w-xl text-lg leading-relaxed text-white/90"
                        >
                            Located in the heart of Manila, we provide
                            state-of-the-art cardiovascular diagnostics,
                            automated patient tracking, and AI-driven medical
                            insights tailored to your health.
                        </p>
                    </div>

                    <!-- Primary Call-to-Action -->
                    <button
                        @click="handleNavigation"
                        :disabled="isNavigating"
                        class="inline-flex w-fit items-center justify-center rounded-lg bg-white px-7 py-3.5 text-sm font-bold text-[#005bb5] shadow-lg transition-all duration-200 hover:bg-gray-50 focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-[#005bb5] focus:outline-none disabled:cursor-not-allowed disabled:opacity-75"
                    >
                        <svg
                            v-if="isNavigating"
                            class="mr-2 -ml-1 h-4 w-4 animate-spin text-[#005bb5]"
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

                        <span v-if="isNavigating">Connecting...</span>
                        <span v-else-if="isAuthenticated"
                            >Return to Dashboard &rarr;</span
                        >
                        <span v-else>Portal Login</span>
                    </button>

                    <!-- Location Card (frosted glass) -->
                    <div
                        class="max-w-lg rounded-xl border border-white/25 bg-white/10 p-6 shadow-lg backdrop-blur-md"
                    >
                        <div class="flex items-start gap-4">
                            <div class="mt-1 shrink-0">
                                <svg
                                    class="h-6 w-6 text-red-400"
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
                                    class="mb-1 text-lg font-semibold text-white"
                                >
                                    Location Details
                                </h3>
                                <address
                                    class="text-sm leading-relaxed text-white/85 not-italic"
                                >
                                    <span class="font-medium text-white"
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
