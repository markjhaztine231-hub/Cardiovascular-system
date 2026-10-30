<template>
    <div
        class="flex h-screen bg-[#f1f5f9] font-sans selection:bg-indigo-500 selection:text-white"
    >
        <AdminSidebar :user="user" />

        <main class="flex h-screen flex-1 flex-col overflow-hidden">
            <AdminHeader title="System Administration" :user="user" />

            <div class="relative flex-1 overflow-y-auto">
                <div class="mx-auto max-w-7xl space-y-6 p-5 lg:p-8">
                    <!-- Hero / quick actions -->
                    <section
                        class="relative overflow-hidden rounded-3xl bg-linear-to-br from-slate-900 via-slate-800 to-indigo-900 p-6 text-white shadow-xl shadow-slate-900/10 lg:p-8"
                    >
                        <div
                            class="pointer-events-none absolute -top-24 -right-16 h-64 w-64 rounded-full bg-indigo-500/25 blur-3xl"
                        ></div>
                        <div
                            class="pointer-events-none absolute right-24 -bottom-28 h-64 w-64 rounded-full border-[24px] border-white/5"
                        ></div>
                        <div
                            class="pointer-events-none absolute -top-10 left-1/3 h-24 w-24 rounded-full border-[14px] border-white/5"
                        ></div>

                        <div
                            class="relative flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between"
                        >
                            <div>
                                <p
                                    class="text-[10px] font-black tracking-[0.25em] text-indigo-300 uppercase"
                                >
                                    Admin overview
                                </p>
                                <h1
                                    class="mt-2 text-2xl font-black tracking-tight lg:text-3xl"
                                >
                                    {{ greeting }}, {{ firstName }}
                                </h1>
                                <p
                                    class="mt-2 max-w-xl text-sm leading-relaxed text-slate-300"
                                >
                                    Monitor clinic activity, manage accounts,
                                    and keep the audit trail in view - all from
                                    one console.
                                </p>
                            </div>

                            <div class="flex flex-wrap items-center gap-2.5">
                                <Link
                                    href="/admin/create-account"
                                    class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-bold text-slate-900 shadow-sm transition hover:bg-indigo-50"
                                >
                                    <UserPlus class="h-4 w-4" />
                                    Create account
                                </Link>
                                <Link
                                    href="/admin/users-directory"
                                    class="inline-flex items-center gap-2 rounded-xl border border-white/15 bg-white/10 px-4 py-2.5 text-sm font-bold text-white backdrop-blur transition hover:bg-white/20"
                                >
                                    <Users class="h-4 w-4" />
                                    Users directory
                                </Link>
                                <Link
                                    href="/admin/audit-log"
                                    class="inline-flex items-center gap-2 rounded-xl border border-white/15 bg-white/10 px-4 py-2.5 text-sm font-bold text-white backdrop-blur transition hover:bg-white/20"
                                >
                                    <FileClock class="h-4 w-4" />
                                    Audit log
                                </Link>
                            </div>
                        </div>
                    </section>

                    <!-- Account KPIs -->
                    <section
                        class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4"
                    >
                        <div
                            v-for="card in overviewCards"
                            :key="card.label"
                            class="group relative overflow-hidden rounded-2xl border border-gray-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
                        >
                            <div class="flex items-start justify-between">
                                <div
                                    class="flex h-11 w-11 items-center justify-center rounded-xl"
                                    :class="card.iconClass"
                                >
                                    <component
                                        :is="card.icon"
                                        class="h-5 w-5"
                                    />
                                </div>
                                <span
                                    class="rounded-full px-2.5 py-1 text-[10px] font-black tracking-wide uppercase"
                                    :class="card.badgeClass"
                                    >{{ card.badge }}</span
                                >
                            </div>
                            <p
                                class="mt-5 text-3xl font-black tracking-tight text-slate-800"
                            >
                                {{ card.value }}
                            </p>
                            <p
                                class="mt-1 text-[10px] font-black tracking-[0.16em] text-slate-400 uppercase"
                            >
                                {{ card.label }}
                            </p>
                            <span
                                class="absolute inset-x-0 bottom-0 h-1"
                                :class="card.accentClass"
                            ></span>
                        </div>
                    </section>

                    <!-- Today's clinic pulse -->
                    <section>
                        <div
                            class="mb-3 flex flex-wrap items-end justify-between gap-2"
                        >
                            <div>
                                <h2
                                    class="text-lg font-black tracking-tight text-slate-800"
                                >
                                    Today's clinic pulse
                                </h2>
                                <p class="text-xs font-medium text-gray-500">
                                    {{ todayLabel }}
                                </p>
                            </div>
                            <span
                                class="rounded-full border border-indigo-100 bg-indigo-50 px-3 py-1 text-[10px] font-black tracking-widest text-indigo-700 uppercase"
                            >
                                {{ stats.appointments_today }} on the schedule
                            </span>
                        </div>

                        <div
                            class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4"
                        >
                            <div
                                v-for="item in todayCards"
                                :key="item.label"
                                class="flex items-center gap-4 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm"
                            >
                                <div
                                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl"
                                    :class="item.iconClass"
                                >
                                    <component
                                        :is="item.icon"
                                        class="h-5 w-5"
                                    />
                                </div>
                                <div class="min-w-0">
                                    <p
                                        class="text-2xl leading-none font-black text-slate-800"
                                    >
                                        {{ item.value }}
                                    </p>
                                    <p
                                        class="mt-1 truncate text-[10px] font-black tracking-[0.14em] text-slate-400 uppercase"
                                    >
                                        {{ item.label }}
                                    </p>
                                    <p
                                        class="mt-1 truncate text-[11px] font-semibold"
                                        :class="item.hintClass"
                                    >
                                        {{ item.hint }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Activity chart + recent registrations -->
                    <section class="grid grid-cols-1 gap-6 xl:grid-cols-3">
                        <div
                            class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6 xl:col-span-2"
                        >
                            <div
                                class="flex flex-wrap items-start justify-between gap-3"
                            >
                                <div>
                                    <h2
                                        class="text-base font-black tracking-tight text-slate-800"
                                    >
                                        Appointment activity
                                    </h2>
                                    <p
                                        class="mt-0.5 text-xs font-medium text-gray-500"
                                    >
                                        Scheduled visits over the last 7 days
                                    </p>
                                </div>
                                <div
                                    class="flex items-center gap-4 text-[11px] font-bold text-gray-500"
                                >
                                    <span
                                        class="inline-flex items-center gap-1.5"
                                    >
                                        <span
                                            class="h-2.5 w-2.5 rounded-full bg-indigo-200"
                                        ></span>
                                        All visits
                                    </span>
                                    <span
                                        class="inline-flex items-center gap-1.5"
                                    >
                                        <span
                                            class="h-2.5 w-2.5 rounded-full bg-indigo-600"
                                        ></span>
                                        Completed
                                    </span>
                                </div>
                            </div>

                            <div class="relative mt-6 h-72 w-full">
                                <canvas ref="trendCanvas"></canvas>
                            </div>
                        </div>

                        <div
                            class="flex flex-col rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6"
                        >
                            <div class="flex items-start justify-between">
                                <div>
                                    <h2
                                        class="text-base font-black tracking-tight text-slate-800"
                                    >
                                        Latest accounts
                                    </h2>
                                    <p
                                        class="mt-0.5 text-xs font-medium text-gray-500"
                                    >
                                        Newest registrations
                                    </p>
                                </div>
                                <Link
                                    href="/admin/users-directory"
                                    class="inline-flex items-center gap-1 text-xs font-bold text-indigo-600 transition hover:text-indigo-800"
                                >
                                    View all
                                    <ArrowUpRight class="h-3.5 w-3.5" />
                                </Link>
                            </div>

                            <div class="mt-4 flex-1 divide-y divide-gray-100">
                                <div
                                    v-for="r in recent"
                                    :key="r.id"
                                    class="flex items-center gap-3 py-3 first:pt-0 last:pb-0"
                                >
                                    <span
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-xs font-black"
                                        :class="roleAvatarClass(r.role)"
                                        >{{ r.name.charAt(0) }}</span
                                    >
                                    <div class="min-w-0 flex-1">
                                        <p
                                            class="truncate text-sm font-bold text-slate-700"
                                        >
                                            {{ r.name }}
                                        </p>
                                        <p
                                            class="truncate text-[11px] text-gray-400"
                                        >
                                            {{ r.email }}
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <span
                                            class="rounded-md px-2 py-0.5 text-[10px] font-black tracking-wide uppercase"
                                            :class="roleBadgeClass(r.role)"
                                            >{{ r.role }}</span
                                        >
                                        <p
                                            class="mt-1 text-[10px] text-gray-400"
                                        >
                                            {{ r.time }}
                                        </p>
                                    </div>
                                </div>

                                <p
                                    v-if="recent.length === 0"
                                    class="py-10 text-center text-sm font-medium text-gray-400"
                                >
                                    No registrations yet.
                                </p>
                            </div>
                        </div>
                    </section>

                    <!-- Account mix / completion / shortcuts -->
                    <section class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                        <div
                            class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6"
                        >
                            <div class="flex items-start justify-between">
                                <div>
                                    <h2
                                        class="text-base font-black tracking-tight text-slate-800"
                                    >
                                        Account mix
                                    </h2>
                                    <p
                                        class="mt-0.5 text-xs font-medium text-gray-500"
                                    >
                                        {{ stats.total_users }} accounts in the
                                        system
                                    </p>
                                </div>
                                <span
                                    class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-100 text-slate-600"
                                >
                                    <ShieldCheck class="h-4 w-4" />
                                </span>
                            </div>

                            <div class="mt-5 space-y-4">
                                <div
                                    v-for="role in roleBreakdown"
                                    :key="role.key"
                                >
                                    <div
                                        class="flex items-center justify-between text-xs font-bold"
                                    >
                                        <span
                                            class="inline-flex items-center gap-2 text-slate-600"
                                        >
                                            <span
                                                class="h-2 w-2 rounded-full"
                                                :class="roleTone(role.key).dot"
                                            ></span>
                                            {{ role.label }}
                                        </span>
                                        <span class="text-slate-800">
                                            {{ role.value }}
                                            <span
                                                class="ml-1 text-[10px] font-bold text-gray-400"
                                                >{{ role.percent }}%</span
                                            >
                                        </span>
                                    </div>
                                    <div
                                        class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-slate-100"
                                    >
                                        <div
                                            class="h-full rounded-full transition-all"
                                            :class="roleTone(role.key).bar"
                                            :style="{
                                                width: `${role.percent}%`,
                                            }"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div
                            class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6"
                        >
                            <div class="flex items-start justify-between">
                                <div>
                                    <h2
                                        class="text-base font-black tracking-tight text-slate-800"
                                    >
                                        Today's completion
                                    </h2>
                                    <p
                                        class="mt-0.5 text-xs font-medium text-gray-500"
                                    >
                                        Visits finished vs. scheduled
                                    </p>
                                </div>
                                <span
                                    class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600"
                                >
                                    <TrendingUp class="h-4 w-4" />
                                </span>
                            </div>

                            <div class="mt-5 flex items-center gap-5">
                                <div
                                    class="relative flex h-24 w-24 shrink-0 items-center justify-center rounded-full"
                                    :style="{
                                        background: `conic-gradient(#4f46e5 ${completionRate}%, #e2e8f0 0)`,
                                    }"
                                >
                                    <div
                                        class="flex h-[74px] w-[74px] flex-col items-center justify-center rounded-full bg-white"
                                    >
                                        <span
                                            class="text-xl font-black text-slate-800"
                                            >{{ completionRate }}%</span
                                        >
                                        <span
                                            class="text-[9px] font-black tracking-wider text-gray-400 uppercase"
                                            >done</span
                                        >
                                    </div>
                                </div>
                                <div class="space-y-1.5 text-xs font-semibold">
                                    <p
                                        class="flex items-center gap-2 text-slate-600"
                                    >
                                        <span
                                            class="h-2 w-2 rounded-full bg-indigo-600"
                                        ></span>
                                        {{ stats.completed_today }} completed
                                    </p>
                                    <p
                                        class="flex items-center gap-2 text-slate-600"
                                    >
                                        <span
                                            class="h-2 w-2 rounded-full bg-amber-400"
                                        ></span>
                                        {{ stats.pending_today }} pending
                                    </p>
                                    <p
                                        class="flex items-center gap-2 text-slate-600"
                                    >
                                        <span
                                            class="h-2 w-2 rounded-full bg-red-400"
                                        ></span>
                                        {{ stats.no_shows_today }} no-shows
                                    </p>
                                </div>
                            </div>

                            <div class="mt-5 grid grid-cols-2 gap-3">
                                <div class="rounded-xl bg-slate-50 p-3">
                                    <p
                                        class="text-lg leading-none font-black text-slate-800"
                                    >
                                        {{ stats.total_appointments }}
                                    </p>
                                    <p
                                        class="mt-1 text-[9px] font-black tracking-widest text-gray-400 uppercase"
                                    >
                                        All-time visits
                                    </p>
                                </div>
                                <div class="rounded-xl bg-indigo-50 p-3">
                                    <p
                                        class="text-lg leading-none font-black text-indigo-700"
                                    >
                                        +{{ stats.new_patients_month }}
                                    </p>
                                    <p
                                        class="mt-1 text-[9px] font-black tracking-widest text-indigo-400 uppercase"
                                    >
                                        New patients
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div
                            class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6"
                        >
                            <div class="flex items-start justify-between">
                                <div>
                                    <h2
                                        class="text-base font-black tracking-tight text-slate-800"
                                    >
                                        Admin shortcuts
                                    </h2>
                                    <p
                                        class="mt-0.5 text-xs font-medium text-gray-500"
                                    >
                                        Common console tasks
                                    </p>
                                </div>
                                <span
                                    class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600"
                                >
                                    <Activity class="h-4 w-4" />
                                </span>
                            </div>

                            <div class="mt-5 space-y-2.5">
                                <Link
                                    v-for="action in shortcuts"
                                    :key="action.title"
                                    :href="action.href"
                                    class="group flex items-center gap-3 rounded-xl border border-slate-200 p-3 transition hover:border-indigo-200 hover:bg-indigo-50/60"
                                >
                                    <span
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg"
                                        :class="action.iconClass"
                                    >
                                        <component
                                            :is="action.icon"
                                            class="h-4 w-4"
                                        />
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span
                                            class="block text-sm font-bold text-slate-700"
                                            >{{ action.title }}</span
                                        >
                                        <span
                                            class="block truncate text-[11px] text-gray-400"
                                            >{{ action.description }}</span
                                        >
                                    </span>
                                    <ArrowUpRight
                                        class="h-4 w-4 text-gray-300 transition group-hover:text-indigo-500"
                                    />
                                </Link>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </main>
    </div>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Chart, registerables } from 'chart.js';
import {
    Activity,
    ArrowUpRight,
    CalendarDays,
    CheckCircle2,
    Clock,
    FileClock,
    HeartPulse,
    ShieldCheck,
    TrendingUp,
    UserPlus,
    UserRound,
    Users,
    XCircle,
} from 'lucide-vue-next';
import { computed, nextTick, onMounted, onUnmounted, ref } from 'vue';
import type { Component } from 'vue';
import AdminHeader from '@/components/AdminHeader.vue';
import AdminSidebar from '@/components/AdminSidebar.vue';

Chart.register(...registerables);

interface TrendPoint {
    label: string;
    date: string;
    total: number;
    completed: number;
}

interface RoleSlice {
    key: string;
    label: string;
    value: number;
}

interface AdminStats {
    total_users: number;
    total_doctors: number;
    total_patients: number;
    total_staff: number;
    appointments_today: number;
    completed_today: number;
    pending_today: number;
    no_shows_today: number;
    total_appointments: number;
    new_patients_month: number;
    appointments_trend?: TrendPoint[];
    role_distribution?: RoleSlice[];
}

interface RecentRegistration {
    id: number;
    name: string;
    email: string;
    role: string;
    time: string;
}

interface OverviewCard {
    label: string;
    value: number;
    icon: Component;
    badge: string;
    iconClass: string;
    badgeClass: string;
    accentClass: string;
}

interface TodayCard {
    label: string;
    value: number;
    hint: string;
    icon: Component;
    iconClass: string;
    hintClass: string;
}

const props = defineProps<{
    user?: { name?: string };
    stats: AdminStats;
    recent: RecentRegistration[];
}>();

const trendCanvas = ref<HTMLCanvasElement | null>(null);
let trendChart: Chart | null = null;

const firstName = computed(
    () => props.user?.name?.replace(/^Dr\.\s*/i, '').split(' ')[0] ?? 'Admin',
);

const greeting = computed(() => {
    const hour = new Date().getHours();

    if (hour < 12) {
        return 'Good morning';
    }

    if (hour < 18) {
        return 'Good afternoon';
    }

    return 'Good evening';
});

const todayLabel = new Intl.DateTimeFormat('en-US', {
    weekday: 'long',
    month: 'long',
    day: 'numeric',
    year: 'numeric',
}).format(new Date());

const completionRate = computed(() => {
    if (props.stats.appointments_today === 0) {
        return 0;
    }

    return Math.round(
        (props.stats.completed_today / props.stats.appointments_today) * 100,
    );
});

const overviewCards = computed<OverviewCard[]>(() => [
    {
        label: 'Total users',
        value: props.stats.total_users,
        icon: Users,
        badge: 'All accounts',
        iconClass: 'bg-indigo-50 text-indigo-600',
        badgeClass: 'bg-indigo-50 text-indigo-600',
        accentClass: 'bg-indigo-500',
    },
    {
        label: 'Cardiologists',
        value: props.stats.total_doctors,
        icon: HeartPulse,
        badge: 'Clinical',
        iconClass: 'bg-emerald-50 text-emerald-600',
        badgeClass: 'bg-emerald-50 text-emerald-600',
        accentClass: 'bg-emerald-500',
    },
    {
        label: 'Reception staff',
        value: props.stats.total_staff,
        icon: UserRound,
        badge: 'Front desk',
        iconClass: 'bg-sky-50 text-sky-600',
        badgeClass: 'bg-sky-50 text-sky-600',
        accentClass: 'bg-sky-500',
    },
    {
        label: 'Registered patients',
        value: props.stats.total_patients,
        icon: Users,
        badge: `+${props.stats.new_patients_month} this month`,
        iconClass: 'bg-amber-50 text-amber-600',
        badgeClass: 'bg-amber-50 text-amber-700',
        accentClass: 'bg-amber-400',
    },
]);

const todayCards = computed<TodayCard[]>(() => [
    {
        label: 'Appointments today',
        value: props.stats.appointments_today,
        hint: 'On the schedule',
        icon: CalendarDays,
        iconClass: 'bg-indigo-50 text-indigo-600',
        hintClass: 'text-indigo-500',
    },
    {
        label: 'Completed',
        value: props.stats.completed_today,
        hint: `${completionRate.value}% of today`,
        icon: CheckCircle2,
        iconClass: 'bg-emerald-50 text-emerald-600',
        hintClass: 'text-emerald-600',
    },
    {
        label: 'Scheduled / pending',
        value: props.stats.pending_today,
        hint: 'Still awaiting',
        icon: Clock,
        iconClass: 'bg-amber-50 text-amber-600',
        hintClass: 'text-amber-600',
    },
    {
        label: 'No-shows today',
        value: props.stats.no_shows_today,
        hint: 'Needs follow-up',
        icon: XCircle,
        iconClass: 'bg-red-50 text-red-600',
        hintClass: 'text-red-500',
    },
]);

const roleBreakdown = computed(() => {
    const total = props.stats.total_users || 1;

    return (props.stats.role_distribution ?? []).map((role) => ({
        ...role,
        percent: Math.round((role.value / total) * 100),
    }));
});

const shortcuts = [
    {
        title: 'Create an account',
        description: 'Provision staff or patient access',
        href: '/admin/create-account',
        icon: UserPlus,
        iconClass: 'bg-indigo-50 text-indigo-600',
    },
    {
        title: 'Users directory',
        description: 'Search, edit, or remove accounts',
        href: '/admin/users-directory',
        icon: Users,
        iconClass: 'bg-sky-50 text-sky-600',
    },
    {
        title: 'Audit log',
        description: 'Review recent system activity',
        href: '/admin/audit-log',
        icon: FileClock,
        iconClass: 'bg-slate-100 text-slate-600',
    },
];

const roleTone = (key: string) => {
    const tones: Record<string, { bar: string; dot: string }> = {
        doctor: { bar: 'bg-emerald-500', dot: 'bg-emerald-500' },
        receptionist: { bar: 'bg-sky-500', dot: 'bg-sky-500' },
        admin: { bar: 'bg-indigo-500', dot: 'bg-indigo-500' },
        patient: { bar: 'bg-amber-400', dot: 'bg-amber-400' },
    };

    return tones[key] ?? { bar: 'bg-slate-400', dot: 'bg-slate-400' };
};

const roleAvatarClass = (role: string): string => {
    switch (role) {
        case 'doctor':
            return 'bg-emerald-100 text-emerald-700';
        case 'receptionist':
            return 'bg-sky-100 text-sky-700';
        case 'admin':
            return 'bg-indigo-100 text-indigo-700';
        default:
            return 'bg-amber-100 text-amber-700';
    }
};

const roleBadgeClass = (role: string): string => {
    switch (role) {
        case 'doctor':
            return 'bg-emerald-50 text-emerald-700';
        case 'receptionist':
            return 'bg-sky-50 text-sky-700';
        case 'admin':
            return 'bg-indigo-50 text-indigo-700';
        default:
            return 'bg-amber-50 text-amber-700';
    }
};

function renderTrendChart() {
    const points = props.stats.appointments_trend ?? [];

    if (!trendCanvas.value) {
        return;
    }

    trendChart?.destroy();

    const context = trendCanvas.value.getContext('2d');

    if (!context) {
        return;
    }

    trendChart = new Chart(context, {
        type: 'bar',
        data: {
            labels: points.map((point) => point.label),
            datasets: [
                {
                    label: 'All visits',
                    data: points.map((point) => point.total),
                    backgroundColor: '#c7d2fe',
                    hoverBackgroundColor: '#a5b4fc',
                    borderRadius: 7,
                    borderSkipped: false,
                    barPercentage: 0.6,
                    categoryPercentage: 0.7,
                },
                {
                    type: 'line',
                    label: 'Completed',
                    data: points.map((point) => point.completed),
                    borderColor: '#4f46e5',
                    backgroundColor: '#4f46e5',
                    borderWidth: 2.5,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#4f46e5',
                    pointBorderWidth: 2,
                    tension: 0.35,
                    fill: false,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index',
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#0f172a',
                    titleFont: { size: 11, weight: 'bold' },
                    bodyFont: { size: 11 },
                    padding: 10,
                    cornerRadius: 8,
                    displayColors: true,
                    boxWidth: 8,
                    boxHeight: 8,
                },
            },
            scales: {
                x: {
                    grid: { display: false },
                    border: { display: false },
                    ticks: {
                        color: '#94a3b8',
                        font: { size: 11, weight: 600 },
                    },
                },
                y: {
                    beginAtZero: true,
                    border: { display: false },
                    grid: { color: 'rgba(148, 163, 184, 0.18)' },
                    ticks: {
                        color: '#94a3b8',
                        font: { size: 10 },
                        precision: 0,
                        stepSize: 1,
                    },
                },
            },
        },
    });
}

onMounted(() => {
    nextTick(renderTrendChart);
});

onUnmounted(() => {
    trendChart?.destroy();
    trendChart = null;
});
</script>
