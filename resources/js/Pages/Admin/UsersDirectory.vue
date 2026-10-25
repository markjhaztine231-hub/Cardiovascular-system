<template>
    <div
        class="flex h-screen bg-[#f1f5f9] font-sans selection:bg-indigo-500 selection:text-white"
    >
        <AdminSidebar />

        <main class="flex h-screen flex-1 flex-col overflow-hidden">
            <AdminHeader title="Users Directory" :user="user" />

            <div class="relative flex-1 overflow-y-auto p-8">
                <div class="mx-auto max-w-7xl space-y-6">
                    <div class="flex items-center gap-4">
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Search by name, email, or ID..."
                            class="w-80 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-800 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                            @input="debouncedSearch"
                        />
                        <select
                            v-model="roleFilter"
                            class="rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-800 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                            @change="fetchUsers"
                        >
                            <option value="">All Roles</option>
                            <option value="doctor">Doctor</option>
                            <option value="receptionist">Receptionist</option>
                            <option value="admin">Admin</option>
                            <option value="patient">Patient</option>
                        </select>
                        <select
                            v-model="statusFilter"
                            class="rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-800 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                            @change="fetchUsers"
                        >
                            <option value="">All Status</option>
                            <option value="Register">Register</option>
                            <option value="Walk-in Register">
                                Walk-in Register
                            </option>
                        </select>
                        <button
                            @click="clearFilters"
                            class="rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-bold text-gray-600 transition-colors hover:bg-gray-50"
                        >
                            Clear
                        </button>
                        <span class="ml-auto text-sm font-medium text-gray-500"
                            >{{ users.total }} users</span
                        >
                    </div>

                    <div
                        class="max-h-[calc(100vh-14rem)] overflow-y-auto rounded-2xl border border-gray-200 bg-white shadow-sm"
                    >
                        <table class="w-full">
                            <thead class="sticky top-0 z-10">
                                <tr class="border-b border-gray-100 bg-gray-50">
                                    <th
                                        class="px-6 py-4 text-left text-[10px] font-extrabold tracking-widest text-gray-500 uppercase"
                                    >
                                        System ID
                                    </th>
                                    <th
                                        class="px-6 py-4 text-left text-[10px] font-extrabold tracking-widest text-gray-500 uppercase"
                                    >
                                        Name & Email
                                    </th>
                                    <th
                                        class="px-6 py-4 text-left text-[10px] font-extrabold tracking-widest text-gray-500 uppercase"
                                    >
                                        Role
                                    </th>
                                    <th
                                        class="px-6 py-4 text-left text-[10px] font-extrabold tracking-widest text-gray-500 uppercase"
                                    >
                                        Status
                                    </th>
                                    <th
                                        class="px-6 py-4 text-left text-[10px] font-extrabold tracking-widest text-gray-500 uppercase"
                                    >
                                        Joined
                                    </th>
                                    <th
                                        class="px-6 py-4 text-right text-[10px] font-extrabold tracking-widest text-gray-500 uppercase"
                                    >
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr
                                    v-for="u in users.data"
                                    :key="u.id"
                                    class="transition-colors hover:bg-gray-50"
                                >
                                    <td
                                        class="px-6 py-4 font-mono text-sm font-bold text-gray-900"
                                    >
                                        {{ u.system_id || '—' }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <p
                                            class="text-sm font-bold text-gray-700"
                                        >
                                            {{ u.name }}
                                        </p>
                                        <p class="text-xs text-gray-400">
                                            {{ u.email }}
                                        </p>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span
                                            class="rounded-md border px-2.5 py-1 text-[10px] font-extrabold tracking-wider uppercase"
                                            :class="roleBadgeClass(u.role)"
                                            >{{ u.role }}</span
                                        >
                                    </td>
                                    <td class="px-6 py-4">
                                        <span
                                            class="rounded-md border px-2.5 py-1 text-[10px] font-extrabold tracking-wider"
                                            :class="statusBadgeClass(u.status)"
                                            >{{ u.status }}</span
                                        >
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        {{ u.joined }}
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <button
                                            @click="openEdit(u)"
                                            class="rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-[11px] font-bold text-indigo-700 transition-colors hover:bg-indigo-600 hover:text-white"
                                        >
                                            Edit
                                        </button>
                                        <button
                                            @click="deleteUser(u)"
                                            class="ml-2 rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-[11px] font-bold text-red-700 transition-colors hover:bg-red-600 hover:text-white"
                                        >
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="users.data.length === 0">
                                    <td
                                        colspan="6"
                                        class="px-6 py-12 text-center text-sm font-medium text-gray-400"
                                    >
                                        No users found.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div
                        v-if="users.last_page > 1"
                        class="flex items-center justify-center gap-2"
                    >
                        <Link
                            v-for="page in users.last_page"
                            :key="page"
                            :href="
                                users.path +
                                '?page=' +
                                page +
                                (filters.search
                                    ? '&search=' + filters.search
                                    : '') +
                                (filters.role ? '&role=' + filters.role : '') +
                                (filters.status
                                    ? '&status=' + filters.status
                                    : '')
                            "
                            class="rounded-lg border px-3 py-1.5 text-sm font-bold transition-colors"
                            :class="
                                page === users.current_page
                                    ? 'border-indigo-500 bg-indigo-600 text-white'
                                    : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'
                            "
                        >
                            {{ page }}
                        </Link>
                    </div>
                </div>
            </div>
        </main>

        <!-- Edit Modal -->
        <div
            v-if="editingUser"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
            @click.self="editingUser = null"
        >
            <div
                ref="editUserDialog"
                role="dialog"
                aria-modal="true"
                aria-labelledby="edit-user-title"
                tabindex="-1"
                class="w-full max-w-lg rounded-2xl bg-white p-8 shadow-2xl outline-none"
            >
                <h3
                    id="edit-user-title"
                    class="mb-6 text-lg font-bold text-gray-800"
                >
                    Edit User
                </h3>
                <form @submit.prevent="updateUser" class="space-y-4">
                    <div>
                        <label
                            class="mb-1.5 block text-[10px] font-extrabold tracking-widest text-gray-500 uppercase"
                            >Role</label
                        >
                        <span
                            class="block rounded-xl border border-gray-200 bg-gray-100 px-4 py-3 text-sm font-bold text-gray-600"
                            >{{ editForm.role }}</span
                        >
                    </div>
                    <div>
                        <label
                            class="mb-1.5 block text-[10px] font-extrabold tracking-widest text-gray-500 uppercase"
                            >Status</label
                        >
                        <select
                            v-model="editForm.status"
                            class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-medium text-slate-800 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                        >
                            <option value="Register">Register</option>
                            <option value="Walk-in Register">
                                Walk-in Register
                            </option>
                        </select>
                    </div>
                    <div>
                        <label
                            class="mb-1.5 block text-[10px] font-extrabold tracking-widest text-gray-500 uppercase"
                            >Full Name</label
                        >
                        <input
                            v-model="editForm.name"
                            type="text"
                            class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-medium text-slate-800 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                            required
                        />
                    </div>
                    <div v-if="editForm.role === 'doctor'">
                        <label
                            class="mb-1.5 block text-[10px] font-extrabold tracking-widest text-gray-500 uppercase"
                            >Specialty</label
                        >
                        <input
                            v-model="editForm.specialty"
                            type="text"
                            class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-medium text-slate-800 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
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
                            class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-medium text-slate-800 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                            required
                        />
                    </div>
                    <template v-if="editForm.role === 'patient'">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label
                                    class="mb-1.5 block text-[10px] font-extrabold tracking-widest text-gray-500 uppercase"
                                    >Date of Birth</label
                                >
                                <input
                                    v-model="editForm.date_of_birth"
                                    type="date"
                                    class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-medium text-slate-800 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                                />
                            </div>
                            <div>
                                <label
                                    class="mb-1.5 block text-[10px] font-extrabold tracking-widest text-gray-500 uppercase"
                                    >Sex</label
                                >
                                <select
                                    v-model="editForm.sex"
                                    class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-medium text-slate-800 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                                >
                                    <option value="">Select...</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                            </div>
                        </div>
                    </template>
                    <div>
                        <label
                            class="mb-1.5 block text-[10px] font-extrabold tracking-widest text-gray-500 uppercase"
                            >New Password (leave blank to keep current)</label
                        >
                        <PasswordField
                            v-model="editForm.password"
                            placeholder="Min 8 characters"
                            class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-medium text-slate-800 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                        />
                        <PasswordRequirements
                            v-if="editForm.password"
                            :password="editForm.password"
                            :min="8"
                            :full="false"
                        />
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button
                            type="button"
                            @click="editingUser = null"
                            class="rounded-xl border border-gray-200 bg-white px-6 py-2.5 text-sm font-bold text-gray-700 transition-colors hover:bg-gray-50"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="editForm.processing"
                            class="rounded-xl bg-indigo-600 px-6 py-2.5 text-sm font-bold text-white transition-colors hover:bg-indigo-700 disabled:opacity-50"
                        >
                            {{
                                editForm.processing
                                    ? 'Saving...'
                                    : 'Save Changes'
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
import { computed, ref } from 'vue';
import AdminHeader from '@/components/AdminHeader.vue';
import AdminSidebar from '@/components/AdminSidebar.vue';
import PasswordField from '@/components/PasswordField.vue';
import PasswordRequirements from '@/components/PasswordRequirements.vue';
import { useModalA11y } from '@/composables/useModalA11y';
import { useToast } from '@/composables/useToast';

const { success: showSuccess, error: showError } = useToast();

const props = defineProps<{
    user: any;
    users: any;
    filters: { search?: string; role?: string; status?: string };
}>();

const search = ref(props.filters.search || '');
const roleFilter = ref(props.filters.role || '');
const statusFilter = ref(props.filters.status || '');

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

const roleBadgeClass = (role: string): string => {
    switch (role) {
        case 'doctor':
            return 'border-emerald-200 bg-emerald-50 text-emerald-700';
        case 'receptionist':
            return 'border-blue-200 bg-blue-50 text-blue-700';
        case 'admin':
            return 'border-purple-200 bg-purple-50 text-purple-700';
        default:
            return 'border-gray-200 bg-gray-50 text-gray-700';
    }
};

const statusBadgeClass = (status: string): string => {
    return status === 'Register'
        ? 'border-green-200 bg-green-50 text-green-700'
        : 'border-amber-200 bg-amber-50 text-amber-700';
};

const editingUser = ref<any>(null);
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

const openEdit = (u: any) => {
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
    editForm.put(`/admin/users/${editingUser.value.id}`, {
        preserveState: true,
        onSuccess: () => {
            editingUser.value = null;
            showSuccess('User account updated.');
        },
        onError: () => showError('Failed to update user.'),
    });
};

const deleteUser = (u: any) => {
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
