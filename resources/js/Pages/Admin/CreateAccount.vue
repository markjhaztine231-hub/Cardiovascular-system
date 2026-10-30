<template>
    <div
        class="flex h-screen bg-[#f1f5f9] font-sans selection:bg-indigo-500 selection:text-white"
    >
        <AdminSidebar :user="user" />

        <main class="flex h-screen flex-1 flex-col overflow-hidden">
            <AdminHeader title="Create Account" :user="user" />

            <div class="relative flex-1 overflow-y-auto">
                <div class="mx-auto max-w-7xl space-y-6 p-5 lg:p-8">
                    <AdminPageHeader
                        eyebrow="Account provisioning"
                        title="Create a new account"
                        description="Set up secure access for a cardiologist, receptionist, administrator, or patient. The new account is audit-logged automatically."
                    >
                        <template #actions>
                            <Link
                                href="/admin/users-directory"
                                class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-bold text-gray-700 shadow-sm transition hover:bg-gray-50"
                            >
                                <Users class="h-4 w-4" />
                                Back to directory
                            </Link>
                        </template>
                    </AdminPageHeader>

                    <div class="grid gap-6 xl:grid-cols-3">
                        <div class="xl:col-span-2">
                            <AdminSectionCard
                                title="Account details"
                                subtitle="Choose the access level first, then enter the account owner's details."
                            >
                                <form
                                    class="space-y-6"
                                    @submit.prevent="submitUser"
                                >
                                    <div
                                        v-if="flashSuccess"
                                        class="flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3"
                                    >
                                        <CheckCircle2
                                            class="mt-0.5 h-4 w-4 shrink-0 text-emerald-600"
                                        />
                                        <p
                                            class="text-sm font-bold text-emerald-700"
                                        >
                                            {{ flashSuccess }}
                                        </p>
                                    </div>
                                    <div
                                        v-if="form.hasErrors"
                                        class="flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3"
                                    >
                                        <AlertCircle
                                            class="mt-0.5 h-4 w-4 shrink-0 text-red-500"
                                        />
                                        <p
                                            class="text-sm font-bold text-red-700"
                                        >
                                            Could not create the account. Please
                                            fix the highlighted fields below and
                                            try again.
                                        </p>
                                    </div>

                                    <div>
                                        <label
                                            class="mb-2 block text-[10px] font-extrabold tracking-widest text-gray-500 uppercase"
                                            >Account role</label
                                        >
                                        <div
                                            class="grid grid-cols-1 gap-3 sm:grid-cols-2"
                                        >
                                            <button
                                                v-for="role in roles"
                                                :key="role.value"
                                                type="button"
                                                class="group relative flex items-start gap-3 rounded-xl border p-4 text-left transition"
                                                :class="
                                                    form.role === role.value
                                                        ? 'border-indigo-400 bg-indigo-50/70 ring-2 ring-indigo-500/15'
                                                        : 'border-gray-200 bg-white hover:border-indigo-200 hover:bg-indigo-50/30'
                                                "
                                                @click="form.role = role.value"
                                            >
                                                <span
                                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl"
                                                    :class="role.iconClass"
                                                >
                                                    <component
                                                        :is="role.icon"
                                                        class="h-5 w-5"
                                                    />
                                                </span>
                                                <span class="min-w-0">
                                                    <span
                                                        class="block text-sm font-bold text-slate-800"
                                                        >{{ role.label }}</span
                                                    >
                                                    <span
                                                        class="mt-0.5 block text-[11px] leading-snug text-gray-500"
                                                        >{{
                                                            role.description
                                                        }}</span
                                                    >
                                                </span>
                                                <span
                                                    v-if="
                                                        form.role === role.value
                                                    "
                                                    class="absolute top-3 right-3 flex h-5 w-5 items-center justify-center rounded-full bg-indigo-600 text-white"
                                                >
                                                    <Check class="h-3 w-3" />
                                                </span>
                                            </button>
                                        </div>
                                        <p
                                            v-if="form.errors.role"
                                            class="mt-1.5 text-xs font-bold text-red-500"
                                        >
                                            {{ form.errors.role }}
                                        </p>
                                    </div>

                                    <div class="grid gap-4 sm:grid-cols-2">
                                        <div>
                                            <label
                                                class="mb-1.5 block text-[10px] font-extrabold tracking-widest text-gray-500 uppercase"
                                                >Full name</label
                                            >
                                            <input
                                                v-model="form.name"
                                                type="text"
                                                placeholder="e.g. Michael Chen"
                                                :class="inputClass"
                                                required
                                            />
                                            <p
                                                v-if="form.errors.name"
                                                class="mt-1 text-xs font-bold text-red-500"
                                            >
                                                {{ form.errors.name }}
                                            </p>
                                            <p
                                                v-if="form.role === 'doctor'"
                                                class="mt-1 text-[10px] font-bold text-indigo-500 italic"
                                            >
                                                "Dr." will be automatically
                                                appended.
                                            </p>
                                        </div>
                                        <div>
                                            <label
                                                class="mb-1.5 block text-[10px] font-extrabold tracking-widest text-gray-500 uppercase"
                                                >Email address</label
                                            >
                                            <input
                                                v-model="form.email"
                                                type="email"
                                                placeholder="email@clinic.com"
                                                :class="inputClass"
                                                required
                                            />
                                            <p
                                                v-if="form.errors.email"
                                                class="mt-1 text-xs font-bold text-red-500"
                                            >
                                                {{ form.errors.email }}
                                            </p>
                                        </div>
                                    </div>

                                    <div
                                        v-if="form.role === 'doctor'"
                                        class="animate-fade-in-up"
                                    >
                                        <label
                                            class="mb-1.5 block text-[10px] font-extrabold tracking-widest text-gray-500 uppercase"
                                            >Medical specialty</label
                                        >
                                        <input
                                            v-model="form.specialty"
                                            type="text"
                                            placeholder="e.g. Interventional Cardiology"
                                            :class="inputClass"
                                            required
                                        />
                                        <p
                                            v-if="form.errors.specialty"
                                            class="mt-1 text-xs font-bold text-red-500"
                                        >
                                            {{ form.errors.specialty }}
                                        </p>
                                    </div>

                                    <div
                                        v-if="form.role === 'patient'"
                                        class="animate-fade-in-up grid gap-4 sm:grid-cols-2"
                                    >
                                        <div>
                                            <label
                                                class="mb-1.5 block text-[10px] font-extrabold tracking-widest text-gray-500 uppercase"
                                                >Date of birth</label
                                            >
                                            <input
                                                v-model="form.date_of_birth"
                                                type="date"
                                                :class="inputClass"
                                            />
                                            <p
                                                v-if="form.errors.date_of_birth"
                                                class="mt-1 text-xs font-bold text-red-500"
                                            >
                                                {{ form.errors.date_of_birth }}
                                            </p>
                                        </div>
                                        <div>
                                            <label
                                                class="mb-1.5 block text-[10px] font-extrabold tracking-widest text-gray-500 uppercase"
                                                >Sex</label
                                            >
                                            <select
                                                v-model="form.sex"
                                                :class="inputClass"
                                            >
                                                <option value="">
                                                    Select...
                                                </option>
                                                <option value="Male">
                                                    Male
                                                </option>
                                                <option value="Female">
                                                    Female
                                                </option>
                                            </select>
                                            <p
                                                v-if="form.errors.sex"
                                                class="mt-1 text-xs font-bold text-red-500"
                                            >
                                                {{ form.errors.sex }}
                                            </p>
                                        </div>
                                    </div>

                                    <div>
                                        <label
                                            class="mb-1.5 block text-[10px] font-extrabold tracking-widest text-gray-500 uppercase"
                                            >Temporary password</label
                                        >
                                        <PasswordField
                                            v-model="form.password"
                                            placeholder="Min 12 characters with mixed case, numbers, symbols"
                                            :class="inputClass"
                                            required
                                        />
                                        <PasswordRequirements
                                            :password="form.password"
                                            :min="12"
                                        />
                                        <p
                                            v-if="form.errors.password"
                                            class="mt-1 text-xs font-bold text-red-500"
                                        >
                                            {{ form.errors.password }}
                                        </p>
                                    </div>

                                    <div
                                        class="flex flex-col-reverse gap-2 border-t border-gray-100 pt-5 sm:flex-row sm:items-center sm:justify-between"
                                    >
                                        <p
                                            class="text-[11px] font-medium text-gray-400"
                                        >
                                            The account owner can change their
                                            password after signing in.
                                        </p>
                                        <div
                                            class="flex flex-col-reverse gap-2 sm:flex-row"
                                        >
                                            <Link
                                                href="/admin/users-directory"
                                                class="rounded-xl border border-gray-200 bg-white px-5 py-2.5 text-center text-sm font-bold text-gray-700 transition hover:bg-gray-50"
                                            >
                                                Cancel
                                            </Link>
                                            <button
                                                type="submit"
                                                :disabled="form.processing"
                                                class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm shadow-indigo-600/20 transition hover:bg-indigo-700 disabled:opacity-50"
                                            >
                                                <Loader2
                                                    v-if="form.processing"
                                                    class="h-4 w-4 animate-spin"
                                                />
                                                <UserPlus
                                                    v-else
                                                    class="h-4 w-4"
                                                />
                                                {{
                                                    form.processing
                                                        ? 'Provisioning...'
                                                        : 'Create account'
                                                }}
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </AdminSectionCard>
                        </div>

                        <div class="space-y-6">
                            <AdminSectionCard
                                title="Selected access"
                                subtitle="A quick preview of what this account can do."
                            >
                                <div
                                    v-if="selectedRole"
                                    class="rounded-2xl border border-indigo-100 bg-indigo-50/60 p-4"
                                >
                                    <span
                                        class="flex h-11 w-11 items-center justify-center rounded-xl"
                                        :class="selectedRole.iconClass"
                                    >
                                        <component
                                            :is="selectedRole.icon"
                                            class="h-5 w-5"
                                        />
                                    </span>
                                    <p
                                        class="mt-3 text-sm font-black text-slate-800"
                                    >
                                        {{ selectedRole.label }}
                                    </p>
                                    <p
                                        class="mt-1 text-xs leading-relaxed text-gray-500"
                                    >
                                        {{ selectedRole.description }}
                                    </p>
                                    <ul class="mt-4 space-y-2">
                                        <li
                                            v-for="ability in selectedRole.abilities"
                                            :key="ability"
                                            class="flex items-start gap-2 text-[11px] font-semibold text-slate-600"
                                        >
                                            <Check
                                                class="mt-0.5 h-3.5 w-3.5 shrink-0 text-indigo-500"
                                            />
                                            {{ ability }}
                                        </li>
                                    </ul>
                                </div>
                                <div
                                    v-else
                                    class="rounded-2xl border border-dashed border-gray-200 bg-gray-50 p-6 text-center"
                                >
                                    <UserRound
                                        class="mx-auto h-6 w-6 text-gray-300"
                                    />
                                    <p
                                        class="mt-2 text-xs font-bold text-gray-500"
                                    >
                                        Select a role to preview its access.
                                    </p>
                                </div>
                            </AdminSectionCard>

                            <AdminSectionCard
                                title="Provisioning safeguards"
                                subtitle="Security is applied automatically."
                            >
                                <ul class="space-y-4">
                                    <li
                                        v-for="check in safeguards"
                                        :key="check.title"
                                        class="flex items-start gap-3"
                                    >
                                        <span
                                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg"
                                            :class="check.iconClass"
                                        >
                                            <component
                                                :is="check.icon"
                                                class="h-4 w-4"
                                            />
                                        </span>
                                        <div>
                                            <p
                                                class="text-xs font-bold text-slate-700"
                                            >
                                                {{ check.title }}
                                            </p>
                                            <p
                                                class="mt-0.5 text-[11px] leading-snug text-gray-500"
                                            >
                                                {{ check.description }}
                                            </p>
                                        </div>
                                    </li>
                                </ul>
                            </AdminSectionCard>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>

<script setup lang="ts">
import { Link, useForm, usePage } from '@inertiajs/vue3';
import {
    AlertCircle,
    Check,
    CheckCircle2,
    FileClock,
    HeartPulse,
    Loader2,
    LockKeyhole,
    Phone,
    ShieldCheck,
    UserPlus,
    UserRound,
    Users,
} from 'lucide-vue-next';
import { computed } from 'vue';
import type { Component } from 'vue';
import AdminPageHeader from '@/components/admin/AdminPageHeader.vue';
import AdminSectionCard from '@/components/admin/AdminSectionCard.vue';
import AdminHeader from '@/components/AdminHeader.vue';
import AdminSidebar from '@/components/AdminSidebar.vue';
import PasswordField from '@/components/PasswordField.vue';
import PasswordRequirements from '@/components/PasswordRequirements.vue';

interface RoleOption {
    value: string;
    label: string;
    description: string;
    icon: Component;
    iconClass: string;
    abilities: string[];
}

defineProps<{ user: { name?: string } }>();

const page = usePage();

const inputClass =
    'w-full rounded-xl border border-gray-200 bg-slate-50 px-4 py-2.5 text-sm font-medium text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-indigo-400 focus:bg-white focus:ring-2 focus:ring-indigo-500/15';

const form = useForm({
    name: '',
    email: '',
    password: '',
    role: '',
    specialty: '',
    date_of_birth: null as string | null,
    sex: '' as string,
});

const flashSuccess = computed(
    () => (page.props as any).flash?.success as string | undefined,
);

const roles: RoleOption[] = [
    {
        value: 'doctor',
        label: 'Cardiologist',
        description: 'Clinical access to consultations and patient records.',
        icon: HeartPulse,
        iconClass: 'bg-emerald-50 text-emerald-600',
        abilities: [
            'Consultations, SOAP notes, and study readings',
            'Patient history and clinical documents',
            'Prescriptions and follow-up scheduling',
        ],
    },
    {
        value: 'receptionist',
        label: 'Receptionist',
        description: 'Front desk access for registration and scheduling.',
        icon: Phone,
        iconClass: 'bg-sky-50 text-sky-600',
        abilities: [
            'Patient registration and appointment scheduling',
            'Document intake and OCR review',
            'Queue, transfers, and reschedule requests',
        ],
    },
    {
        value: 'admin',
        label: 'Administrator',
        description: 'Full console access, including account management.',
        icon: ShieldCheck,
        iconClass: 'bg-indigo-50 text-indigo-600',
        abilities: [
            'Create, edit, suspend, and delete accounts',
            'Review the complete audit trail',
            'Access every administrative dashboard',
        ],
    },
    {
        value: 'patient',
        label: 'Patient',
        description: 'Portal access limited to the patient’s own record.',
        icon: UserRound,
        iconClass: 'bg-amber-50 text-amber-600',
        abilities: [
            'View upcoming and past appointments',
            'Read prescriptions and medication reminders',
            'Request reschedules and download records',
        ],
    },
];

const selectedRole = computed(
    () => roles.find((role) => role.value === form.role) ?? null,
);

const safeguards = [
    {
        title: 'Strong password required',
        description:
            'At least 12 characters with mixed case, numbers, and symbols.',
        icon: LockKeyhole,
        iconClass: 'bg-indigo-50 text-indigo-600',
    },
    {
        title: 'Audit-logged automatically',
        description:
            'Every created account is recorded with the acting administrator.',
        icon: FileClock,
        iconClass: 'bg-slate-100 text-slate-600',
    },
    {
        title: 'Access can be revoked',
        description:
            'Accounts can be suspended or removed from the directory at any time.',
        icon: ShieldCheck,
        iconClass: 'bg-emerald-50 text-emerald-600',
    },
];

const submitUser = () => {
    form.post('/admin/users', {
        preserveState: true,
        onSuccess: () => {
            form.reset(
                'name',
                'email',
                'password',
                'specialty',
                'date_of_birth',
                'sex',
            );
            form.role = '';
        },
    });
};
</script>
