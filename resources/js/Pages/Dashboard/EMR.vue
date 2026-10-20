<template>
    <div
        class="flex h-screen bg-[#f4f7fa] font-sans selection:bg-[#15818c] selection:text-white"
    >
        <!-- SIDEBAR -->
        <!-- Mobile backdrop -->
        <div
            v-if="isMobileOpen"
            class="fixed inset-0 z-40 bg-black/50 md:hidden"
            @click="closeMobile"
        ></div>

        <aside
            class="fixed inset-y-0 left-0 z-50 flex shrink-0 flex-col bg-[#146b8a] text-white shadow-xl transition-[width,transform] duration-300 md:static md:z-20 md:flex"
            :class="[
                isMobileOpen
                    ? 'translate-x-0'
                    : '-translate-x-full md:translate-x-0',
                isOpen ? 'md:w-64' : 'md:w-0 md:overflow-hidden',
            ]"
        >
            <div class="flex h-full w-64 shrink-0 flex-col">
                <div
                    class="flex h-20 items-center border-b border-[#1b7f9e] bg-[#0e5c78] px-6"
                >
                    <div class="flex-1">
                        <h1
                            class="text-lg leading-tight font-bold tracking-wide"
                        >
                            CardioCare
                        </h1>
                        <p
                            class="text-[10px] tracking-widest text-teal-100 uppercase"
                        >
                            Healthcare Portal
                        </p>
                    </div>
                    <button
                        type="button"
                        class="ml-2 hidden rounded-md p-1.5 text-teal-100 transition-colors hover:bg-[#1a7f9e] hover:text-white md:inline-flex"
                        @click="toggleDesktop"
                        aria-label="Toggle sidebar"
                    >
                        <svg
                            class="h-5 w-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16"
                            />
                        </svg>
                    </button>
                </div>
                <nav class="flex-1 overflow-y-auto py-6">
                    <div
                        class="mb-2 px-6 text-xs font-semibold tracking-wider text-teal-200 uppercase"
                    >
                        Clinical Workflow
                    </div>
                    <ul class="space-y-1">
                        <li>
                            <Link
                                href="/doctor/dashboard"
                                class="flex items-center border-l-4 border-transparent px-6 py-3 text-teal-100 transition-colors hover:bg-[#1a7f9e] hover:text-white"
                                @click="closeMobile"
                            >
                                <svg
                                    class="mr-3 h-5 w-5"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                                    />
                                </svg>
                                Consultation Dashboard
                            </Link>
                        </li>
                        <li>
                            <Link
                                href="/doctor/emr"
                                class="flex items-center border-l-4 border-white bg-[#11888a] px-6 py-3 font-medium text-white shadow-inner transition-colors"
                                @click="closeMobile"
                            >
                                <svg
                                    class="mr-3 h-5 w-5"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                    />
                                </svg>
                                Patient Records (EMR)
                            </Link>
                        </li>
                        <!-- NEW: Monthly Narrative Report -->
                        <li>
                            <Link
                                href="/doctor/reports/monthly"
                                class="flex items-center px-6 py-3 text-teal-100 transition-colors hover:bg-[#1a7f9e] hover:text-white"
                                :class="{
                                    'border-l-4 border-white bg-[#11888a] font-medium text-white shadow-inner':
                                        $page.url === '/doctor/reports/monthly',
                                }"
                                @click="closeMobile"
                            >
                                <svg
                                    class="mr-3 h-5 w-5"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                    />
                                </svg>
                                Monthly Narrative Report
                            </Link>
                        </li>
                    </ul>
                </nav>

                <div class="border-t border-[#1b7f9e] p-4">
                    <LogoutButton>
                        <button
                            type="button"
                            class="flex w-full items-center rounded-lg px-4 py-2 text-sm font-medium text-teal-100 transition-colors hover:bg-[#1a7f9e] hover:text-white"
                        >
                            <svg
                                class="mr-3 h-5 w-5 rotate-180"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
                                />
                            </svg>
                            Sign Out
                        </button>
                    </LogoutButton>
                </div>
            </div>
        </aside>

        <!-- MAIN CONTENT AREA -->
        <main
            class="flex h-screen flex-1 flex-col overflow-hidden bg-[#f8fafc]"
        >
            <header
                class="z-10 flex h-20 items-center justify-between border-b border-gray-200 bg-white px-4 shadow-sm sm:px-6 lg:px-8"
            >
                <div class="flex items-center gap-3">
                    <button
                        v-if="!isSidebarOpen"
                        type="button"
                        class="inline-flex items-center justify-center rounded-md p-2 text-gray-600 transition-colors hover:bg-gray-100 hover:text-gray-900"
                        @click="toggle"
                        aria-label="Open sidebar"
                    >
                        <svg
                            class="h-5 w-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16"
                            />
                        </svg>
                    </button>
                    <h2 class="text-xl font-bold text-gray-800">
                        Electronic Medical Records
                    </h2>
                </div>
                <div class="flex items-center gap-6">
                    <button
                        class="relative text-gray-400 transition-colors hover:text-gray-600"
                    >
                        <svg
                            class="h-6 w-6"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
                            />
                        </svg>
                        <span
                            class="absolute top-0 right-0 block h-2.5 w-2.5 rounded-full bg-red-500 ring-2 ring-white"
                        ></span>
                    </button>
                    <div
                        class="flex items-center gap-3 border-l border-gray-200 pl-6"
                    >
                        <div class="text-right">
                            <p class="text-sm font-bold text-gray-900">
                                {{ user.name }}
                            </p>
                            <p
                                class="text-xs font-semibold tracking-wider text-gray-500 uppercase"
                            >
                                Physician
                            </p>
                        </div>
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-full bg-[#146b8a] font-bold text-white shadow-sm"
                        >
                            {{ user.name.replace('Dr. ', '').charAt(0) }}
                        </div>
                    </div>
                </div>
            </header>

            <div class="flex-1 overflow-y-auto p-8">
                <div class="mx-auto max-w-7xl space-y-6">
                    <div
                        class="mb-2 flex flex-col justify-between gap-4 md:flex-row md:items-center"
                    >
                        <div>
                            <h2
                                class="text-2xl font-black tracking-tight text-gray-800"
                            >
                                Global Patient Registry
                            </h2>
                            <p class="mt-1 text-sm font-medium text-gray-500">
                                Centralized registry of all patients and their
                                latest records.
                            </p>
                        </div>

                        <!-- REAL-TIME SEARCH BAR -->
                        <div class="relative w-full md:w-80">
                            <div
                                class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3"
                            >
                                <svg
                                    class="h-5 w-5 text-gray-400"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                    />
                                </svg>
                            </div>
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search by name or ID..."
                                class="w-full rounded-xl border border-gray-200 bg-white py-2.5 pr-4 pl-10 text-sm font-medium text-gray-800 shadow-sm transition-all focus:border-[#146b8a] focus:ring-2 focus:ring-[#146b8a]/20 focus:outline-none"
                            />
                        </div>
                    </div>

                    <div
                        class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm"
                    >
                        <div class="overflow-x-auto">
                            <table class="min-w-full border-collapse text-left">
                                <thead>
                                    <tr class="border-b border-gray-100">
                                        <th
                                            class="px-6 py-5 text-xs font-extrabold tracking-widest text-gray-400 uppercase"
                                        >
                                            Patient
                                        </th>
                                        <th
                                            class="px-6 py-5 text-xs font-extrabold tracking-widest text-gray-400 uppercase"
                                        >
                                            Consultation Type
                                        </th>
                                        <th
                                            class="px-6 py-5 text-xs font-extrabold tracking-widest text-gray-400 uppercase"
                                        >
                                            Last Visit
                                        </th>
                                        <th
                                            class="px-6 py-5 text-xs font-extrabold tracking-widest text-gray-400 uppercase"
                                        >
                                            App Status
                                        </th>
                                        <th
                                            class="px-6 py-5 text-right text-xs font-extrabold tracking-widest text-gray-400 uppercase"
                                        >
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    <tr
                                        v-for="patient in filteredPatients"
                                        :key="patient.id"
                                        class="group transition-colors hover:bg-gray-50/80"
                                    >
                                        <td class="px-6 py-5 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div
                                                    class="flex h-10 w-10 items-center justify-center rounded-lg bg-[#146b8a] text-lg font-bold text-white shadow-sm"
                                                >
                                                    {{ patient.name.charAt(0) }}
                                                </div>
                                                <div class="ml-4">
                                                    <div
                                                        class="text-sm font-bold text-gray-900"
                                                    >
                                                        {{ patient.name }}
                                                    </div>
                                                    <div
                                                        class="text-xs font-medium tracking-widest text-gray-500 uppercase"
                                                    >
                                                        ID: #{{ patient.id }}
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td
                                            class="px-6 py-5 text-sm font-bold whitespace-nowrap text-gray-800"
                                        >
                                            {{ patient.consultation_type }}
                                        </td>
                                        <td
                                            class="px-6 py-5 text-sm font-medium whitespace-nowrap text-gray-500"
                                        >
                                            {{ patient.last_visit }}
                                        </td>
                                        <td class="px-6 py-5 whitespace-nowrap">
                                            <span
                                                class="text-xs font-medium text-gray-400 italic"
                                                >{{ patient.app_status }}</span
                                            >
                                        </td>
                                        <td
                                            class="px-6 py-5 text-right whitespace-nowrap"
                                        >
                                            <Link
                                                :href="`/doctor/patients/${patient.id}/history`"
                                                class="inline-flex items-center gap-1.5 rounded-lg bg-[#146b8a] px-4 py-2 text-sm font-bold text-white shadow-sm transition-all hover:bg-[#0e5c78] hover:shadow-md active:scale-95"
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
                                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                                    />
                                                </svg>
                                                View History
                                            </Link>
                                        </td>
                                    </tr>
                                    <tr v-if="filteredPatients.length === 0">
                                        <td
                                            colspan="5"
                                            class="px-6 py-16 text-center"
                                        >
                                            <svg
                                                class="mx-auto mb-3 h-12 w-12 text-gray-300"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                                />
                                            </svg>
                                            <p
                                                class="text-base font-bold text-gray-600"
                                            >
                                                No patients found
                                            </p>
                                            <p
                                                class="mt-1 text-sm text-gray-400"
                                            >
                                                Try adjusting your search
                                                criteria.
                                            </p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import LogoutButton from '@/components/LogoutButton.vue';
import { useSidebar } from '@/composables/useSidebar';

const {
    isOpen,
    isMobileOpen,
    isSidebarOpen,
    toggleDesktop,
    toggle,
    closeMobile,
} = useSidebar('doctor');

// Type definitions
interface Patient {
    id: string | number;
    name: string;
    consultation_type: string;
    last_visit: string;
    app_status: string;
}

interface User {
    id: string | number;
    name: string;
    email: string;
}

const props = defineProps<{
    user: User;
    patients: Patient[];
}>();

const searchQuery = ref<string>('');

const filteredPatients = computed((): Patient[] => {
    if (!searchQuery.value) {
        return props.patients;
    }

    const lowerCaseQuery = searchQuery.value.toLowerCase();

    return props.patients.filter((patient: Patient): boolean => {
        return (
            patient.name.toLowerCase().includes(lowerCaseQuery) ||
            String(patient.id).includes(lowerCaseQuery)
        );
    });
});
</script>
