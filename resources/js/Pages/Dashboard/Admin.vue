<template>
    <div class="flex h-screen bg-[#f1f5f9] font-sans selection:bg-indigo-500 selection:text-white">
        <AdminSidebar />

        <main class="flex h-screen flex-1 flex-col overflow-hidden">
            <header class="flex h-20 shrink-0 items-center justify-between border-b border-gray-200 bg-white px-8 shadow-sm">
                <h2 class="text-xl font-black tracking-tight text-slate-800">System Administration</h2>
                <div class="flex items-center gap-4">
                    <span class="rounded-lg border border-indigo-100 bg-indigo-50 px-3 py-1.5 text-xs font-extrabold tracking-widest text-indigo-700 uppercase">Super Admin</span>
                    <Link href="/profile" class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-600 font-bold text-white shadow-md transition-colors hover:bg-indigo-700" :title="user.name">
                        {{ user.name.charAt(0) }}
                    </Link>
                </div>
            </header>

            <div class="relative flex-1 overflow-y-auto p-8">
                <div class="mx-auto max-w-7xl space-y-8">

                    <!-- Stats Row 1 — System-wide -->
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                            <p class="text-xs font-bold tracking-widest text-gray-500 uppercase">Total Users</p>
                            <p class="mt-2 text-4xl font-black text-slate-800">{{ stats.total_users }}</p>
                        </div>
                        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                            <p class="text-xs font-bold tracking-widest text-gray-500 uppercase">Cardiologists</p>
                            <p class="mt-2 text-4xl font-black text-emerald-600">{{ stats.total_doctors }}</p>
                        </div>
                        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                            <p class="text-xs font-bold tracking-widest text-gray-500 uppercase">Reception Staff</p>
                            <p class="mt-2 text-4xl font-black text-blue-600">{{ stats.total_staff }}</p>
                        </div>
                        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                            <p class="text-xs font-bold tracking-widest text-gray-500 uppercase">Registered Patients</p>
                            <p class="mt-2 text-4xl font-black text-indigo-600">{{ stats.total_patients }}</p>
                        </div>
                    </div>

                    <!-- Stats Row 2 — Today's Pulse & Monthly -->
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="rounded-2xl border border-sky-200 bg-sky-50 p-6 shadow-sm">
                            <p class="text-xs font-bold tracking-widest text-sky-700 uppercase">Appointments Today</p>
                            <p class="mt-2 text-4xl font-black text-sky-800">{{ stats.appointments_today }}</p>
                        </div>
                        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-6 shadow-sm">
                            <p class="text-xs font-bold tracking-widest text-emerald-700 uppercase">Completed</p>
                            <p class="mt-2 text-4xl font-black text-emerald-800">{{ stats.completed_today }}</p>
                        </div>
                        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-6 shadow-sm">
                            <p class="text-xs font-bold tracking-widest text-amber-700 uppercase">Scheduled / Pending</p>
                            <p class="mt-2 text-4xl font-black text-amber-800">{{ stats.pending_today }}</p>
                        </div>
                        <div class="rounded-2xl border border-red-200 bg-red-50 p-6 shadow-sm">
                            <p class="text-xs font-bold tracking-widest text-red-700 uppercase">No-Shows Today</p>
                            <p class="mt-2 text-4xl font-black text-red-800">{{ stats.no_shows_today }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
                        <!-- Lifetime & Monthly KPIs -->
                        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                            <p class="text-xs font-bold tracking-widest text-gray-500 uppercase">All-Time Appointments</p>
                            <p class="mt-2 text-4xl font-black text-slate-800">{{ stats.total_appointments }}</p>
                            <div class="mt-4 flex items-center gap-2 text-sm">
                                <span class="rounded-lg bg-indigo-100 px-2.5 py-1 text-xs font-bold text-indigo-700">{{ stats.new_patients_month }} new</span>
                                <span class="text-gray-500">patients this month</span>
                            </div>
                        </div>

                        <!-- Recent Registrations -->
                        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                            <h3 class="mb-4 flex items-center gap-2 text-xs font-bold tracking-widest text-gray-500 uppercase">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                                Recent Registrations
                            </h3>
                            <div class="divide-y divide-gray-100">
                                <div v-for="r in recent" :key="r.id" class="flex items-center justify-between py-2.5 first:pt-0 last:pb-0">
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-8 w-8 items-center justify-center rounded-lg text-xs font-bold"
                                            :class="roleColor(r.role)">{{ r.name.charAt(0) }}</span>
                                        <div>
                                            <p class="text-sm font-bold text-gray-700">{{ r.name }}</p>
                                            <p class="text-[11px] text-gray-400">{{ r.email }} · {{ r.role }}</p>
                                        </div>
                                    </div>
                                    <span class="text-xs text-gray-400">{{ r.time }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AdminSidebar from '@/components/AdminSidebar.vue';

defineProps<{
    user?: any;
    stats?: any;
    recent?: any[];
}>();

const roleColor = (role: string): string => {
    switch (role) {
        case 'doctor': return 'bg-emerald-100 text-emerald-700';
        case 'receptionist': return 'bg-blue-100 text-blue-700';
        case 'admin': return 'bg-purple-100 text-purple-700';
        default: return 'bg-gray-100 text-gray-700';
    }
};
</script>

<style scoped>
.animate-fade-in-up {
    animation: fadeInUp 0.3s ease-out forwards;
}
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
