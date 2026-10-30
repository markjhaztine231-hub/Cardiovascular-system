<template>
    <div
        class="flex h-screen bg-[#f1f5f9] font-sans selection:bg-indigo-500 selection:text-white"
    >
        <AdminSidebar :user="user" />

        <main class="flex h-screen flex-1 flex-col overflow-hidden">
            <AdminHeader title="Users Directory" :user="user" />

            <div class="relative flex-1 overflow-y-auto">
                <div class="mx-auto max-w-7xl space-y-6 p-5 lg:p-8">
                    <AdminPageHeader
                        eyebrow="Access control"
                        title="Users directory"
                        description="Search, review, and manage every account in the clinic. Role and status changes are recorded in the audit log."
                    >
                        <template #actions>
                            <Link
                                href="/admin/create-account"
                                class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm shadow-indigo-600/20 transition hover:bg-indigo-700"
                            >
                                <UserPlus class="h-4 w-4" />
                                New account
                            </Link>
                        </template>
                    </AdminPageHeader>

                    <div
                        class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4"
                    >
                        <AdminStatCard
                            v-for="card in summaryCards"
                            :key="card.label"
                            :label="card.label"
                            :value="card.value"
                            :hint="card.hint"
                            :icon="card.icon"
                            :tone="card.tone"
                        />
                    </div>

                    <AdminSectionCard
                        title="All accounts"
                        :subtitle="resultSummary"
                        :padded="false"
                    >
                        <template #actions>
                            <span
                                class="rounded-full bg-indigo-50 px-3 py-1 text-[10px] font-black tracking-wide text-indigo-600 uppercase ring-1 ring-indigo-100"
                            >
                                {{ users.total }} result{{
                                    users.total === 1 ? '' : 's'
                                }}
                            </span>
                        </template>

                        <div class="border-b border-gray-100 p-4 sm:p-5">
                            <div
                                class="flex flex-col gap-3 xl:flex-row xl:items-center"
                            >
                                <div class="relative flex-1 xl:max-w-sm">
                                    <Search
                                        class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-slate-400"
                                    />
                                    <input
                                        v-model="search"
                                        type="text"
                                        placeholder="Search by name, email, or ID..."
                                        class="w-full rounded-xl border border-gray-200 bg-slate-50 py-2.5 pr-3 pl-9 text-sm font-medium text-slate-800 transition outline-none placeholder:text-slate-400 focus:border-indigo-400 focus:bg-white focus:ring-2 focus:ring-indigo-500/15"
                                        @input="debouncedSearch"
                                    />
                                </div>

                                <div class="flex flex-wrap items-center gap-2">
                                    <select
                                        v-model="roleFilter"
                                        class="rounded-xl border border-gray-200 bg-slate-50 px-3.5 py-2.5 text-sm font-semibold text-slate-700 transition outline-none focus:border-indigo-400 focus:bg-white focus:ring-2 focus:ring-indigo-500/15"
                                        @change="fetchUsers"
                                    >
                                        <option value="">All roles</option>
                                        <option value="doctor">Doctor</option>
                                        <option value="receptionist">
                                            Receptionist
                                        </option>
                                        <option value="admin">Admin</option>
                                        <option value="patient">Patient</option>
                                    </select>
                                    <select
                                        v-model="statusFilter"
                                        class="rounded-xl border border-gray-200 bg-slate-50 px-3.5 py-2.5 text-sm font-semibold text-slate-700 transition outline-none focus:border-indigo-400 focus:bg-white focus:ring-2 focus:ring-indigo-500/15"
                                        @change="fetchUsers"
                                    >
                                        <option value="">All statuses</option>
                                        <option value="Register">
                                            Register
                                        </option>
                                        <option value="Walk-in Register">
                                            Walk-in Register
                                        </option>
                                    </select>
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-sm font-bold text-gray-600 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40"
                                        :disabled="!hasFilters"
                                        @click="clearFilters"
                                    >
                                        <X class="h-3.5 w-3.5" />
                                        Clear
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="max-h-[calc(100vh-28rem)] overflow-auto">
                            <table class="w-full min-w-[64rem]">
                                <thead class="sticky top-0 z-10 bg-slate-50">
                                    <tr class="border-b border-gray-100">
                                        <th
                                            class="px-6 py-3.5 text-left text-[10px] font-extrabold tracking-widest text-gray-500 uppercase"
                                        >
                                            Account
                                        </th>
                                        <th
                                            class="px-6 py-3.5 text-left text-[10px] font-extrabold tracking-widest text-gray-500 uppercase"
                                        >
                                            System ID
                                        </th>
                                        <th
                                            class="px-6 py-3.5 text-left text-[10px] font-extrabold tracking-widest text-gray-500 uppercase"
                                        >
                                            Role
                                        </th>
                                        <th
                                            class="px-6 py-3.5 text-left text-[10px] font-extrabold tracking-widest text-gray-500 uppercase"
                                        >
                                            Status
                                        </th>
                                        <th
                                            class="px-6 py-3.5 text-left text-[10px] font-extrabold tracking-widest text-gray-500 uppercase"
                                        >
                                            Joined
                                        </th>
                                        <th
                                            class="px-6 py-3.5 text-right text-[10px] font-extrabold tracking-widest text-gray-500 uppercase"
                                        >
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <tr
                                        v-for="u in users.data"
                                        :key="u.id"
                                        class="transition-colors hover:bg-indigo-50/40"
                                    >
                                        <td class="px-6 py-3.5">
                                            <div
                                                class="flex items-center gap-3"
                                            >
                                                <span
                                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-xs font-black"
                                                    :class="
                                                        roleAvatarClass(u.role)
                                                    "
                                                    >{{
                                                        u.name
                                                            .charAt(0)
                                                            .toUpperCase()
                                                    }}</span
                                                >
                                                <div class="min-w-0">
                                                    <p
                                                        class="truncate text-sm font-bold text-slate-700"
                                                    >
                                                        {{ u.name }}
                                                    </p>
                                                    <p
                                                        class="truncate text-[11px] text-gray-400"
                                                    >
                                                        {{ u.email }}
                                                    </p>
                                                    <p
                                                        v-if="u.specialty"
                                                        class="mt-0.5 truncate text-[10px] font-bold text-indigo-500"
                                                    >
                                                        {{ u.specialty }}
                                                    </p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-3.5">
                                            <span
                                                class="rounded-md bg-slate-100 px-2 py-1 font-mono text-[11px] font-bold text-slate-600"
                                            >
                                                {{ u.system_id || '—' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-3.5">
                                            <span
                                                class="rounded-md border px-2.5 py-1 text-[10px] font-extrabold tracking-wider uppercase"
                                                :class="roleBadgeClass(u.role)"
                                                >{{ roleLabel(u.role) }}</span
                                            >
                                        </td>
                                        <td class="px-6 py-3.5">
                                            <span
                                                class="inline-flex items-center gap-1.5 rounded-md border px-2.5 py-1 text-[10px] font-extrabold tracking-wider uppercase"
                                                :class="
                                                    statusBadgeClass(u.status)
                                                "
                                            >
                                                <span
                                                    class="h-1.5 w-1.5 rounded-full"
                                                    :class="
                                                        statusDotClass(u.status)
                                                    "
                                                ></span>
                                                {{ u.status || 'Unknown' }}
                                            </span>
                                        </td>
                                        <td
                                            class="px-6 py-3.5 text-xs font-medium text-gray-500"
                                        >
                                            {{ u.joined }}
                                        </td>
                                        <td class="px-6 py-3.5 text-right">
                                            <button
                                                type="button"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-indigo-200 bg-indigo-50 text-indigo-600 transition hover:bg-indigo-600 hover:text-white"
                                                title="Edit account"
                                                aria-label="Edit account"
                                                @click="openEdit(u)"
                                            >
                                                <Pencil class="h-3.5 w-3.5" />
                                            </button>
                                            <button
                                                type="button"
                                                class="ml-1.5 inline-flex h-8 w-8 items-center justify-center rounded-lg border border-red-200 bg-red-50 text-red-600 transition hover:bg-red-600 hover:text-white"
                                                title="Delete account"
                                                aria-label="Delete account"
                                                @click="deleteUser(u)"
                                            >
                                                <Trash2 class="h-3.5 w-3.5" />
                                            </button>
                                        </td>
                                    </tr>

                                    <tr v-if="users.data.length === 0">
                                        <td colspan="6" class="px-6 py-16">
                                            <div
                                                class="flex flex-col items-center gap-3 text-center"
                                            >
                                                <span
                                                    class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400"
                                                >
                                                    <Users class="h-5 w-5" />
                                                </span>
                                                <div>
                                                    <p
                                                        class="text-sm font-bold text-slate-600"
                                                    >
                                                        No accounts found
                                                    </p>
                                                    <p
                                                        class="mt-1 text-xs text-gray-400"
                                                    >
                                                        Try a different search
                                                        or clear the filters.
                                                    </p>
                                                </div>
                                                <button
                                                    v-if="hasFilters"
                                                    type="button"
                                                    class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-bold text-gray-600 transition hover:bg-gray-50"
                                                    @click="clearFilters"
                                                >
                                                    Clear filters
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div
                            class="flex flex-col gap-3 border-t border-gray-100 p-4 sm:flex-row sm:items-center sm:justify-between sm:px-6"
                        >
                            <p class="text-xs font-medium text-gray-500">
                                <template v-if="users.total > 0">
                                    Showing {{ rangeStart }}-{{ rangeEnd }} of
                                    {{ users.total }} accounts
                                </template>
                                <template v-else>No accounts to show</template>
                            </p>
                            <AdminPagination
                                :current-page="users.current_page"
                                :last-page="users.last_page"
                                :url-for="pageUrl"
                            />
                        </div>
                    </AdminSectionCard>
                </div>
            </div>
        </main>

        <!-- Edit modal -->
        <div
            v-if="editingUser"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4 backdrop-blur-sm"
            @click.self="editingUser = null"
        >
            <div
                ref="editUserDialog"
                role="dialog"
                aria-modal="true"
                aria-labelledby="edit-user-title"
                tabindex="-1"
                class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-3xl bg-white shadow-2xl outline-none"
            >
                <div
                    class="flex items-start gap-4 border-b border-gray-100 p-6"
                >
                    <span
                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl text-sm font-black"
                        :class="roleAvatarClass(editingUser.role)"
                        >{{ editingUser.name.charAt(0).toUpperCase() }}</span
                    >
                    <div class="min-w-0 flex-1">
                        <h3
                            id="edit-user-title"
                            class="text-lg font-black tracking-tight text-slate-800"
                        >
                            Edit account
                        </h3>
                        <p class="truncate text-xs text-gray-400">
                            {{ editingUser.email }}
                        </p>
                    </div>
                    <button
                        type="button"
                        class="rounded-lg p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700"
                        aria-label="Close"
                        @click="editingUser = null"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <form @submit.prevent="updateUser" class="space-y-5 p-6">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label
                                class="mb-1.5 block text-[10px] font-extrabold tracking-widest text-gray-500 uppercase"
                                >Full name</label
                            >
                            <input
                                v-model="editForm.name"
                                type="text"
                                class="w-full rounded-xl border border-gray-200 bg-slate-50 px-4 py-2.5 text-sm font-medium text-slate-800 transition outline-none focus:border-indigo-400 focus:bg-white focus:ring-2 focus:ring-indigo-500/15"
                                required
                            />
                        </div>
                        <div>
                            <label
                                class="mb-1.5 block text-[10px] font-extrabold tracking-widest text-gray-500 uppercase"
                                >Email</label
                            >
                            <input
                                v-model="editForm.email"
                                type="email"
                                class="w-full rounded-xl border border-gray-200 bg-slate-50 px-4 py-2.5 text-sm font-medium text-slate-800 transition outline-none focus:border-indigo-400 focus:bg-white focus:ring-2 focus:ring-indigo-500/15"
                                required
                            />
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label
                                class="mb-1.5 block text-[10px] font-extrabold tracking-widest text-gray-500 uppercase"
                                >Role</label
                            >
                            <span
                                class="flex items-center gap-2 rounded-xl border border-gray-200 bg-slate-100 px-4 py-2.5 text-sm font-bold text-gray-600"
                            >
                                <ShieldCheck class="h-4 w-4 text-slate-400" />
                                {{ roleLabel(editForm.role) }}
                            </span>
                        </div>
                        <div>
                            <label
                                class="mb-1.5 block text-[10px] font-extrabold tracking-widest text-gray-500 uppercase"
                                >Status</label
                            >
                            <select
                                v-model="editForm.status"
                                class="w-full rounded-xl border border-gray-200 bg-slate-50 px-4 py-2.5 text-sm font-medium text-slate-800 transition outline-none focus:border-indigo-400 focus:bg-white focus:ring-2 focus:ring-indigo-500/15"
                            >
                                <option value="Register">Register</option>
                                <option value="Walk-in Register">
                                    Walk-in Register
                                </option>
                            </select>
                        </div>
                    </div>

                    <div v-if="editForm.role === 'doctor'">
                        <label
                            class="mb-1.5 block text-[10px] font-extrabold tracking-widest text-gray-500 uppercase"
                            >Specialty</label
                        >
                        <input
                            v-model="editForm.specialty"
                            type="text"
                            class="w-full rounded-xl border border-gray-200 bg-slate-50 px-4 py-2.5 text-sm font-medium text-slate-800 transition outline-none focus:border-indigo-400 focus:bg-white focus:ring-2 focus:ring-indigo-500/15"
                        />
                    </div>

                    <div
                        v-if="editForm.role === 'patient'"
                        class="grid gap-4 sm:grid-cols-2"
                    >
                        <div>
                            <label
                                class="mb-1.5 block text-[10px] font-extrabold tracking-widest text-gray-500 uppercase"
                                >Date of birth</label
                            >
                            <input
                                v-model="editForm.date_of_birth"
                                type="date"
                                class="w-full rounded-xl border border-gray-200 bg-slate-50 px-4 py-2.5 text-sm font-medium text-slate-800 transition outline-none focus:border-indigo-400 focus:bg-white focus:ring-2 focus:ring-indigo-500/15"
                            />
                        </div>
                        <div>
                            <label
                                class="mb-1.5 block text-[10px] font-extrabold tracking-widest text-gray-500 uppercase"
                                >Sex</label
                            >
                            <select
                                v-model="editForm.sex"
                                class="w-full rounded-xl border border-gray-200 bg-slate-50 px-4 py-2.5 text-sm font-medium text-slate-800 transition outline-none focus:border-indigo-400 focus:bg-white focus:ring-2 focus:ring-indigo-500/15"
                            >
                                <option value="">Select...</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label
                            class="mb-1.5 block text-[10px] font-extrabold tracking-widest text-gray-500 uppercase"
                            >New password
                            <span
                                class="ml-1 font-bold tracking-normal text-gray-400 normal-case"
                                >(leave blank to keep current)</span
                            ></label
                        >
                        <PasswordField
                            v-model="editForm.password"
                            placeholder="Min 8 characters"
                            class="w-full rounded-xl border border-gray-200 bg-slate-50 px-4 py-2.5 text-sm font-medium text-slate-800 transition outline-none focus:border-indigo-400 focus:bg-white focus:ring-2 focus:ring-indigo-500/15"
                        />
                        <PasswordRequirements
                            v-if="editForm.password"
                            :password="editForm.password"
                            :min="8"
                            :full="false"
                        />
                    </div>

                    <div
                        class="flex flex-col-reverse gap-2 border-t border-gray-100 pt-4 sm:flex-row sm:justify-end"
                    >
                        <button
                            type="button"
                            class="rounded-xl border border-gray-200 bg-white px-5 py-2.5 text-sm font-bold text-gray-700 transition hover:bg-gray-50"
                            @click="editingUser = null"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="editForm.processing"
                            class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm shadow-indigo-600/20 transition hover:bg-indigo-700 disabled:opacity-50"
                        >
                            {{
                                editForm.processing
                                    ? 'Saving...'
                                    : 'Save changes'
                            }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Link, router, useForm } from '@inertiajs/vue3';
import {
    HeartPulse,
    Pencil,
    Phone,
    Search,
    ShieldCheck,
    Trash2,
    UserPlus,
    UserRound,
    Users,
    X,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import type { Component } from 'vue';
import AdminPageHeader from '@/components/admin/AdminPageHeader.vue';
import AdminPagination from '@/components/admin/AdminPagination.vue';
import AdminSectionCard from '@/components/admin/AdminSectionCard.vue';
import AdminStatCard from '@/components/admin/AdminStatCard.vue';
import AdminHeader from '@/components/AdminHeader.vue';
import AdminSidebar from '@/components/AdminSidebar.vue';
import PasswordField from '@/components/PasswordField.vue';
import PasswordRequirements from '@/components/PasswordRequirements.vue';
import { useModalA11y } from '@/composables/useModalA11y';
import { useToast } from '@/composables/useToast';

type Tone = 'indigo' | 'emerald' | 'sky' | 'amber' | 'red' | 'slate';

interface DirectoryUser {
    id: number;
    system_id?: string | null;
    name: string;
    email: string;
    role: string;
    status?: string | null;
    specialty?: string;
    joined?: string;
    age?: number | null;
    date_of_birth?: string | null;
    sex?: string | null;
}

interface PaginatedUsers {
    data: DirectoryUser[];
    total: number;
    current_page: number;
    last_page: number;
    path: string;
}

interface DirectorySummary {
    total: number;
    doctors: number;
    receptionists: number;
    admins: number;
    patients: number;
}

interface SummaryCard {
    label: string;
    value: number;
    hint: string;
    icon: Component;
    tone: Tone;
}

const { success: showSuccess, error: showError } = useToast();

const props = defineProps<{
    user: { name?: string };
    users: PaginatedUsers;
    summary: DirectorySummary;
    filters: { search?: string; role?: string; status?: string };
}>();

const PER_PAGE = 20;

const search = ref(props.filters.search || '');
const roleFilter = ref(props.filters.role || '');
const statusFilter = ref(props.filters.status || '');

const hasFilters = computed(
    () => !!search.value || !!roleFilter.value || !!statusFilter.value,
);

const resultSummary = computed(() =>
    hasFilters.value
        ? 'Filtered accounts matching your current search'
        : 'Every staff and patient account in the system',
);

const rangeStart = computed(() =>
    props.users.total === 0 ? 0 : (props.users.current_page - 1) * PER_PAGE + 1,
);

const rangeEnd = computed(() =>
    Math.min(props.users.current_page * PER_PAGE, props.users.total),
);

const summaryCards = computed<SummaryCard[]>(() => [
    {
        label: 'Total accounts',
        value: props.summary.total,
        hint: 'All roles',
        icon: Users,
        tone: 'indigo',
    },
    {
        label: 'Cardiologists',
        value: props.summary.doctors,
        hint: 'Clinical',
        icon: HeartPulse,
        tone: 'emerald',
    },
    {
        label: 'Reception staff',
        value: props.summary.receptionists,
        hint: 'Front desk',
        icon: Phone,
        tone: 'sky',
    },
    {
        label: 'Patients',
        value: props.summary.patients,
        hint: 'Portal access',
        icon: UserRound,
        tone: 'amber',
    },
]);

let debounceTimer: ReturnType<typeof setTimeout> | null = null;

const debouncedSearch = () => {
    if (debounceTimer) {
        clearTimeout(debounceTimer);
    }

    debounceTimer = setTimeout(fetchUsers, 300);
};

const fetchUsers = () => {
    router.get(
        '/admin/users-directory',
        {
            search: search.value,
            role: roleFilter.value,
            status: statusFilter.value,
        },
        { preserveState: true, replace: true },
    );
};

const clearFilters = () => {
    search.value = '';
    roleFilter.value = '';
    statusFilter.value = '';
    router.get(
        '/admin/users-directory',
        {},
        { preserveState: true, replace: true },
    );
};

const pageUrl = (page: number): string => {
    const params = new URLSearchParams({ page: String(page) });

    if (props.filters.search) {
        params.set('search', props.filters.search);
    }

    if (props.filters.role) {
        params.set('role', props.filters.role);
    }

    if (props.filters.status) {
        params.set('status', props.filters.status);
    }

    return `${props.users.path}?${params.toString()}`;
};

const roleLabel = (role: string): string => {
    switch (role) {
        case 'doctor':
            return 'Doctor';
        case 'receptionist':
            return 'Receptionist';
        case 'admin':
            return 'Admin';
        case 'patient':
            return 'Patient';
        default:
            return role || 'Unknown';
    }
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
            return 'border-emerald-200 bg-emerald-50 text-emerald-700';
        case 'receptionist':
            return 'border-sky-200 bg-sky-50 text-sky-700';
        case 'admin':
            return 'border-indigo-200 bg-indigo-50 text-indigo-700';
        default:
            return 'border-amber-200 bg-amber-50 text-amber-700';
    }
};

const statusBadgeClass = (status?: string | null): string => {
    return status === 'Register'
        ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
        : 'border-amber-200 bg-amber-50 text-amber-700';
};

const statusDotClass = (status?: string | null): string => {
    return status === 'Register' ? 'bg-emerald-500' : 'bg-amber-500';
};

const editingUser = ref<DirectoryUser | null>(null);
const editUserOpen = computed(() => editingUser.value !== null);
const { container: editUserDialog } = useModalA11y(editUserOpen, () => {
    editingUser.value = null;
});

const editForm = useForm({
    name: '',
    email: '',
    role: '',
    specialty: '',
    password: '',
    date_of_birth: '' as string,
    sex: '' as string,
    status: '' as string,
});

const openEdit = (u: DirectoryUser) => {
    editingUser.value = u;
    editForm.name = u.name;
    editForm.email = u.email;
    editForm.role = u.role;
    editForm.specialty = u.specialty || '';
    editForm.password = '';
    editForm.date_of_birth = u.date_of_birth || '';
    editForm.sex = u.sex || '';
    editForm.status = u.status || '';
};

const updateUser = () => {
    if (!editingUser.value) {
        return;
    }

    editForm.put(`/admin/users/${editingUser.value.id}`, {
        preserveState: true,
        onSuccess: () => {
            editingUser.value = null;
            showSuccess('User account updated.');
        },
        onError: () => showError('Failed to update user.'),
    });
};

const deleteUser = (u: DirectoryUser) => {
    if (!confirm(`Delete ${u.name}? This cannot be undone.`)) {
        return;
    }

    router.delete(`/admin/users/${u.id}`, {
        preserveState: true,
        onSuccess: () => showSuccess('User deleted.'),
        onError: () => showError('Failed to delete user.'),
    });
};
</script>
