<template>
    <div class="flex h-screen bg-[#f1f5f9] font-sans selection:bg-indigo-500 selection:text-white">
        <AdminSidebar />

        <main class="flex h-screen flex-1 flex-col overflow-hidden">
            <header class="flex h-20 shrink-0 items-center justify-between border-b border-gray-200 bg-white px-8 shadow-sm">
                <h2 class="text-xl font-black tracking-tight text-slate-800">Create Account</h2>
                <div class="flex items-center gap-4">
                    <span class="rounded-lg border border-indigo-100 bg-indigo-50 px-3 py-1.5 text-xs font-extrabold tracking-widest text-indigo-700 uppercase">Super Admin</span>
                    <Link href="/profile" class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-600 font-bold text-white shadow-md transition-colors hover:bg-indigo-700" :title="user.name">
                        {{ user.name.charAt(0) }}
                    </Link>
                </div>
            </header>

            <div class="relative flex-1 overflow-y-auto p-8">
                <div class="mx-auto max-w-2xl">
                    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                        <div class="bg-slate-800 px-6 py-5 text-white">
                            <h3 class="flex items-center text-lg font-bold tracking-wide">
                                <svg class="mr-2 h-5 w-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" /></svg>
                                Provision New Account
                            </h3>
                        </div>

                        <form @submit.prevent="submitUser" class="space-y-5 p-6">
                            <p v-if="($page.props as any).flash?.success" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700">{{ ($page.props as any).flash.success }}</p>
                            <p v-if="form.hasErrors" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-700">
                                Could not create the account. Please fix the highlighted fields below and try again.
                            </p>

                            <div>
                                <label class="mb-1.5 block text-[10px] font-extrabold tracking-widest text-gray-500 uppercase">Account Role</label>
                                <select v-model="form.role" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 font-bold text-slate-700 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20" required>
                                    <option value="" disabled>Select Role...</option>
                                    <option value="doctor">Cardiologist (Doctor)</option>
                                    <option value="receptionist">Receptionist (Front Desk)</option>
                                    <option value="admin">System Administrator</option>
                                    <option value="patient">Patient</option>
                                </select>
                                <p v-if="form.errors.role" class="mt-1 text-xs font-bold text-red-500">{{ form.errors.role }}</p>
                            </div>

                            <div>
                                <label class="mb-1.5 block text-[10px] font-extrabold tracking-widest text-gray-500 uppercase">Full Name</label>
                                <input type="text" v-model="form.name" placeholder="e.g. Michael Chen" class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-medium text-slate-800 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20" required />
                                <p v-if="form.errors.name" class="mt-1 text-xs font-bold text-red-500">{{ form.errors.name }}</p>
                                <p v-if="form.role === 'doctor'" class="mt-1 text-[10px] font-bold text-indigo-500 italic">"Dr." will be automatically appended.</p>
                            </div>

                            <div v-if="form.role === 'doctor'" class="animate-fade-in-up">
                                <label class="mb-1.5 block text-[10px] font-extrabold tracking-widest text-gray-500 uppercase">Medical Specialty</label>
                                <input type="text" v-model="form.specialty" placeholder="e.g. Interventional Cardiology" class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-medium text-slate-800 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20" required />
                                <p v-if="form.errors.specialty" class="mt-1 text-xs font-bold text-red-500">{{ form.errors.specialty }}</p>
                            </div>

                            <div>
                                <label class="mb-1.5 block text-[10px] font-extrabold tracking-widest text-gray-500 uppercase">Email Address</label>
                                <input type="email" v-model="form.email" placeholder="email@clinic.com" class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-medium text-slate-800 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20" required />
                                <p v-if="form.errors.email" class="mt-1 text-xs font-bold text-red-500">{{ form.errors.email }}</p>
                            </div>

                            <template v-if="form.role === 'patient'">
                                <div class="animate-fade-in-up grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="mb-1.5 block text-[10px] font-extrabold tracking-widest text-gray-500 uppercase">Age</label>
                                        <input type="number" v-model="form.age" min="1" max="130" placeholder="e.g. 35" class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-medium text-slate-800 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20" />
                                        <p v-if="form.errors.age" class="mt-1 text-xs font-bold text-red-500">{{ form.errors.age }}</p>
                                    </div>
                                    <div>
                                        <label class="mb-1.5 block text-[10px] font-extrabold tracking-widest text-gray-500 uppercase">Sex</label>
                                        <select v-model="form.sex" class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-medium text-slate-800 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                                            <option value="">Select...</option>
                                            <option value="Male">Male</option>
                                            <option value="Female">Female</option>
                                        </select>
                                        <p v-if="form.errors.sex" class="mt-1 text-xs font-bold text-red-500">{{ form.errors.sex }}</p>
                                    </div>
                                </div>
                            </template>

                            <div>
                                <label class="mb-1.5 block text-[10px] font-extrabold tracking-widest text-gray-500 uppercase">Temporary Password</label>
                                <PasswordField v-model="form.password" placeholder="Min 12 characters with mixed case, numbers, symbols" class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-medium text-slate-800 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20" required />
                                <PasswordRequirements :password="form.password" :min="12" />
                                <p v-if="form.errors.password" class="mt-1 text-xs font-bold text-red-500">{{ form.errors.password }}</p>
                            </div>

                            <button type="submit" :disabled="form.processing"
                                class="mt-2 flex w-full items-center justify-center rounded-xl bg-indigo-600 py-3.5 font-bold text-white shadow-md transition-all hover:bg-indigo-700 disabled:opacity-50">
                                <svg v-if="form.processing" class="mr-2 -ml-1 h-4 w-4 animate-spin text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                {{ form.processing ? 'Provisioning...' : 'Create Account' }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>

<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import AdminSidebar from '@/components/AdminSidebar.vue';
import PasswordField from '@/components/PasswordField.vue';
import PasswordRequirements from '@/components/PasswordRequirements.vue';

const props = defineProps<{ user: any }>();

const form = useForm({
    name: '', email: '', password: '', role: '', specialty: '', age: null as number | null, sex: '' as string,
});

const submitUser = () => {
    form.post('/admin/users', {
        preserveState: true,
        onSuccess: () => {
            form.reset('name', 'email', 'password', 'specialty', 'age', 'sex');
            form.role = '';
        },
    });
};
</script>
