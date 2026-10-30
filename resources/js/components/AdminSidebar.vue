<template>
    <!-- Mobile backdrop -->
    <div
        v-if="isMobileOpen"
        class="fixed inset-0 z-40 bg-slate-950/50 backdrop-blur-sm md:hidden"
        @click="closeMobile"
    ></div>

    <aside
        class="fixed inset-y-0 left-0 z-50 flex w-64 shrink-0 flex-col border-r border-slate-200 bg-white shadow-2xl shadow-slate-900/10 transition-[width,transform] duration-300 md:static md:z-20 md:shadow-none"
        :class="[
            isMobileOpen
                ? 'translate-x-0'
                : '-translate-x-full md:translate-x-0',
            collapsed ? 'md:w-20' : 'md:w-64',
        ]"
    >
        <!-- Logo -->
        <div
            class="flex h-20 shrink-0 items-center border-b border-slate-100"
            :class="collapsed ? 'md:justify-center md:px-0' : 'px-4'"
        >
            <Link
                href="/admin/dashboard"
                class="flex min-w-0 items-center gap-3"
                :class="collapsed ? 'md:justify-center' : ''"
                :title="collapsed ? 'CardioCare admin' : undefined"
                @click="closeMobile"
            >
                <span
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-linear-to-br from-indigo-500 to-violet-600 text-white shadow-lg shadow-indigo-500/25"
                >
                    <HeartPulse class="h-5 w-5" />
                </span>
                <span v-show="!collapsed" class="min-w-0 leading-tight">
                    <span
                        class="block truncate text-base font-black tracking-tight text-slate-800"
                        >CardioCare</span
                    >
                    <span
                        class="block text-[9px] font-black tracking-[0.22em] text-indigo-500 uppercase"
                        >Admin console</span
                    >
                </span>
            </Link>

            <button
                v-show="!collapsed"
                type="button"
                class="ml-auto hidden rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 md:inline-flex"
                title="Collapse sidebar"
                aria-label="Collapse sidebar"
                @click="toggleDesktop"
            >
                <PanelLeftClose class="h-4 w-4" />
            </button>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 overflow-y-auto px-3 py-5">
            <p
                v-show="!collapsed"
                class="px-3 pb-2 text-[9px] font-black tracking-[0.22em] text-slate-400 uppercase"
            >
                Workspace
            </p>
            <ul class="space-y-1">
                <li v-for="item in workspaceNav" :key="item.href">
                    <Link
                        :href="item.href"
                        :title="collapsed ? item.label : undefined"
                        class="group flex items-center rounded-xl py-2.5 text-sm font-bold whitespace-nowrap transition"
                        :class="[
                            isActive(item.href)
                                ? 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-100'
                                : 'text-slate-500 hover:bg-slate-100 hover:text-slate-800',
                            collapsed
                                ? 'md:justify-center md:px-0'
                                : 'gap-3 px-3',
                        ]"
                        @click="closeMobile"
                    >
                        <component
                            :is="item.icon"
                            class="h-[18px] w-[18px] shrink-0"
                            :class="
                                isActive(item.href)
                                    ? 'text-indigo-600'
                                    : 'text-slate-400 group-hover:text-slate-600'
                            "
                        />
                        <span v-show="!collapsed">{{ item.label }}</span>
                    </Link>
                </li>
            </ul>

            <p
                v-show="!collapsed"
                class="mt-6 px-3 pb-2 text-[9px] font-black tracking-[0.22em] text-slate-400 uppercase"
            >
                Account
            </p>
            <div
                v-show="collapsed"
                class="my-4 border-t border-slate-100"
            ></div>
            <ul class="space-y-1">
                <li v-for="item in accountNav" :key="item.href">
                    <Link
                        :href="item.href"
                        :title="collapsed ? item.label : undefined"
                        class="group flex items-center rounded-xl py-2.5 text-sm font-bold whitespace-nowrap transition"
                        :class="[
                            isActive(item.href)
                                ? 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-100'
                                : 'text-slate-500 hover:bg-slate-100 hover:text-slate-800',
                            collapsed
                                ? 'md:justify-center md:px-0'
                                : 'gap-3 px-3',
                        ]"
                        @click="closeMobile"
                    >
                        <component
                            :is="item.icon"
                            class="h-[18px] w-[18px] shrink-0"
                            :class="
                                isActive(item.href)
                                    ? 'text-indigo-600'
                                    : 'text-slate-400 group-hover:text-slate-600'
                            "
                        />
                        <span v-show="!collapsed">{{ item.label }}</span>
                    </Link>
                </li>
            </ul>
        </nav>

        <!-- User -->
        <div class="border-t border-slate-100 p-3">
            <div
                class="flex items-center gap-2.5"
                :class="collapsed ? 'md:flex-col' : ''"
            >
                <Link
                    href="/profile"
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-linear-to-br from-slate-700 to-slate-900 text-xs font-black text-white shadow-sm"
                    :title="displayName"
                    @click="closeMobile"
                >
                    {{ initial }}
                </Link>
                <div v-show="!collapsed" class="min-w-0 flex-1">
                    <Link
                        href="/profile"
                        class="block truncate text-sm font-bold text-slate-700 hover:text-indigo-600"
                        @click="closeMobile"
                    >
                        {{ displayName }}
                    </Link>
                    <p
                        class="text-[9px] font-black tracking-[0.16em] text-slate-400 uppercase"
                    >
                        System administrator
                    </p>
                </div>
                <LogoutButton v-show="!collapsed">
                    <button
                        type="button"
                        class="rounded-lg p-2 text-slate-400 transition hover:bg-red-50 hover:text-red-600"
                        title="Sign out"
                        aria-label="Sign out"
                    >
                        <LogOut class="h-4 w-4" />
                    </button>
                </LogoutButton>
            </div>

            <LogoutButton v-if="collapsed">
                <button
                    type="button"
                    class="mt-2 flex h-10 w-10 items-center justify-center rounded-xl text-slate-400 transition hover:bg-red-50 hover:text-red-600"
                    title="Sign out"
                    aria-label="Sign out"
                >
                    <LogOut class="h-4 w-4" />
                </button>
            </LogoutButton>
        </div>
    </aside>
</template>

<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    HeartPulse,
    LayoutDashboard,
    LogOut,
    PanelLeftClose,
    ScrollText,
    UserPlus,
    UserRound,
    Users,
} from 'lucide-vue-next';
import { computed } from 'vue';
import LogoutButton from '@/components/LogoutButton.vue';
import { useSidebar } from '@/composables/useSidebar';

const props = defineProps<{
    user?: { name?: string };
}>();

const { isOpen, isMobileOpen, isMobile, toggleDesktop, closeMobile } =
    useSidebar('admin');

const page = usePage();

const collapsed = computed(() => !isOpen.value && !isMobile.value);

const currentPath = computed(() => page.url.split('?')[0]);

const isActive = (href: string): boolean => currentPath.value === href;

const displayName = computed(() => props.user?.name ?? 'Administrator');

const initial = computed(() => displayName.value.charAt(0).toUpperCase());

const workspaceNav = [
    {
        label: 'Dashboard',
        href: '/admin/dashboard',
        icon: LayoutDashboard,
    },
    {
        label: 'Users directory',
        href: '/admin/users-directory',
        icon: Users,
    },
    {
        label: 'Create account',
        href: '/admin/create-account',
        icon: UserPlus,
    },
];

const accountNav = [
    {
        label: 'My profile',
        href: '/profile',
        icon: UserRound,
    },
    {
        label: 'Audit log',
        href: '/admin/audit-log',
        icon: ScrollText,
    },
];
</script>
