<template>
    <div
        class="flex min-h-screen items-center justify-center bg-[#f4f7fa] p-4 font-sans selection:bg-[#0f8c8e] selection:text-white"
    >
        <div
            class="w-full max-w-3xl overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-2xl"
        >
            <!-- Header -->
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
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                        />
                    </svg>
                    Book Returning Patient
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
                    class="mb-8 flex items-center rounded-xl border border-blue-200 bg-blue-50 px-5 py-4 text-blue-800 shadow-sm"
                >
                    <svg
                        class="mr-3 h-5 w-5 text-blue-600"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                    </svg>
                    <p class="text-sm font-semibold">
                        Select an existing patient from the registry to maintain
                        their unified medical history.
                    </p>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Row 1: Patient Selection with Search -->
                    <div>
                        <label
                            class="mb-2 block text-xs font-extrabold tracking-widest text-gray-500 uppercase"
                            >Search Registered Patient</label
                        >
                        <div class="relative">
                            <input
                                type="text"
                                v-model="searchQuery"
                                placeholder="Search by name, ID, or email..."
                                class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 font-medium text-gray-800 transition-all outline-none focus:border-[#0f8c8e] focus:ring-2 focus:ring-[#0f8c8e]/20"
                            />
                            <svg
                                class="pointer-events-none absolute top-1/2 right-4 h-5 w-5 -translate-y-1/2 transform text-gray-400"
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

                        <!-- Dropdown Results -->
                        <div
                            v-if="searchQuery && filteredPatients.length > 0"
                            class="absolute z-50 mt-2 max-h-64 w-full overflow-y-auto rounded-xl border border-gray-200 bg-white shadow-lg"
                        >
                            <div
                                v-for="patient in filteredPatients"
                                :key="patient.id"
                                @click="selectPatient(patient)"
                                class="cursor-pointer border-b border-gray-100 px-4 py-3 transition-colors last:border-0 hover:bg-[#0f8c8e]/10"
                            >
                                <div class="font-medium text-gray-800">
                                    {{ patient.name }}
                                </div>
                                <div class="text-xs text-gray-500">
                                    #{{ patient.id }} • {{ patient.email }}
                                </div>
                            </div>
                        </div>

                        <!-- No Results Message -->
                        <div
                            v-else-if="
                                searchQuery && filteredPatients.length === 0
                            "
                            class="absolute z-50 mt-2 w-full rounded-xl border border-gray-200 bg-white p-4 shadow-lg"
                        >
                            <p class="text-center text-sm text-gray-500">
                                No patients found matching "{{ searchQuery }}"
                            </p>
                        </div>

                        <!-- Selected Patient Display -->
                        <div
                            v-if="form.patient_id && selectedPatient"
                            class="mt-3 rounded-xl border border-[#0f8c8e]/20 bg-[#0f8c8e]/5 p-3"
                        >
                            <p
                                class="text-xs font-semibold text-gray-500 uppercase"
                            >
                                Selected Patient
                            </p>
                            <p class="text-sm font-medium text-gray-800">
                                {{ selectedPatient.name }}
                            </p>
                            <p class="text-xs text-gray-600">
                                #{{ selectedPatient.id }} •
                                {{ selectedPatient.email }}
                            </p>
                        </div>

                        <p
                            v-if="form.errors.patient_id"
                            class="mt-1 text-xs font-bold text-red-500"
                        >
                            {{ form.errors.patient_id }}
                        </p>
                    </div>

                    <!-- Row 2: Doctor Selection -->
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

                    <!-- Row 3: Service Type -->
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

                    <!-- Row 4: Schedule -->
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
                                >Slot Time</label
                            >
                            <input
                                type="time"
                                v-model="form.time"
                                :min="minTime"
                                class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 font-medium text-gray-800 transition-all outline-none focus:border-[#0f8c8e] focus:ring-2 focus:ring-[#0f8c8e]/20"
                                required
                            />
                            <p
                                v-if="timeError"
                                class="mt-1 text-xs font-bold text-red-500"
                            >
                                {{ timeError }}
                            </p>
                            <p
                                v-if="form.errors.time"
                                class="mt-1 text-xs font-bold text-red-500"
                            >
                                {{ form.errors.time }}
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
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
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

interface Patient {
    id: string | number;
    name: string;
    email: string;
}

interface Doctor {
    id: string | number;
    name: string;
    specialty: string;
}

const props = defineProps({
    patients: { type: Array as () => Patient[], required: true },
    doctors: { type: Array as () => Doctor[], required: true },
});

const searchQuery = ref('');

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

const filteredPatients = computed(() => {
    if (!searchQuery.value.trim()) {
        return [];
    }

    const query = searchQuery.value.toLowerCase();

    return props.patients.filter(
        (patient: Patient) =>
            patient.name.toLowerCase().includes(query) ||
            String(patient.id).includes(query) ||
            patient.email.toLowerCase().includes(query),
    );
});

const selectedPatient = computed(() => {
    return (
        props.patients.find((p: Patient) => String(p.id) === form.patient_id) ||
        null
    );
});

const form = useForm({
    patient_id: '',
    doctor_id: '',
    service_type: '',
    date: today.value,
    time: '', // Receptionist must pick a future slot
});

const selectPatient = (patient: Patient) => {
    form.patient_id = String(patient.id);
    searchQuery.value = '';
};

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
    form.post('/appointments/returning');
};
</script>
