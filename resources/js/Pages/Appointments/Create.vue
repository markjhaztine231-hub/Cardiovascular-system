<template>
    <div
        class="flex min-h-screen items-center justify-center bg-[#f4f7fa] p-4 font-sans selection:bg-[#0f8c8e] selection:text-white"
    >
        <!-- Wide Modal Card -->
        <div
            class="w-full max-w-3xl overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-2xl"
        >
            <!-- Modal Header -->
            <div
                class="flex items-center justify-between bg-[#0f8c8e] px-8 py-5 text-white"
            >
                <h2 class="flex items-center text-xl font-bold tracking-wide">
                    <svg
                        class="mr-3 h-6 w-6 text-teal-100"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"
                        />
                    </svg>
                    Manual Booking
                </h2>
                <Link
                    href="/receptionist/dashboard"
                    class="text-teal-100 transition-colors hover:text-white"
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
                            d="M6 18L18 6M6 6l12 12"
                        />
                    </svg>
                </Link>
            </div>

            <div class="p-8">
                <!-- Info Alert -->
                <div
                    class="mb-8 flex items-center rounded-xl border border-[#bbf7d0] bg-[#f0fdf4] px-5 py-4 text-[#166534] shadow-sm"
                >
                    <svg
                        class="mr-3 h-5 w-5 text-[#16a34a]"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                    </svg>
                    <p class="text-sm font-semibold">
                        Assisted Mode: Use this for walk-in patients or those
                        without app access.
                    </p>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Row 1: Full Name -->
                    <div>
                        <label
                            class="mb-2 block text-xs font-extrabold tracking-widest text-gray-500 uppercase"
                            >Patient Name</label
                        >
                        <input
                            type="text"
                            v-model="form.name"
                            placeholder="Full Name"
                            class="w-full rounded-xl border border-gray-200 px-4 py-3 font-medium text-gray-800 transition-all outline-none focus:border-[#0f8c8e] focus:ring-2 focus:ring-[#0f8c8e]/20"
                            required
                        />
                        <p
                            v-if="form.errors.name"
                            class="mt-1 text-xs font-bold text-red-500"
                        >
                            {{ form.errors.name }}
                        </p>
                    </div>

                    <!-- Row 2: Date of Birth & Gender -->
                    <div v-if="!isProcedureOnly" class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div>
                            <label
                                class="mb-2 block text-xs font-extrabold tracking-widest text-gray-500 uppercase"
                                >Date of Birth</label
                            >
                            <input
                                type="date"
                                v-model="form.date_of_birth"
                                :max="minDob"
                                class="w-full rounded-xl border border-gray-200 px-4 py-3 font-medium text-gray-800 transition-all outline-none focus:border-[#0f8c8e] focus:ring-2 focus:ring-[#0f8c8e]/20"
                                :required="!isProcedureOnly"
                            />
                            <p class="mt-1 text-xs text-gray-500">
                                Must be at least 1 year old
                            </p>
                            <p
                                v-if="form.errors.date_of_birth"
                                class="mt-1 text-xs font-bold text-red-500"
                            >
                                {{ form.errors.date_of_birth }}
                            </p>
                        </div>
                        <div>
                            <label
                                class="mb-2 block text-xs font-extrabold tracking-widest text-gray-500 uppercase"
                                >Sex</label
                            >
                            <select
                                v-model="form.gender"
                                class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 font-medium text-gray-800 transition-all outline-none focus:border-[#0f8c8e] focus:ring-2 focus:ring-[#0f8c8e]/20"
                                :required="!isProcedureOnly"
                            >
                                <option value="" disabled>Select Sex</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                            <p
                                v-if="form.errors.gender"
                                class="mt-1 text-xs font-bold text-red-500"
                            >
                                {{ form.errors.gender }}
                            </p>
                        </div>
                    </div>

                    <!-- Row 3: Phone & Doctor -->
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div v-if="!isProcedureOnly">
                            <label
                                class="mb-2 block text-xs font-extrabold tracking-widest text-gray-500 uppercase"
                                >Phone Number</label
                            >
                            <input
                                type="text"
                                v-model="form.phone"
                                placeholder="0917..."
                                class="w-full rounded-xl border border-gray-200 px-4 py-3 font-medium text-gray-800 transition-all outline-none focus:border-[#0f8c8e] focus:ring-2 focus:ring-[#0f8c8e]/20"

                            />
                            <p
                                v-if="form.errors.phone"
                                class="mt-1 text-xs font-bold text-red-500"
                            >
                                {{ form.errors.phone }}
                            </p>
                        </div>
                        <div>
                            <label
                                class="mb-2 block text-xs font-extrabold tracking-widest text-gray-500 uppercase"
                                >Available Doctor</label
                            >
                            <select
                                v-model="form.doctor_id"
                                class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 font-medium text-gray-800 transition-all outline-none focus:border-[#0f8c8e] focus:ring-2 focus:ring-[#0f8c8e]/20"
                                required
                            >
                                <option value="" disabled>
                                    Assign to a Cardiologist...
                                </option>
                                <option
                                    v-for="doc in doctors"
                                    :key="doc.id"
                                    :value="doc.id"
                                >
                                    {{ doc.name }} ({{ doc.specialty }})
                                </option>
                            </select>
                            <p
                                v-if="form.errors.doctor_id"
                                class="mt-1 text-xs font-bold text-red-500"
                            >
                                {{ form.errors.doctor_id }}
                            </p>
                        </div>
                    </div>

                    <!-- Row 4: Service Type -->
                    <div>
                        <label
                            class="mb-2 block text-xs font-extrabold tracking-widest text-gray-500 uppercase"
                            >Service Type</label
                        >
                        <select
                            v-model="form.service_type"
                            class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 font-medium text-gray-800 transition-all outline-none focus:border-[#0f8c8e] focus:ring-2 focus:ring-[#0f8c8e]/20"
                            required
                        >
                            <option value="" disabled>Select service...</option>
                            <option value="Consultation">Consultation</option>
                            <option value="Procedure">Procedure</option>
                            <option value="Consultation and Procedure">
                                Consultation and Procedure
                            </option>
                        </select>
                        <p
                            v-if="form.errors.service_type"
                            class="mt-1 text-xs font-bold text-red-500"
                        >
                            {{ form.errors.service_type }}
                        </p>
                    </div>

                    <!-- Row 5: Schedule (Date & Time) -->
                    <div
                        class="grid grid-cols-1 gap-6 rounded-xl border border-gray-100 bg-gray-50 p-5 md:grid-cols-2"
                    >
                        <div>
                            <label
                                class="mb-2 block text-xs font-extrabold tracking-widest text-gray-500 uppercase"
                                >Slot Date</label
                            >
                            <input
                                type="date"
                                v-model="form.date"
                                :min="today"
                                class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 font-medium text-gray-800 transition-all outline-none focus:border-[#0f8c8e] focus:ring-2 focus:ring-[#0f8c8e]/20"
                                required
                            />
                        </div>
                        <div>
                            <label
                                class="mb-2 block text-xs font-extrabold tracking-widest text-gray-500 uppercase"
                                >Time Slot</label
                            >
                            <select
                                v-model="form.time_slot"
                                required
                                class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 font-medium text-gray-800 transition-all outline-none focus:border-[#0f8c8e] focus:ring-2 focus:ring-[#0f8c8e]/20"
                            >
                                <option value="" disabled>Select Time Slot</option>
                                <option value="morning">Morning (6:00 AM - 12:00 PM)</option>
                                <option value="afternoon">Afternoon (1:00 PM - 6:00 PM)</option>
                            </select>
                            <p
                                v-if="form.errors.time_slot"
                                class="mt-1 text-xs font-bold text-red-500"
                            >
                                {{ form.errors.time_slot }}
                            </p>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div
                        class="flex items-center justify-end gap-4 border-t border-gray-100 pt-6"
                    >
                        <Link
                            href="/receptionist/dashboard"
                            class="px-6 py-3 text-sm font-bold text-gray-500 transition-colors hover:text-gray-800"
                        >
                            Cancel
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="flex items-center rounded-xl bg-[#0f8c8e] px-8 py-3 text-sm font-bold text-white shadow-md transition-all hover:bg-[#0c7072] hover:shadow-lg disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <svg
                                v-if="form.processing"
                                class="mr-2 -ml-1 h-4 w-4 animate-spin text-white"
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
                            <svg
                                v-else
                                class="mr-2 h-5 w-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                />
                            </svg>
                            {{
                                form.processing
                                    ? 'Adding to Queue...'
                                    : 'Add to Queue'
                            }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { useForm, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface Doctor {
    id: string | number;
    name: string;
    specialty: string;
}

defineProps({
    doctors: {
        type: Array as () => Doctor[],
        required: true,
    },
});

// Today's date in local time so the receptionist can't book into the past.
const today = computed(() => {
    const d = new Date();
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');

    return `${y}-${m}-${day}`;
});

const timeError = ref('');
const padTime = (n: number): string => String(n).padStart(2, '0');
const isTodayBooking = computed(() => form.date === today.value);

// Earliest selectable time = "now"; the native picker refuses anything past it.
const minTime = computed((): string =>
    isTodayBooking.value
        ? `${padTime(new Date().getHours())}:${padTime(new Date().getMinutes())}`
        : '',
);

const form = useForm({
    name: '',
    date_of_birth: '',
    gender: '',
    phone: '',
    doctor_id: '',
    service_type: '',
    date: '',
    time_slot: '',
});

const minDob = computed(() => {
    const d = new Date();
    d.setFullYear(d.getFullYear() - 1);
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');

    return `${y}-${m}-${day}`;
});

const isProcedureOnly = computed(() => form.service_type === 'Procedure');

const submit = () => {
    if (isTodayBooking.value && form.time) {
        const [hh, mm] = form.time.split(':').map(Number);
        const slotMinutes = hh * 60 + mm;
        const now = new Date();
        const nowMinutes = now.getHours() * 60 + now.getMinutes();

        if (slotMinutes <= nowMinutes) {
            timeError.value =
                'That time has already passed — please pick a future time.';

            return;
        }
    }

    timeError.value = '';
    form.post('/appointments/manual');
};
</script>
