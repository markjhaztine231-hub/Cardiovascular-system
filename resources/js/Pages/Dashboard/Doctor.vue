<template>
    <div
        class="flex h-screen bg-[#f4f7fa] font-sans selection:bg-[#15818c] selection:text-white"
    >
        <!-- Toast Notifications -->
        <Toast />
        <!-- SIDEBAR -->
        <aside
            class="z-20 hidden w-64 shrink-0 flex-col bg-[#146b8a] text-white shadow-xl md:flex"
        >
            <div
                class="flex h-20 items-center border-b border-[#1b7f9e] bg-[#0e5c78] px-6"
            >
                <div
                    class="mr-3 rounded-lg bg-white p-2 text-xl font-bold text-[#146b8a] shadow-md"
                >
                    ♥
                </div>
                <div>
                    <h1 class="text-lg leading-tight font-bold tracking-wide">
                        CardioCare
                    </h1>
                    <p
                        class="text-[10px] tracking-widest text-teal-100 uppercase"
                    >
                        Healthcare Portal
                    </p>
                </div>
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
                            class="flex items-center border-l-4 border-white bg-[#11888a] px-6 py-3 font-medium text-white shadow-inner transition-colors"
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
                            class="flex items-center border-l-4 border-transparent px-6 py-3 text-teal-100 transition-colors hover:bg-[#1a7f9e] hover:text-white"
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
                    <li>
                        <Link
                            href="/doctor/reports/monthly"
                            class="flex items-center px-6 py-3 text-teal-100 transition-colors hover:bg-[#1a7f9e] hover:text-white"
                            :class="{
                                'border-l-4 border-white bg-[#11888a] font-medium text-white shadow-inner':
                                    $page.url === '/doctor/reports/monthly',
                            }"
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
            <div class="space-y-1 border-t border-[#1b7f9e] p-4">
                <Link
                    href="/profile"
                    class="flex w-full items-center rounded-lg px-4 py-2 text-sm font-medium text-teal-100 transition-colors hover:bg-[#1a7f9e] hover:text-white"
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
                    My Profile
                </Link>
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
        </aside>

        <!-- MAIN CONTENT -->
        <main class="flex h-screen flex-1 flex-col overflow-hidden">
            <header
                class="z-10 flex h-20 shrink-0 items-center justify-between border-b border-gray-200 bg-white px-8 shadow-sm"
            >
                <h2 class="text-xl font-bold text-gray-800">
                    Physician Dashboard
                </h2>
                <div class="flex items-center gap-6">
                    <!-- Notification Bell -->
                    <div class="relative">
                        <!-- Backdrop: click anywhere outside to close -->
                        <div
                            v-if="showDoctorNotifications"
                            class="fixed inset-0 z-40"
                            @click="showDoctorNotifications = false"
                        ></div>
                        <button
                            @click="
                                showDoctorNotifications =
                                    !showDoctorNotifications
                            "
                            class="relative rounded-full p-2 text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-700"
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
                                    d="M15 17h5l-1.405-1.405C18.21 14.79 18 13.9 18 13V11a6 6 0 10-12 0v2c0 .9-.21 1.79-.595 2.595L4 17h5m6 0a3 3 0 11-6 0h6z"
                                />
                            </svg>
                            <span
                                v-if="
                                    !doctorNotifRead &&
                                    doctorNotifications.length > 0
                                "
                                class="absolute top-1.5 right-1.5 flex h-3 w-3 items-center justify-center rounded-full bg-red-500 ring-2 ring-white"
                            ></span>
                        </button>
                        <div
                            v-if="showDoctorNotifications"
                            class="absolute top-full right-0 z-50 mt-2 w-80 overflow-hidden rounded-xl border border-gray-100 bg-white shadow-xl"
                        >
                            <div
                                class="flex items-center justify-between border-b border-gray-100 bg-gray-50 px-4 py-3"
                            >
                                <h3 class="text-sm font-bold text-gray-800">
                                    Notifications
                                </h3>
                                <span
                                    class="rounded-full bg-gray-200 px-2 py-0.5 text-xs text-gray-600"
                                    >{{
                                        doctorNotifications.length
                                    }}
                                    Alert(s)</span
                                >
                            </div>
                            <div
                                class="max-h-72 divide-y divide-gray-50 overflow-y-auto"
                            >
                                <div
                                    v-for="notif in doctorNotifications"
                                    :key="notif.id"
                                    class="p-4"
                                >
                                    <p class="text-sm font-bold text-gray-800">
                                        {{ notif.title }}
                                    </p>
                                    <p class="mt-1 text-xs text-gray-600">
                                        {{ notif.message }}
                                    </p>
                                    <p
                                        class="mt-2 text-[10px] font-bold tracking-wider text-gray-400 uppercase"
                                    >
                                        {{ notif.time }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
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

            <div class="relative flex-1 overflow-y-auto p-8">
                <div class="mx-auto max-w-7xl space-y-6">
                    <!-- Hero Card -->
                    <div
                        class="relative overflow-hidden rounded-2xl bg-linear-to-br from-[#128a8c] to-[#0f6c7a] p-8 text-white shadow-md"
                    >
                        <svg
                            class="absolute -right-6 -bottom-10 h-64 w-64 -rotate-12 transform text-white opacity-10"
                            fill="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"
                            />
                        </svg>
                        <div class="relative z-10">
                            <div
                                class="mb-2 flex items-center text-sm font-semibold tracking-wider text-teal-100"
                            >
                                <svg
                                    class="mr-2 h-4 w-4"
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
                                NEXT PATIENT
                            </div>
                            <h3
                                class="mb-1 text-4xl font-extrabold"
                                v-if="nextPatient"
                            >
                                {{ nextPatient.name }}
                            </h3>
                            <h3
                                class="mb-1 text-4xl font-extrabold opacity-70"
                                v-else
                            >
                                No Patients
                            </h3>
                            <p
                                class="text-lg font-medium text-teal-100"
                                v-if="nextPatient"
                            >
                                {{ nextPatient.service ?? 'General Checkup' }} •
                                {{ nextPatient.time }}
                            </p>
                        </div>
                        <div class="relative z-10 mt-8">
                            <button
                                @click="openConsultModal(nextPatient)"
                                :disabled="!nextPatient"
                                class="flex items-center rounded-lg bg-white px-6 py-3 font-bold text-[#0f6c7a] shadow-md transition-all hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <svg
                                    class="mr-2 h-5 w-5"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"
                                    />
                                </svg>
                                START CONSULT
                            </button>
                        </div>
                    </div>

                    <!-- Queue Table -->
                    <div
                        class="mt-6 overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm"
                    >
                        <div
                            class="flex items-center justify-between border-b border-gray-100 p-6"
                        >
                            <h3
                                class="flex items-center text-lg font-bold text-gray-800"
                            >
                                <svg
                                    class="mr-2 h-5 w-5 text-[#128a8c]"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                                    />
                                </svg>
                                Today's Queue
                            </h3>
                            <div
                                class="rounded-lg border border-blue-100 bg-blue-50 px-4 py-1.5 text-sm font-bold text-blue-700"
                            >
                                {{ formattedDate }}
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full border-collapse text-left">
                                <thead>
                                    <tr class="border-b border-gray-100">
                                        <th
                                            class="px-6 py-4 text-xs font-extrabold tracking-widest text-gray-400 uppercase"
                                        >
                                            Time
                                        </th>
                                        <th
                                            class="px-6 py-4 text-xs font-extrabold tracking-widest text-gray-400 uppercase"
                                        >
                                            Patient Name
                                        </th>
                                        <th
                                            class="px-6 py-4 text-xs font-extrabold tracking-widest text-gray-400 uppercase"
                                        >
                                            Status
                                        </th>
                                        <th
                                            class="px-6 py-4 text-right text-xs font-extrabold tracking-widest text-gray-400 uppercase"
                                        >
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    <tr
                                        v-for="appt in queue"
                                        :key="appt.id"
                                        class="transition-colors hover:bg-gray-50/50"
                                    >
                                        <td
                                            class="px-6 py-5 text-sm font-bold whitespace-nowrap text-[#146b8a]"
                                        >
                                            {{ appt.time }}
                                        </td>
                                        <td
                                            class="px-6 py-5 text-sm font-bold whitespace-nowrap text-gray-900"
                                        >
                                            {{ appt.name }}
                                        </td>
                                        <td class="px-6 py-5 whitespace-nowrap">
                                            <span
                                                class="inline-flex rounded-md border px-3 py-1 text-xs leading-5 font-bold tracking-wider uppercase"
                                                :class="
                                                    appt.status ===
                                                    'In Progress'
                                                        ? 'border-blue-200 bg-blue-50 text-blue-700'
                                                        : 'border-yellow-200 bg-yellow-50 text-yellow-700'
                                                "
                                            >
                                                {{ appt.status ?? 'Pending' }}
                                            </span>
                                        </td>
                                        <td
                                            class="px-6 py-5 text-right whitespace-nowrap"
                                        >
                                            <div
                                                class="flex items-center justify-end gap-4"
                                            >
                                                <button
                                                    @click="
                                                        openConsultModal(appt)
                                                    "
                                                    class="flex items-center rounded-md bg-[#0f8c8e] px-4 py-1.5 text-xs font-bold text-white shadow-sm transition-colors hover:bg-[#0c7072]"
                                                >
                                                    Start
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="queue.length === 0">
                                        <td
                                            colspan="4"
                                            class="px-6 py-12 text-center font-medium text-gray-500"
                                        >
                                            No patients currently in queue.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- ========================================== -->
        <!-- WIDE CONSULTATION MODAL -->
        <!-- ========================================== -->
        <div
            v-if="isModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm transition-opacity"
        >
            <div
                class="animate-fade-in-up flex h-[95vh] w-full max-w-[95vw] flex-col overflow-hidden rounded-2xl bg-white shadow-2xl xl:max-w-7xl"
            >
                <!-- Modal Header -->
                <div
                    class="z-10 flex shrink-0 items-center justify-between bg-[#1e293b] px-6 py-4 text-white shadow-md"
                >
                    <div class="flex items-center gap-4">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-lg bg-teal-500 text-lg font-bold text-white"
                        >
                            {{ activeAppt?.name?.charAt(0) ?? '' }}
                        </div>
                        <div>
                            <h2 class="text-xl font-bold tracking-wide">
                                {{ activeAppt?.name }}
                            </h2>
                            <p
                                class="text-xs font-medium tracking-widest text-gray-300 uppercase"
                            >
                                ID: {{ formatPatientIdentifier() }}
                            </p>
                        </div>
                    </div>
                    <button
                        @click="closeConsultModal"
                        class="text-gray-400 transition-colors hover:text-white"
                    >
                        <svg
                            class="h-7 w-7"
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
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="flex flex-1 overflow-hidden">
                    <!-- LEFT COLUMN: Medical History -->
                    <div
                        class="flex w-1/4 shrink-0 flex-col gap-6 overflow-y-auto border-r border-gray-200 bg-[#f8fafc] p-6"
                    >
                        <div
                            class="flex h-full flex-col rounded-xl border border-gray-200 bg-white p-5 shadow-sm"
                        >
                            <div class="mb-4 flex items-center justify-between">
                                <h3
                                    class="flex items-center text-xs font-extrabold tracking-widest text-[#146b8a] uppercase"
                                >
                                    <svg
                                        class="mr-2 h-4 w-4"
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
                                    Medical History
                                </h3>
                                <span
                                    class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-bold text-gray-500"
                                    >{{
                                        activeAppt?.history?.length ?? 0
                                    }}</span
                                >
                            </div>
                            <!-- Empty state -->
                            <div
                                v-if="!activeAppt?.history?.length"
                                class="flex flex-1 flex-col items-center justify-center rounded-lg border border-dashed border-gray-300 bg-gray-50 p-6 text-center"
                            >
                                <p class="text-sm font-bold text-gray-500">
                                    No previous records.
                                </p>
                                <p class="mt-1 text-xs text-gray-400">
                                    First consultation.
                                </p>
                            </div>
                            <!-- History list -->
                            <div
                                v-else
                                class="flex-1 space-y-2 overflow-y-auto"
                            >
                                <div
                                    v-for="(record, idx) in activeAppt.history"
                                    :key="idx"
                                    class="group rounded-lg border border-gray-100 bg-gray-50 p-3 text-left transition-colors hover:border-[#146b8a]/30 hover:bg-teal-50/30"
                                >
                                    <div
                                        class="flex items-start justify-between"
                                    >
                                        <div class="min-w-0 flex-1">
                                            <p
                                                class="text-[10px] font-bold text-[#146b8a]"
                                            >
                                                {{
                                                    new Date(
                                                        record.date,
                                                    ).toLocaleDateString(
                                                        'en-US',
                                                        {
                                                            month: 'short',
                                                            day: 'numeric',
                                                            year: 'numeric',
                                                        },
                                                    )
                                                }}
                                            </p>
                                            <p
                                                class="mt-0.5 text-xs font-semibold text-gray-700"
                                            >
                                                {{
                                                    record.service_type ??
                                                    'General Checkup'
                                                }}
                                            </p>
                                            <!-- Type badges for the mini-history entry -->
                                            <div
                                                class="mt-1 flex flex-wrap gap-1"
                                            >
                                                <span
                                                    v-if="record.echo_data"
                                                    class="rounded-sm bg-blue-100 px-1 py-0.5 text-[8px] font-bold text-blue-600"
                                                    >ECHO</span
                                                >
                                                <span
                                                    v-if="record.tmt_data"
                                                    class="rounded-sm bg-purple-100 px-1 py-0.5 text-[8px] font-bold text-purple-600"
                                                    >TMT</span
                                                >
                                                <span
                                                    v-if="record.doppler_data"
                                                    class="rounded-sm bg-teal-100 px-1 py-0.5 text-[8px] font-bold text-teal-600"
                                                    >DOP</span
                                                >
                                                <span
                                                    v-if="
                                                        record.manual_draft_data
                                                    "
                                                    class="rounded-sm bg-amber-100 px-1 py-0.5 text-[8px] font-bold text-amber-700"
                                                    >DRAFT</span
                                                >
                                            </div>
                                            <p
                                                v-if="record.diagnosis"
                                                class="mt-0.5 line-clamp-2 text-[10px] text-gray-500"
                                            >
                                                Dx: {{ record.diagnosis }}
                                            </p>
                                        </div>
                                        <!-- Quick PDF links per record type -->
                                        <div
                                            class="ml-2 flex shrink-0 flex-col gap-1 opacity-0 transition-opacity group-hover:opacity-100"
                                        >
                                            <a
                                                v-if="record.echo_data"
                                                :href="`/doctor/pdf/${record.id}/echo`"
                                                target="_blank"
                                                @click.stop
                                                class="rounded bg-blue-600 px-1.5 py-0.5 text-[8px] font-bold text-white hover:bg-blue-700"
                                                title="Download Echo PDF"
                                                >PDF</a
                                            >
                                            <a
                                                v-if="record.tmt_data"
                                                :href="`/doctor/pdf/${record.id}/tmt`"
                                                target="_blank"
                                                @click.stop
                                                class="rounded bg-purple-600 px-1.5 py-0.5 text-[8px] font-bold text-white hover:bg-purple-700"
                                                title="Download TMT PDF"
                                                >PDF</a
                                            >
                                            <a
                                                v-if="record.doppler_data"
                                                :href="`/doctor/pdf/${record.id}/doppler`"
                                                target="_blank"
                                                @click.stop
                                                class="rounded bg-teal-600 px-1.5 py-0.5 text-[8px] font-bold text-white hover:bg-teal-700"
                                                title="Download Doppler PDF"
                                                >PDF</a
                                            >
                                            <a
                                                v-if="
                                                    record.manual_draft_data
                                                "
                                                :href="`/doctor/pdf/${record.id}/manual_draft`"
                                                target="_blank"
                                                @click.stop
                                                class="rounded bg-amber-600 px-1.5 py-0.5 text-[8px] font-bold text-white hover:bg-amber-700"
                                                title="Download Manual Draft PDF"
                                                >PDF</a
                                            >
                                        </div>
                                    </div>
                                </div>
                                <!-- View Full History link -->
                                <Link
                                    v-if="activeHistoryUrl"
                                    :href="activeHistoryUrl"
                                    class="mt-2 flex w-full items-center justify-center gap-1.5 rounded-lg border border-dashed border-[#146b8a]/40 py-2 text-xs font-bold text-[#146b8a] transition-colors hover:border-[#146b8a] hover:bg-teal-50"
                                >
                                    <svg
                                        class="h-3.5 w-3.5"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                        />
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                        />
                                    </svg>
                                    View Full History
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT COLUMN: SOAP & FORM -->
                    <div class="relative min-w-0 flex-1 overflow-y-auto bg-white p-8">
                        <form @submit.prevent="saveConsultation">
                            <div
                                class="mb-6 flex items-center justify-between border-b border-gray-100 pb-4"
                            >
                                <h2
                                    class="text-2xl font-black tracking-tight text-gray-800"
                                >
                                    Consultation Record
                                </h2>
                                <span
                                    class="rounded-lg border border-gray-200 bg-gray-100 px-3 py-1.5 text-xs font-bold text-gray-600"
                                    >{{ formattedDate }}</span
                                >
                            </div>

                            <div class="space-y-6">
                                <!-- Subjective & Objective -->
                                <div
                                    class="grid grid-cols-1 gap-6 md:grid-cols-2"
                                >
                                    <div>
                                        <label
                                            class="mb-2 block text-xs font-extrabold tracking-widest text-[#146b8a] uppercase"
                                            >Subjective (Symptoms)</label
                                        >
                                        <textarea
                                            v-model="consultForm.symptoms"
                                            placeholder="Patient complaints..."
                                            class="h-24 w-full resize-none rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 outline-none focus:border-[#0f8c8e]"
                                        ></textarea>
                                    </div>
                                    <div>
                                        <label
                                            class="mb-2 block text-xs font-extrabold tracking-widest text-[#146b8a] uppercase"
                                            >Objective (Vitals)</label
                                        >
                                        <div class="grid grid-cols-3 gap-2">
                                            <div>
                                                <label class="mb-0.5 block text-[10px] font-semibold text-gray-500">BP Sys</label>
                                                <input type="number" v-model.number="vitalsForm.bp_sys" placeholder="120" min="60" max="250"
                                                    class="h-8 w-full rounded-lg border border-gray-200 bg-white px-2 text-sm text-gray-900 outline-none focus:border-[#0f8c8e]" />
                                            </div>
                                            <div>
                                                <label class="mb-0.5 block text-[10px] font-semibold text-gray-500">BP Dia</label>
                                                <input type="number" v-model.number="vitalsForm.bp_dia" placeholder="80" min="30" max="180"
                                                    class="h-8 w-full rounded-lg border border-gray-200 bg-white px-2 text-sm text-gray-900 outline-none focus:border-[#0f8c8e]" />
                                            </div>
                                            <div>
                                                <label class="mb-0.5 block text-[10px] font-semibold text-gray-500">Heart Rate</label>
                                                <input type="number" v-model.number="vitalsForm.hr" placeholder="72" min="30" max="250"
                                                    class="h-8 w-full rounded-lg border border-gray-200 bg-white px-2 text-sm text-gray-900 outline-none focus:border-[#0f8c8e]" />
                                            </div>
                                            <div>
                                                <label class="mb-0.5 block text-[10px] font-semibold text-gray-500">Resp. Rate</label>
                                                <input type="number" v-model.number="vitalsForm.rr" placeholder="18" min="6" max="60"
                                                    class="h-8 w-full rounded-lg border border-gray-200 bg-white px-2 text-sm text-gray-900 outline-none focus:border-[#0f8c8e]" />
                                            </div>
                                            <div>
                                                <label class="mb-0.5 block text-[10px] font-semibold text-gray-500">Temp (°C)</label>
                                                <input type="number" v-model="vitalsForm.temp" placeholder="36.5" min="34" max="42" step="0.1"
                                                    class="h-8 w-full rounded-lg border border-gray-200 bg-white px-2 text-sm text-gray-900 outline-none focus:border-[#0f8c8e]" />
                                            </div>
                                            <div>
                                                <label class="mb-0.5 block text-[10px] font-semibold text-gray-500">SpO₂ (%)</label>
                                                <input type="number" v-model.number="vitalsForm.spo2" placeholder="97" min="50" max="100"
                                                    class="h-8 w-full rounded-lg border border-gray-200 bg-white px-2 text-sm text-gray-900 outline-none focus:border-[#0f8c8e]" />
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Assessment -->
                                <div>
                                    <label
                                        class="mb-2 block text-xs font-extrabold tracking-widest text-[#146b8a] uppercase"
                                        >Assessment (Diagnosis)</label
                                    >
                                    <input
                                        type="text"
                                        v-model="consultForm.diagnosis"
                                        placeholder="e.g. Acute Viral Infection"
                                        class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 font-medium text-gray-900 outline-none focus:border-[#0f8c8e]"
                                    />
                                </div>

                                <!-- Plan (P in SOAP) -->
                                <div>
                                    <label
                                        class="mb-2 block text-xs font-extrabold tracking-widest text-[#146b8a] uppercase"
                                        >Plan (Recommendations)</label
                                    >
                                    <textarea
                                        v-model="consultForm.plan"
                                        placeholder="Treatment plan, lifestyle modifications, medications, follow-up instructions..."
                                        class="h-24 w-full resize-none rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 outline-none focus:border-[#0f8c8e]"
                                    ></textarea>
                                </div>

                                <!-- PLAN & CATEGORY SELECTOR -->
                                <div class="border-t border-gray-100 pt-6">
                                    <div class="mb-4">
                                        <h3
                                            class="mb-3 text-sm font-extrabold tracking-widest text-[#146b8a] uppercase"
                                        >
                                            Medical Plan & Forms
                                        </h3>
                                        <p
                                            class="mb-2 text-[10px] font-semibold tracking-widest text-gray-500 uppercase"
                                        >
                                            Select one or more consultation
                                            forms:
                                        </p>
                                        <div class="flex flex-wrap gap-3">
                                            <label
                                                class="flex cursor-pointer items-center gap-2 rounded-lg border px-4 py-2 text-sm font-bold transition-colors"
                                                :class="
                                                    consultForm.consultation_type.includes(
                                                        'Type 1',
                                                    )
                                                        ? 'border-[#146b8a] bg-[#146b8a]/10 text-[#146b8a]'
                                                        : 'border-gray-200 bg-gray-50 text-gray-600 hover:border-[#146b8a]/50'
                                                "
                                            >
                                                <input
                                                    type="checkbox"
                                                    value="Type 1"
                                                    v-model="
                                                        consultForm.consultation_type
                                                    "
                                                    class="accent-[#146b8a]"
                                                />
                                                Type 1 — Echocardiographic
                                            </label>
                                            <label
                                                class="flex cursor-pointer items-center gap-2 rounded-lg border px-4 py-2 text-sm font-bold transition-colors"
                                                :class="
                                                    consultForm.consultation_type.includes(
                                                        'Type 2',
                                                    )
                                                        ? 'border-[#1e8996] bg-[#1e8996]/10 text-[#1e8996]'
                                                        : 'border-gray-200 bg-gray-50 text-gray-600 hover:border-[#1e8996]/50'
                                                "
                                            >
                                                <input
                                                    type="checkbox"
                                                    value="Type 2"
                                                    v-model="
                                                        consultForm.consultation_type
                                                    "
                                                    class="accent-[#1e8996]"
                                                />
                                                Type 2 — Treadmill (TMT)
                                            </label>
                                            <label
                                                class="flex cursor-pointer items-center gap-2 rounded-lg border px-4 py-2 text-sm font-bold transition-colors"
                                                :class="
                                                    consultForm.consultation_type.includes(
                                                        'Type 3',
                                                    )
                                                        ? 'border-indigo-600 bg-indigo-50 text-indigo-700'
                                                        : 'border-gray-200 bg-gray-50 text-gray-600 hover:border-indigo-400'
                                                "
                                            >
                                                <input
                                                    type="checkbox"
                                                    value="Type 3"
                                                    v-model="
                                                        consultForm.consultation_type
                                                    "
                                                    class="accent-indigo-600"
                                                />
                                                Type 3 — Color Doppler
                                            </label>
                                            <label
                                                class="flex cursor-pointer items-center gap-2 rounded-lg border px-4 py-2 text-sm font-bold transition-colors"
                                                :class="
                                                    consultForm.consultation_type.includes(
                                                        'Type 4',
                                                    )
                                                        ? 'border-amber-500 bg-amber-50 text-amber-700'
                                                        : 'border-gray-200 bg-gray-50 text-gray-600 hover:border-amber-500/50'
                                                "
                                            >
                                                <input
                                                    type="checkbox"
                                                    value="Type 4"
                                                    v-model="
                                                        consultForm.consultation_type
                                                    "
                                                    class="accent-amber-500"
                                                />
                                                Type 4 — Manual Draft
                                            </label>
                                        </div>
                                    </div>

                                    <!-- ============================================== -->
                                    <!-- TYPE 1: ECHOCARDIOGRAPHIC REPORT FORM -->
                                    <EchoForm
                                        v-if="
                                            consultForm.consultation_type.includes(
                                                'Type 1',
                                            )
                                        "
                                        :echo-data="consultForm.echo_data"
                                        :patient-name="activeAppt?.name ?? ''"
                                    />

                                    <!-- ============================================== -->
                                    <!-- TYPE 2: TREADMILL EXERCISE TEST -->
                                    <TmtForm
                                        v-if="
                                            consultForm.consultation_type.includes(
                                                'Type 2',
                                            )
                                        "
                                        :tmt-data="consultForm.tmt_data"
                                        :echo-sex="consultForm.echo_data.sex"
                                        :patient-name="activeAppt?.name ?? ''"
                                    />

                                    <!-- ============================================== -->
                                    <!-- TYPE 3: COLOR FLOW & DOPPLER STUDY -->
                                    <DopplerForm
                                        v-if="
                                            consultForm.consultation_type.includes(
                                                'Type 3',
                                            )
                                        "
                                        :doppler-data="consultForm.doppler_data"
                                        :echo-data="consultForm.echo_data"
                                        :patient-name="activeAppt?.name ?? ''"
                                    />

                                    <!-- ============================================== -->
                                    <!-- TYPE 4: MANUAL DRAFT (free-form with OCR image) -->
                                    <ManualDraftForm
                                        v-if="
                                            consultForm.consultation_type.includes(
                                                'Type 4',
                                            )
                                        "
                                        :manual-draft-data="
                                            consultForm.manual_draft_data
                                        "
                                        :patient-name="activeAppt?.name ?? ''"
                                    />

                                    <!-- Dynamic Prescription Builder -->
                                    <div class="mt-8 space-y-4">
                                        <h3
                                            class="text-sm font-extrabold tracking-widest text-[#146b8a] uppercase"
                                        >
                                            Prescriptions
                                        </h3>
                                        <div
                                            v-for="(
                                                med, index
                                            ) in consultForm.prescriptions"
                                            :key="index"
                                            class="group relative rounded-xl border border-gray-200 bg-gray-50 p-5 shadow-sm transition-all hover:border-[#146b8a]"
                                        >
                                            <!-- Delete Medication Button -->
                                            <button
                                                @click.prevent="
                                                    removeMedication(index)
                                                "
                                                v-if="
                                                    consultForm.prescriptions
                                                        .length > 1
                                                "
                                                title="Remove Medication"
                                                class="absolute top-4 right-4 rounded-lg border border-red-100 bg-white p-1.5 text-red-400 shadow-sm transition-all hover:scale-105 hover:text-red-600"
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
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                                    />
                                                </svg>
                                            </button>

                                            <span
                                                class="mb-3 inline-block rounded bg-[#146b8a] px-2 py-1 text-[10px] font-extrabold tracking-widest text-white uppercase"
                                                >Medication
                                                {{ index + 1 }}</span
                                            >

                                            <div
                                                class="grid grid-cols-1 gap-4 pr-10 md:grid-cols-4"
                                            >
                                                <!-- Medicine Name -->
                                                <div class="md:col-span-2">
                                                    <label
                                                        class="mb-1 block text-[10px] font-extrabold tracking-widest text-gray-500 uppercase"
                                                        >Medicine Name</label
                                                    >
                                                    <input
                                                        type="text"
                                                        v-model="med.medicine"
                                                        placeholder="e.g. Amlodipine"
                                                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-900 outline-none focus:border-[#0f8c8e]"
                                                    />
                                                </div>

                                                <!-- Dosage -->
                                                <div>
                                                    <label
                                                        class="mb-1 block text-[10px] font-extrabold tracking-widest text-gray-500 uppercase"
                                                        >Dosage</label
                                                    >
                                                    <input
                                                        type="text"
                                                        v-model="med.dosage"
                                                        placeholder="e.g. 5mg"
                                                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-900 outline-none focus:border-[#0f8c8e]"
                                                    />
                                                </div>

                                                <!-- Frequency -->
                                                <div>
                                                    <label
                                                        class="mb-1 block text-[10px] font-extrabold tracking-widest text-gray-500 uppercase"
                                                        >Frequency</label
                                                    >
                                                    <select
                                                        v-model="med.frequency"
                                                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-900 outline-none focus:border-[#0f8c8e]"
                                                    >
                                                        <option
                                                            v-for="freq in medicationFrequencies"
                                                            :key="freq"
                                                            :value="freq"
                                                        >
                                                            {{ freq }}
                                                        </option>
                                                    </select>
                                                </div>

                                                <!-- Duration -->
                                                <div>
                                                    <label
                                                        class="mb-1 block text-[10px] font-extrabold tracking-widest text-gray-500 uppercase"
                                                        >Duration (days)</label
                                                    >
                                                    <input
                                                        type="number"
                                                        v-model="med.duration"
                                                        placeholder="e.g. 7"
                                                        min="1"
                                                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-900 outline-none focus:border-[#0f8c8e]"
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <button
                                        @click.prevent="addMedication"
                                        class="mt-4 flex w-full items-center justify-center rounded-xl border-2 border-dashed border-[#146b8a]/30 py-3 text-sm font-bold text-[#146b8a] transition-colors hover:border-[#146b8a] hover:bg-teal-50"
                                    >
                                        <svg
                                            class="mr-2 h-4 w-4"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M12 6v6m0 0v6m0-6h6m-6 0H6"
                                            />
                                        </svg>
                                        Add Another Medication
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- RIGHT-HAND PANEL: OCR SCANS CAPTURED BY RECEPTION -->
                    <aside
                        class="flex w-80 shrink-0 flex-col border-l border-gray-200 bg-[#f8fafc] xl:w-[22rem]"
                        aria-label="Scanned documents and converted text"
                    >
                        <div
                            class="flex shrink-0 items-center justify-between border-b border-gray-200 bg-white px-4 py-3"
                        >
                            <h3
                                class="flex items-center text-xs font-extrabold tracking-widest text-[#146b8a] uppercase"
                            >
                                <svg
                                    class="mr-2 h-4 w-4"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M7 16a4 4 0 01-.88-7.9A5 5 0 0115.9 6M15 13l4 4m0-4l-4 4"
                                    />
                                </svg>
                                Scans
                            </h3>
                            <span
                                v-if="scans.length"
                                class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-bold text-gray-500"
                            >
                                {{ scans.length }}
                            </span>
                        </div>

                        <!-- Loading -->
                        <div
                            v-if="scansLoading && !scans.length"
                            class="p-4 text-xs font-medium text-gray-500"
                        >
                            Loading scans…
                        </div>

                        <!-- No scans -->
                        <div
                            v-else-if="!scans.length"
                            class="flex flex-1 flex-col items-center justify-center p-6 text-center"
                        >
                            <svg
                                class="mb-3 h-10 w-10 text-gray-300"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.5"
                                    d="M7 16a4 4 0 01-.88-7.9A5 5 0 0115.9 6M15 13l4 4m0-4l-4 4"
                                />
                            </svg>
                            <p class="text-xs font-bold text-gray-500">
                                No scans uploaded
                            </p>
                            <p class="mt-1 text-[11px] leading-relaxed text-gray-400">
                                Reception can attach a prescription photo from
                                the front desk. Converted text appears here.
                            </p>
                        </div>

                        <!-- Scan list + selected document -->
                        <template v-else>
                            <ul
                                class="max-h-40 shrink-0 divide-y divide-gray-100 overflow-y-auto border-b border-gray-200 bg-white"
                            >
                                <li
                                    v-for="scan in scans"
                                    :key="scan.id"
                                    draggable="true"
                                    @dragstart="onScanDragStart($event, scan)"
                                >
                                    <button
                                        type="button"
                                        class="flex w-full items-center justify-between gap-2 px-4 py-2.5 text-left transition-colors"
                                        :class="
                                            selectedScanId === scan.id
                                                ? 'bg-teal-50'
                                                : 'hover:bg-gray-50'
                                        "
                                        @click="selectedScanId = scan.id"
                                    >
                                        <span class="min-w-0 flex-1">
                                            <span
                                                class="block truncate text-xs font-bold text-gray-800"
                                            >
                                                {{
                                                    scanTypeLabel(
                                                        scan.type,
                                                    )
                                                }}
                                            </span>
                                            <span
                                                class="block truncate text-[10px] text-gray-400"
                                            >
                                                {{
                                                    scan.original_filename ??
                                                    `Scan #${scan.id}`
                                                }}
                                            </span>
                                        </span>
                                        <span
                                            class="shrink-0 rounded-md border px-1.5 py-0.5 text-[9px] font-bold tracking-wider uppercase"
                                            :class="
                                                scan.ocr_status === 'completed'
                                                    ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
                                                    : scan.ocr_status ===
                                                        'failed'
                                                      ? 'border-red-200 bg-red-50 text-red-700'
                                                      : 'border-blue-200 bg-blue-50 text-blue-700'
                                            "
                                        >
                                            {{
                                                scan.ocr_status ===
                                                'completed'
                                                    ? confidenceLabel(
                                                          scan.ocr_confidence,
                                                      )
                                                    : scan.ocr_status
                                            }}
                                        </span>
                                    </button>
                                </li>
                            </ul>

                            <div
                                v-if="selectedScan"
                                class="min-h-0 flex-1 space-y-4 overflow-y-auto p-4"
                            >
                                <!-- In progress -->
                                <div
                                    v-if="
                                        selectedScan.ocr_status === 'pending' ||
                                        selectedScan.ocr_status ===
                                            'processing'
                                    "
                                    class="rounded-lg border border-blue-200 bg-blue-50 p-4 text-center"
                                >
                                    <svg
                                        class="mx-auto mb-2 h-6 w-6 animate-spin text-blue-600"
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
                                        />
                                        <path
                                            class="opacity-75"
                                            fill="currentColor"
                                            d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                                        />
                                    </svg>
                                    <p class="text-xs font-bold text-blue-800">
                                        {{
                                            selectedScan.ocr_status ===
                                            'pending'
                                                ? 'Queued for OCR…'
                                                : 'Converting to text…'
                                        }}
                                    </p>
                                    <p
                                        class="mt-1 text-[11px] text-blue-600"
                                    >
                                        This panel refreshes automatically.
                                    </p>
                                </div>

                                <!-- Failed -->
                                <div
                                    v-else-if="selectedScan.ocr_status === 'failed'"
                                    class="rounded-lg border border-red-200 bg-red-50 p-4"
                                >
                                    <p class="text-xs font-bold text-red-800">
                                        OCR failed
                                    </p>
                                    <p
                                        v-if="selectedScan.ocr_error"
                                        class="mt-1 text-[11px] break-words text-red-600"
                                    >
                                        {{ selectedScan.ocr_error }}
                                    </p>
                                    <p
                                        class="mt-2 text-[11px] text-red-500"
                                    >
                                        Ask reception to re-upload a clearer
                                        photo.
                                    </p>
                                </div>

                                <!-- Completed: image, then converted text -->
                                <template v-else>
                                    <a
                                        :href="selectedScan.url"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="block overflow-hidden rounded-lg border border-gray-200 bg-white"
                                    >
                                        <img
                                            :src="selectedScan.url"
                                            :alt="
                                                selectedScan.original_filename ??
                                                'Scanned document'
                                            "
                                            class="max-h-56 w-full bg-gray-50 object-contain"
                                            loading="lazy"
                                            draggable="true"
                                            @dragstart="
                                                onScanDragStart(
                                                    $event,
                                                    selectedScan,
                                                )
                                            "
                                        />
                                    </a>

                                    <div class="flex flex-wrap items-center gap-2">
                                        <span
                                            class="rounded-md border px-2 py-0.5 text-[10px] font-bold"
                                            :class="
                                                confidenceClass(
                                                    selectedScan.ocr_confidence,
                                                )
                                            "
                                        >
                                            {{
                                                confidenceLabel(
                                                    selectedScan.ocr_confidence,
                                                )
                                            }} confidence
                                        </span>
                                        <span
                                            v-if="selectedScan.engine"
                                            class="rounded-md border border-gray-200 bg-white px-2 py-0.5 text-[10px] font-bold text-gray-500"
                                        >
                                            {{ selectedScan.engine }}
                                        </span>
                                        <span
                                            v-if="
                                                selectedScan.uploaded_by
                                            "
                                            class="text-[10px] text-gray-400"
                                        >
                                            by
                                            {{
                                                selectedScan.uploaded_by
                                            }}
                                        </span>
                                    </div>

                                    <div
                                        v-if="
                                            selectedScan.requires_review
                                        "
                                        class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-[11px] font-bold text-amber-800"
                                    >
                                        {{
                                            selectedScan.ocr_confidence ===
                                            null
                                                ? 'The hosted OCR service reports no confidence score — verify against the image before prescribing.'
                                                : 'Low confidence — verify against the image before prescribing.'
                                        }}
                                    </div>

                                    <!-- Recognised medications -->
                                    <div
                                        v-if="
                                            selectedScan.medications?.length
                                        "
                                    >
                                        <div
                                            class="mb-2 flex items-center justify-between"
                                        >
                                            <h4
                                                class="text-[10px] font-extrabold tracking-widest text-gray-500 uppercase"
                                            >
                                                Recognised
                                                medications
                                            </h4>
                                            <span
                                                v-if="
                                                    scannedToPrescription[
                                                        selectedScan.id
                                                    ]
                                                "
                                                class="text-[10px] font-bold text-emerald-600"
                                            >
                                                added to draft
                                            </span>
                                        </div>

                                        <ul class="space-y-1.5">
                                            <li
                                                v-for="(
                                                    med,
                                                    medIdx
                                                ) in selectedScan.medications"
                                                :key="medIdx"
                                                class="rounded-lg border border-gray-200 bg-white p-2.5"
                                            >
                                                <div
                                                    class="flex items-start justify-between gap-2"
                                                >
                                                    <p
                                                        class="text-xs font-bold text-gray-800"
                                                    >
                                                        {{
                                                            med.medication_name
                                                        }}
                                                    </p>
                                                    <button
                                                        type="button"
                                                        class="shrink-0 rounded-md bg-[#0f8c8e] px-2 py-1 text-[10px] font-bold text-white transition-colors hover:bg-[#0c7072]"
                                                        @click="
                                                            applyMedication(
                                                                selectedScan,
                                                                med,
                                                            )
                                                        "
                                                    >
                                                        + Add
                                                    </button>
                                                </div>
                                                <p
                                                    class="mt-1 text-[11px] text-gray-500"
                                                >
                                                    {{
                                                        [
                                                            med.dosage,
                                                            med.frequency,
                                                            med.route,
                                                            med.duration_days
                                                                ? `${med.duration_days}d`
                                                                : null,
                                                        ]
                                                            .filter(Boolean)
                                                            .join(' · ')
                                                    }}
                                                </p>
                                            </li>
                                        </ul>

                                        <p
                                            v-if="
                                                selectedScan.warnings?.length
                                            "
                                            class="mt-2 text-[10px] text-amber-600"
                                        >
                                            {{
                                                selectedScan.warnings.join(
                                                    ' ',
                                                )
                                            }}
                                        </p>
                                    </div>

                                    <!-- Raw converted text -->
                                    <div>
                                        <h4
                                            class="mb-2 text-[10px] font-extrabold tracking-widest text-gray-500 uppercase"
                                        >
                                            Converted text
                                        </h4>
                                        <pre
                                            class="max-h-64 overflow-y-auto rounded-lg border border-gray-200 bg-white p-3 font-mono text-[11px] leading-relaxed whitespace-pre-wrap text-gray-700"
>{{
                                            selectedScan.ocr_text ||
                                            'No text recognised.'
                                        }}</pre
                                    >
                                    </div>

                                    <!-- Layout and non-text marks detected on the page -->
                                    <div
                                        v-if="
                                            selectedScan.layout?.length ||
                                            visualChips.length
                                        "
                                    >
                                        <h4
                                            class="mb-2 text-[10px] font-extrabold tracking-widest text-gray-500 uppercase"
                                        >
                                            Page layout &amp; visuals
                                        </h4>

                                        <div
                                            v-if="visualChips.length"
                                            class="flex flex-wrap gap-1.5"
                                        >
                                            <span
                                                v-for="chip in visualChips"
                                                :key="chip.kind"
                                                class="inline-flex items-center gap-1 rounded-md border border-purple-200 bg-purple-50 px-2 py-0.5 text-[10px] font-bold text-purple-800"
                                            >
                                                {{ chip.label }}
                                                <span
                                                    class="rounded bg-purple-200/70 px-1 text-[9px]"
                                                >
                                                    {{ chip.count }}
                                                </span>
                                            </span>
                                        </div>

                                        <ul
                                            v-if="selectedScan.visuals?.length"
                                            class="mt-2 space-y-1"
                                        >
                                            <li
                                                v-for="(
                                                    visual,
                                                    index
                                                ) in selectedScan.visuals"
                                                :key="index"
                                                class="flex items-center justify-between gap-2 rounded-md border border-gray-200 bg-white px-2.5 py-1.5 text-[10px]"
                                            >
                                                <span
                                                    class="font-bold text-gray-700"
                                                >
                                                    {{
                                                        VISUAL_LABELS[
                                                            visual.kind
                                                        ] ??
                                                        visual.kind
                                                    }}
                                                </span>
                                                <span
                                                    class="truncate text-gray-400"
                                                >
                                                    {{ visualDetail(visual) }}
                                                </span>
                                            </li>
                                        </ul>

                                        <details
                                            v-if="selectedScan.layout?.length"
                                            class="mt-2"
                                        >
                                            <summary
                                                class="cursor-pointer text-[10px] font-bold text-[#146b8a]"
                                            >
                                                Reading order —
                                                {{
                                                    selectedScan.layout.length
                                                }}
                                                regions
                                            </summary>
                                            <ol
                                                class="mt-1.5 space-y-1"
                                            >
                                                <li
                                                    v-for="region in selectedScan
                                                        .layout"
                                                    :key="
                                                        region.order ??
                                                        region.bbox?.[0]
                                                    "
                                                    class="flex gap-2 text-[10px]"
                                                >
                                                    <span
                                                        class="w-14 shrink-0 font-bold text-gray-500 uppercase"
                                                    >
                                                        {{
                                                            regionLabel(
                                                                region.label,
                                                            )
                                                        }}
                                                    </span>
                                                    <span
                                                        class="min-w-0 flex-1 truncate text-gray-600"
                                                    >
                                                        {{
                                                            region.text ||
                                                            '—'
                                                        }}
                                                    </span>
                                                </li>
                                            </ol>
                                        </details>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </aside>
                </div>

                <!-- Follow-up Appointment Section -->
                <div class="border-t border-gray-100 bg-gray-50/50 px-8 py-5">
                    <div class="mb-3 flex items-center gap-3">
                        <input
                            type="checkbox"
                            id="schedule-followup"
                            v-model="wantsFollowUp"
                            class="h-4 w-4 rounded accent-[#146b8a]"
                        />
                        <label
                            for="schedule-followup"
                            class="cursor-pointer text-sm font-bold text-[#146b8a]"
                        >
                            Schedule a Follow-up Appointment
                        </label>
                    </div>
                    <div
                        v-if="wantsFollowUp"
                        class="animate-fade-in-up grid grid-cols-2 gap-4"
                    >
                        <div>
                            <label
                                class="mb-1 block text-[10px] font-extrabold tracking-widest text-gray-500 uppercase"
                                >Follow-up Date</label
                            >
                            <input
                                type="date"
                                v-model="followUpForm.date"
                                :min="tomorrowDate"
                                class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-900 outline-none focus:border-[#146b8a]"
                            />
                        </div>
                        <div>
                            <label
                                class="mb-1 block text-[10px] font-extrabold tracking-widest text-gray-500 uppercase"
                                >Follow-up Time</label
                            >
                            <input
                                type="time"
                                v-model="followUpForm.time"
                                class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-900 outline-none focus:border-[#146b8a]"
                            />
                        </div>
                        <p class="col-span-2 text-xs font-medium text-gray-500">
                            The follow-up will be assigned to the same doctor
                            and automatically scheduled.
                        </p>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div
                    class="z-10 flex shrink-0 justify-end gap-4 border-t border-gray-200 bg-gray-50 px-8 py-4"
                >
                    <div class="flex items-center gap-3">
                        <p
                            class="hidden text-[10px] font-medium text-gray-400 sm:block"
                        >
                            📄 PDFs can be downloaded from
                            <Link
                                v-if="activeHistoryUrl"
                                :href="activeHistoryUrl"
                                class="font-bold text-[#146b8a] hover:underline"
                                target="_blank"
                                >Patient History</Link
                            >
                            after saving.
                        </p>
                    </div>
                    <div class="flex items-center gap-4">
                        <button
                            @click="closeConsultModal"
                            class="px-6 py-2.5 text-sm font-bold text-gray-500 transition-colors hover:text-gray-800"
                        >
                            Cancel
                        </button>
                        <button
                            @click="saveConsultation"
                            :disabled="isSaving"
                            class="flex items-center rounded-xl bg-[#059669] px-8 py-2.5 text-sm font-bold text-white shadow-md transition-all hover:bg-[#047857] disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <svg
                                class="mr-2 h-5 w-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"
                                />
                            </svg>
                            {{ isSaving ? 'Saving...' : 'Save Record' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Link, useForm, router } from '@inertiajs/vue3';
import LogoutButton from '@/components/LogoutButton.vue';
import { ref, reactive, computed, watch, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import DopplerForm from '@/components/Clinical/DopplerForm.vue';
import EchoForm from '@/components/Clinical/EchoForm.vue';
import ManualDraftForm from '@/components/Clinical/ManualDraftForm.vue';
import TmtForm from '@/components/Clinical/TmtForm.vue';
import Toast from '@/components/Toast.vue';
import { useToast } from '@/composables/useToast';
import { getEcho } from '@/echo';
import type { ConsultForm, Appointment } from '@/types/appointment';
import { savePendingConsultation, isOnline } from '@/utils/offlineSync';
import {
    validateConsultation,
    sanitizeText,
    MEDICATION_FREQUENCIES,
} from '@/utils/validation';

const { success: showSuccess, error: showError } = useToast();

const props = defineProps({
    user: { type: Object, required: true },
    queue: { type: Array<Appointment>, required: true },
});

const nextPatient = computed(() =>
    props.queue.length > 0 ? props.queue[0] : null,
);

// ── Real-time queue updates via WebSocket ──────────────────────────
let echoChannel: any = null;
let pollTimer: ReturnType<typeof setInterval> | null = null;

onMounted(() => {
    const echo = getEcho();

    if (echo && props.user?.id) {
        echoChannel = echo.channel(`doctor.${props.user.id}`);
        echoChannel.listen('QueueUpdated', () => {
            router.reload({ only: ['queue'] });
        });
    }

    // Polling fallback — refreshes queue every 15 seconds
    pollTimer = setInterval(() => {
        router.reload({ only: ['queue'] });
    }, 15000);
});

onUnmounted(() => {
    if (pollTimer) {
        clearInterval(pollTimer);
        pollTimer = null;
    }

    stopScanPolling();

    if (echoChannel) {
        const echo = getEcho();

        if (echo) {
echo.leave(`doctor.${props.user.id}`);
}

        echoChannel = null;
    }
});
// ────────────────────────────────────────────────────────────────────

// --- Doctor Notifications ---
const showDoctorNotifications = ref(false);
const doctorNotifRead = ref(false);

const doctorNotifications = computed(() => {
    const notifs = [];

    if (props.queue.length > 0) {
        notifs.push({
            id: 'queue-count',
            title: `${props.queue.length} Patient(s) in Queue Today`,
            message: `You have ${props.queue.length} appointment(s) remaining for today.`,
            time: 'Today',
        });
        const next = props.queue[0];
        notifs.push({
            id: 'next-patient',
            title: 'Next Patient',
            message: `${next.name} at ${next.time}`,
            time: 'Up next',
        });
    } else {
        notifs.push({
            id: 'queue-clear',
            title: 'Queue Clear',
            message: 'No remaining patients scheduled for today.',
            time: 'Today',
        });
    }

    return notifs;
});

const formattedDate = computed(() => {
    const today = new Date();

    return `${today.getMonth() + 1}/${today.getDate()}/${today.getFullYear()}`;
});

const isModalOpen = ref(false);
const activeAppt = ref<Appointment | null>(null);
const isSaving = ref(false);

/**
 * History URL for the appointment currently in the consultation modal.
 *
 * The route binds a User, but an appointment only carries the patients table
 * PK, so the user id has to come from the payload. Falls back to the eager
 * loaded relation and resolves to null when neither is present, which lets the
 * template hide the link instead of navigating to /doctor/patients/undefined.
 */
const activeHistoryUrl = computed<string | null>(() => {
    const appt = activeAppt.value;

    if (!appt) {
        return null;
    }

    const userId = appt.patient_user_id ?? appt.patient?.user_id;

    return userId ? `/doctor/patients/${userId}/history` : null;
});

const wantsFollowUp = ref(false);
const followUpForm = useForm({ date: '', time: '' });

const tomorrowDate = computed(() => {
    const d = new Date();
    d.setDate(d.getDate() + 1);

    return d.toISOString().split('T')[0];
});

// Medication frequency dropdown options
const medicationFrequencies = MEDICATION_FREQUENCIES;

const consultForm = useForm<ConsultForm>({
    symptoms: '',
    vitals: '',
    diagnosis: '',
    plan: '',
    consultation_type: [] as string[],
    ai_summary: '',
    prescriptions: [
        { medicine: '', dosage: '', frequency: '3x a day (8h)', duration: '' },
    ],
    echo_data: {
        requesting_physician: '',
        contact_no: '',
        age: '',
        sex: '',
        file_no: '',
        bsa: '',
        height: '',
        weight: '',
        mitral_ef: '',
        mitral_de: '',
        mitral_epss: '',
        mitral_area: '',
        ivs_dia: '',
        ivs_sys: '',
        ivs_exc: '',
        tricuspid_valve: 'normal',
        aortic_root: '',
        aortic_valve: '',
        aortic_excursion: '',
        lvpw_dia: '',
        lvpw_sys: '',
        lvpw_exc: '',
        ra_rl: '',
        pulmonic_root: '',
        pulmonic_notching: '',
        la_ap: '',
        la_rl: '',
        la_lavi: '',
        rv_ap: '',
        rv_rl: '',
        lv_dia: '',
        lv_sys: '',
        lv_lvedv: '',
        lv_lvesv: '',
        lv_mass: '',
        lv_rwt: '',
        lv_ef: '',
        lv_fs: '',
        hr: '',
        simpsons: '',
        tapse: '',
        peri_ant: '',
        peri_post: '',
        rvot: '',
        lvot: '',
        findings: '',
    },
    tmt_data: {
        age: '',
        pmhr: '',
        height: '',
        bp: '',
        weight: '',
        cell_no: '',
        birthday: '',
        medicines: '',
        requesting_physician: '',
        procedure: 'Treadmill Exercise Test',
        attending_physician: '',
        residence: '',
        consent_to: '',
        consent_day: '',
        consent_month: '',
        consent_place: '',
        witness_name: '',
        witness_address: '',
        protocol: 'BRUCE, MODIFIED BRUCE, NIH',
        indication: '',
        stages: [
            {
                name: 'Pre-test',
                speed: '0',
                grade: '0',
                mets: '0',
                bp: '',
                hr: '',
                remarks: '',
            },
            {
                name: 'STAGE 1',
                speed: '1.7',
                grade: '10',
                mets: '4.6',
                bp: '',
                hr: '',
                remarks: '',
            },
            {
                name: 'STAGE 2',
                speed: '2.5',
                grade: '12',
                mets: '7.0',
                bp: '',
                hr: '',
                remarks: '',
            },
            {
                name: 'STAGE 3',
                speed: '3.4',
                grade: '14',
                mets: '10.2',
                bp: '',
                hr: '',
                remarks: '',
            },
            {
                name: 'STAGE 4',
                speed: '4.2',
                grade: '16',
                mets: '13.5',
                bp: '',
                hr: '',
                remarks: '',
            },
            {
                name: 'STAGE 5',
                speed: '5.0',
                grade: '18',
                mets: '17.2',
                bp: '',
                hr: '',
                remarks: '',
            },
            {
                name: 'STAGE 6',
                speed: '5.5',
                grade: '20',
                mets: '20.4',
                bp: '',
                hr: '',
                remarks: '',
            },
            {
                name: 'STAGE 7',
                speed: '6.0',
                grade: '22',
                mets: '23.8',
                bp: '',
                hr: '',
                remarks: '',
            },
            {
                name: 'Immediate Recovery',
                speed: '-',
                grade: '-',
                mets: '-',
                bp: '',
                hr: '',
                remarks: '',
            },
            {
                name: '1 minutes',
                speed: '-',
                grade: '-',
                mets: '-',
                bp: '',
                hr: '',
                remarks: '',
            },
            {
                name: '3 minutes',
                speed: '-',
                grade: '-',
                mets: '-',
                bp: '',
                hr: '',
                remarks: '',
            },
            {
                name: '6 minutes',
                speed: '-',
                grade: '-',
                mets: '-',
                bp: '',
                hr: '',
                remarks: '',
            },
            {
                name: '9 minutes',
                speed: '-',
                grade: '-',
                mets: '-',
                bp: '',
                hr: '',
                remarks: '',
            },
        ],
        peak_stage: '',
        peak_minutes: '',
        peak_sbp: '',
        peak_hr: '',
        peak_mets: '',
        termination_reason: '',
        st_shape: '',
        st_displacement: '',
        st_duration: '',
        resting_ecg: '',
        response_to_exercise: '',
        exercise_capacity: '',
        arrhythmias: '',
        probability_cad: '',
        conclusion: '',
        pre_ef: '',
        post_ef: '',
    },
    doppler_data: {
        mv_peak_vel_e: '',
        mv_peak_vel_a: '',
        mv_peak_grad_e: '',
        mv_peak_grad_a: '',
        mv_ivrt: '',
        mv_regurg_frac: '',
        mv_dectime: '',
        mv_ea_ratio: '',
        mv_area_pht: '',
        mv_mean_pressure: '',
        td_septal_e: '',
        td_septal_a: '',
        td_septal_ee: '',
        td_lateral_e: '',
        td_lateral_a: '',
        td_lateral_ee: '',
        tv_e_wave: '',
        tv_a_wave: '',
        tv_regurg_frac: '',
        tv_peak_tr: '',
        tv_sys_pap: '',
        av_peak_vel_before: '',
        av_peak_vel_after: '',
        av_press_grad_before: '',
        av_press_grad_after: '',
        av_area: '',
        av_regurg_frac: '',
        av_ar_pht: '',
        av_mean_grad: '',
        av_ar_jet: 'Faint',
        pv_accel_time: '',
        pv_press_grad: '',
        pv_regurg: '',
        pv_sys_pap: '',
        pv_pr_jet: 'Faint',
        pv_rvot_vti: '',
        qp_qs: '',
        conclusion: '',
    },
    manual_draft_data: {
        requesting_physician: '',
        title: '',
        draft: '',
        conclusion: '',
        images: [],
    },
});

const vitalsForm = reactive({
    bp_sys: null as number | null,
    bp_dia: null as number | null,
    hr: null as number | null,
    rr: null as number | null,
    temp: null as number | null,
    spo2: null as number | null,
});

function composeVitals(): string {
    const parts: string[] = [];

    if (vitalsForm.bp_sys != null && vitalsForm.bp_dia != null) {
        parts.push(`BP: ${vitalsForm.bp_sys}/${vitalsForm.bp_dia} mmHg`);
    }

    if (vitalsForm.hr != null) {
        parts.push(`HR: ${vitalsForm.hr} bpm`);
    }

    if (vitalsForm.rr != null) {
        parts.push(`RR: ${vitalsForm.rr}`);
    }

    if (vitalsForm.temp != null) {
        parts.push(`Temp: ${vitalsForm.temp}°C`);
    }

    if (vitalsForm.spo2 != null) {
        parts.push(`SpO2: ${vitalsForm.spo2}%`);
    }

    return parts.join(' | ');
}

function formatPatientIdentifier(): string {
    if (!activeAppt.value) {
        return 'N/A';
    }

    // Use UUID or hashed value if available, otherwise use internal ID
    // This replaces the sequential padded ID to prevent enumeration
    return `PT-${activeAppt.value.id}`;
}

function openConsultModal(appt: Appointment | null) {
    if (!appt) {
        return;
    }

    activeAppt.value = appt;
    isModalOpen.value = true;
    stopScanPolling();
    loadDraft();

    // Polling is owned by the caller: scheduleScanPoll() starts the loop when
    // the first load reports unfinished OCR, and each tick re-arms it.
    loadScans().then(() => {
        if (scansBusy.value) {
            scheduleScanPoll();
        }
    });

    // Auto-generate file number (e.g., Year 26 + Appt ID)
    const currentYear = new Date().getFullYear().toString().substring(2);
    consultForm.echo_data.file_no = `${currentYear}-${String(appt.id).padStart(4, '0')}`;
}

// ---------------------------------------------------------------------------
// OCR scans captured by reception, shown read-only beside the consultation form.
// Reception may still be scanning when the doctor opens the visit, so this
// re-fetches while any document is still pending/processing.
// ---------------------------------------------------------------------------
const scans = ref<any[]>([]);
const scansLoading = ref(false);
const selectedScanId = ref<number | null>(null);
const scannedToPrescription = ref<Record<number, boolean>>({});

let scanPollTimer: ReturnType<typeof setTimeout> | null = null;

const selectedScan = computed(
    () => scans.value.find((s) => s.id === selectedScanId.value) ?? null,
);

const scansBusy = computed(() =>
    scans.value.some(
        (s) => s.ocr_status === 'pending' || s.ocr_status === 'processing',
    ),
);

const VISUAL_LABELS: Record<string, string> = {
    seal: 'Seal / stamp',
    signature: 'Signature',
    qr_code: 'QR code',
    table: 'Table',
    checkbox: 'Checkbox',
    figure: 'Figure',
};

const LAYOUT_LABELS: Record<string, string> = {
    heading: 'Heading',
    body: 'Body',
    list: 'List',
    caption: 'Caption',
    table: 'Table',
};

/** Grouped counts of the non-text marks found on the page. */
const visualChips = computed(() => {
    const counts = new Map<string, number>();

    for (const visual of selectedScan.value?.visuals ?? []) {
        const kind = visual?.kind;

        if (typeof kind === 'string') {
            counts.set(kind, (counts.get(kind) ?? 0) + 1);
        }
    }

    return Array.from(counts.entries()).map(([kind, count]) => ({
        kind,
        count,
        label: VISUAL_LABELS[kind] ?? kind.replace(/_/g, ' '),
    }));
});

function visualDetail(visual: any): string {
    if (visual?.value) return String(visual.value);
    if (visual?.interior_lines) return `${visual.interior_lines} text lines inside`;
    if (visual?.aspect_ratio) return `aspect ${visual.aspect_ratio}`;

    const bbox = visual?.bbox;

    return Array.isArray(bbox) ? `at ${bbox[0]},${bbox[1]}` : '';
}

function regionLabel(label: string): string {
    return LAYOUT_LABELS[label] ?? label;
}

function stopScanPolling(): void {
    if (scanPollTimer) {
        clearTimeout(scanPollTimer);
        scanPollTimer = null;
    }
}

async function loadScans(): Promise<void> {
    const appt = activeAppt.value;

    if (!appt) return;

    scansLoading.value = true;

    try {
        // Starting the consultation is what flips the visit to In Progress, so
        // this also records the transition the old modal-only flow missed.
        const { data } = await axios.post(
            `/doctor/appointments/${appt.id}/consultation/start`,
        );

        scans.value = data?.documents ?? [];

        if (selectedScanId.value === null && scans.value.length) {
            selectedScanId.value = scans.value[0].id;
        }

        // Drop a selection whose document vanished between polls.
        if (
            selectedScanId.value !== null &&
            !scans.value.some((s: any) => s.id === selectedScanId.value)
        ) {
            selectedScanId.value = scans.value[0]?.id ?? null;
        }
    } catch (error: any) {
        // A doctor without a linked provider record gets 403 here; the SOAP form
        // still works, so surface the scans problem without blocking the visit.
        showError(
            error?.response?.status === 403
                ? 'Could not load scans: no provider record linked to your account.'
                : 'Could not load scans for this appointment.',
        );
    } finally {
        scansLoading.value = false;
    }
}

function scheduleScanPoll(delay = 2500): void {
    stopScanPolling();

    if (!isModalOpen.value || !scansBusy.value) {
        return;
    }

    scanPollTimer = setTimeout(async () => {
        scanPollTimer = null;
        await loadScans();

        // scheduleScanPoll re-checks isModalOpen/scansBusy, so closing the
        // modal or reaching a terminal OCR status ends the loop on its own.
        scheduleScanPoll(delay);
    }, delay);
}

/**
 * Confidence as a whole percentage, or null when the OCR engine did not report
 * one. The hosted PaddleOCR cloud API returns no per-line score, so null means
 * "unknown" and must not be rendered as 0%.
 */
function confidencePercent(value: number | null): number | null {
    return value === null ? null : Math.round(value * 100);
}

/** "42%", "N/A", ... for the scan badges. */
function confidenceLabel(value: number | null): string {
    const pct = confidencePercent(value);

    return pct === null ? 'N/A' : `${pct}%`;
}

function confidenceClass(value: number | null): string {
    if (value === null) {
        return 'border-gray-200 bg-gray-50 text-gray-500';
    }

    if (value >= 0.9) return 'border-emerald-200 bg-emerald-50 text-emerald-700';
    if (value >= 0.7) return 'border-amber-200 bg-amber-50 text-amber-700';

    return 'border-red-200 bg-red-50 text-red-700';
}

function scanTypeLabel(value: string): string {
    return (
        {
            prescription: 'Prescription',
            lab_result: 'Lab Result',
            referral: 'Referral',
            other: 'Other',
        }[value] ?? value
    );
}

/**
 * Hands the dragged scan to any drop target (the Manual Draft form). The
 * payload carries the document id, public URL and filename so the receiving
 * form can show a thumbnail and the backend can re-resolve the stored image.
 */
function onScanDragStart(event: DragEvent, scan: any): void {
    const payload = JSON.stringify({
        id: scan.id,
        url: scan.url,
        name: scan.original_filename ?? `Scan ${scan.id}`,
    });

    event.dataTransfer?.setData('application/x-clinic-scan', payload);
    event.dataTransfer?.setData('text/plain', payload);

    if (event.dataTransfer) {
        event.dataTransfer.effectAllowed = 'copy';
    }
}

/**
 * Copies a recognised medication into the SOAP prescription rows. OCR stays a
 * suggestion — the doctor still edits and submits it like any other entry.
 */
function applyMedication(doc: any, med: any): void {
    if (!doc || !med?.medication_name) return;

    consultForm.prescriptions.push({
        medicine: med.medication_name,
        dosage: med.dosage ?? '',
        frequency: med.frequency ?? '3x a day (8h)',
        duration: med.duration_days ? `${med.duration_days} day(s)` : '',
    });

    scannedToPrescription.value[doc.id] = true;
    showSuccess(`Added ${med.medication_name} to the prescription draft.`);
}

function resetVitalsForm() {
    vitalsForm.bp_sys = null;
    vitalsForm.bp_dia = null;
    vitalsForm.hr = null;
    vitalsForm.rr = null;
    vitalsForm.temp = null;
    vitalsForm.spo2 = null;
}

// ── Local draft auto-save (30-minute TTL, per-appointment) ─────────
const DRAFT_TTL = 30 * 60 * 1000;

function draftKey() {
    const appt = activeAppt.value;

    return appt ? `consult_draft_${appt.id}` : null;
}

function saveDraft() {
    const key = draftKey();

    if (!key) {
return;
}

    const payload = consultForm.data();
    const vitalFields = { ...vitalsForm };
    const draft = { payload, vitalFields, savedAt: Date.now() };

    try {
        localStorage.setItem(key, JSON.stringify(draft));
    } catch { /* storage full — silently ignore */ }
}

function clearDraft() {
    const key = draftKey();

    if (key) {
localStorage.removeItem(key);
}
}

function loadDraft() {
    const key = draftKey();

    if (!key) {
return;
}

    try {
        const raw = localStorage.getItem(key);

        if (!raw) {
return;
}

        const draft = JSON.parse(raw);

        if (Date.now() - draft.savedAt > DRAFT_TTL) {
            localStorage.removeItem(key);

            return;
        }

        Object.assign(consultForm, draft.payload);

        if (draft.vitalFields) {
            Object.assign(vitalsForm, draft.vitalFields);
        }
    } catch {
 clearDraft(); 
}
}

// Auto-save on form changes while modal is open (debounced ~2s)
let draftTimer: ReturnType<typeof setTimeout> | null = null;
watch(
    [consultForm, vitalsForm, isModalOpen],
    () => {
        if (!isModalOpen.value) {
return;
}

        if (draftTimer) {
clearTimeout(draftTimer);
}

        draftTimer = setTimeout(saveDraft, 2000);
    },
    { deep: true },
);

const closeConsultModal = () => {
    isModalOpen.value = false;
    wantsFollowUp.value = false;
    followUpForm.reset();
    consultForm.reset();
    resetVitalsForm();
    clearDraft();
    stopScanPolling();
    scans.value = [];
    selectedScanId.value = null;
    scansLoading.value = false;
    scannedToPrescription.value = {};
};

const addMedication = () => {
    consultForm.prescriptions.push({
        medicine: '',
        dosage: '',
        frequency: '3x a day (8h)',
        duration: '',
    });
};

const removeMedication = (index: number) => {
    consultForm.prescriptions.splice(index, 1);
};

/** Returns true if any meaningful (non-empty) value exists in a clinical data object. */
const hasClinicalData = (data: Record<string, unknown>): boolean => {
    return Object.entries(data).some(([key, v]) => {
        if (key === 'file_no') {
            return false; // auto-generated field, ignore
        }

        if (Array.isArray(v)) {
            return (
                v.length > 0 &&
                v.some((item) =>
                    typeof item === 'object' && item !== null
                        ? Object.values(item as Record<string, unknown>).some(
                              (x) => x !== '' && x !== null && x !== undefined,
                          )
                        : Boolean(item),
                )
            );
        }

        return v !== null && v !== undefined && v !== '';
    });
};

const saveConsultation = async () => {
    const appt = activeAppt.value;

    if (!appt) {
 showError('No active appointment selected.');

 return; 
}

    // 1. Sanitize and validate form data
    const sanitizedForm = {
        ...consultForm.data(),
        symptoms: sanitizeText(consultForm.symptoms),
        vitals: sanitizeText(composeVitals()),
        diagnosis: sanitizeText(consultForm.diagnosis),
        plan: sanitizeText(consultForm.plan),
    };

    const validationErrors = validateConsultation(sanitizedForm);

    if (validationErrors.length > 0) {
        const errorMessages = validationErrors.map((e) => e.message).join(', ');
        showError(`Validation failed: ${errorMessages}`);

        return;
    }

    if (!activeAppt.value) {
        return;
    }

    // Warn if a consultation type was selected but its form was left empty
    const selectedTypes = consultForm.consultation_type;
    const emptyTypes: string[] = [];

    if (
        selectedTypes.includes('Type 1') &&
        !hasClinicalData(
            consultForm.echo_data as unknown as Record<string, unknown>,
        )
    ) {
        emptyTypes.push('Echo (Type 1)');
    }

    if (
        selectedTypes.includes('Type 2') &&
        !hasClinicalData(
            consultForm.tmt_data as unknown as Record<string, unknown>,
        )
    ) {
        emptyTypes.push('TMT (Type 2)');
    }

    if (
        selectedTypes.includes('Type 3') &&
        !hasClinicalData(
            consultForm.doppler_data as unknown as Record<string, unknown>,
        )
    ) {
        emptyTypes.push('Doppler (Type 3)');
    }

    if (
        selectedTypes.includes('Type 4') &&
        !hasClinicalData(
            consultForm.manual_draft_data as unknown as Record<
                string,
                unknown
            >,
        )
    ) {
        emptyTypes.push('Manual Draft (Type 4)');
    }

    if (emptyTypes.length > 0) {
        showError(
            `No clinical data was entered for: ${emptyTypes.join(', ')}. Please fill in the form(s) or uncheck those service types before saving.`,
        );

        return;
    }

    isSaving.value = true;

    if (!isOnline()) {
        try {
            closeConsultModal();
            const formPayload = consultForm.data();
            await savePendingConsultation(appt.id, formPayload);
            showSuccess('Saved offline — will sync automatically when connected.');

            return;
        } catch (idbErr) {
            showError('Could not save consultation offline. Please try again when connected.');
            console.error('IndexedDB save error:', idbErr);

            return;
        } finally {
            isSaving.value = false;
        }
    }

    try {
        // Save consultation data (backend sets status to 'Completed')
        await new Promise((resolve, reject) => {
            consultForm
                .transform((data) => ({
                    ...data,
                    // Compose structured vitals inputs into the standard format
                    vitals: composeVitals(),
                    // Ensure duration is always a string (number inputs emit numbers)
                    prescriptions: data.prescriptions?.map((p: any) => ({
                        ...p,
                        duration: String(p.duration ?? ''),
                    })),
                    // Only send clinical data for selected types; null out the rest
                    // so the backend doesn't persist empty JSON objects.
                    echo_data: data.consultation_type.includes('Type 1')
                        ? data.echo_data
                        : null,
                    tmt_data: data.consultation_type.includes('Type 2')
                        ? data.tmt_data
                        : null,
                    doppler_data: data.consultation_type.includes('Type 3')
                        ? data.doppler_data
                        : null,
                    manual_draft_data: data.consultation_type.includes('Type 4')
                        ? data.manual_draft_data
                        : null,
                }))
                .post(`/appointments/${appt.id}/complete`, {
                    preserveScroll: true,
                    onSuccess: () => {
                        // Build an informative saved-types message
                        const typeLabels: Record<string, string> = {
                            'Type 1': 'Echo',
                            'Type 2': 'TMT',
                            'Type 3': 'Doppler',
                            'Type 4': 'Manual Draft',
                        };
                        const savedLabels = selectedTypes
                            .filter(
                                (t) =>
                                    (t === 'Type 1' &&
                                        hasClinicalData(
                                            consultForm.echo_data as unknown as Record<
                                                string,
                                                unknown
                                            >,
                                        )) ||
                                    (t === 'Type 2' &&
                                        hasClinicalData(
                                            consultForm.tmt_data as unknown as Record<
                                                string,
                                                unknown
                                            >,
                                        )) ||
                                    (t === 'Type 3' &&
                                        hasClinicalData(
                                            consultForm.doppler_data as unknown as Record<
                                                string,
                                                unknown
                                            >,
                                        )) ||
                                    (t === 'Type 4' &&
                                        hasClinicalData(
                                            consultForm.manual_draft_data as unknown as Record<
                                                string,
                                                unknown
                                            >,
                                        )),
                            )
                            .map((t) => typeLabels[t]);

                        const clinicalNote =
                            savedLabels.length > 0
                                ? ` ${savedLabels.join(' + ')} data saved — exportable from patient history.`
                                : '';

                        // If doctor wants follow-up, schedule it
                        if (
                            wantsFollowUp.value &&
                            followUpForm.date &&
                            followUpForm.time
                        ) {
                            followUpForm.post(
                                `/appointments/${appt.id}/follow-up`,
                                {
                                    preserveScroll: true,
                                    onSuccess: () => {
                                        showSuccess(
                                            `Consultation saved and follow-up scheduled!${clinicalNote}`,
                                        );
                                        resolve(null);
                                    },
                                    onError: () => {
                                        showError(
                                            'Consultation saved, but follow-up could not be scheduled.',
                                        );
                                        resolve(null);
                                    },
                                },
                            );
                        } else {
                            showSuccess(
                                `Consultation record saved successfully!${clinicalNote}`,
                            );
                            resolve(null);
                        }
                    },
                    onError: reject,
                });
        });

        closeConsultModal();
    } catch (error: any) {
        const msg =
            error?.response?.data?.message ||
            error?.response?.data?.errors
                ? Object.values(error.response.data.errors).flat().join(', ')
                : 'Failed to save consultation. Please try again.';
        showError(msg);
        console.error('Consultation save error:', error);
    } finally {
        isSaving.value = false;
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
        transform: translateY(20px) scale(0.98);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

/* Ensure all form inputs have visible text color */
input,
textarea,
select {
    color: #111827; /* text-gray-900 */
}
form label,
.grid span.text-left,
:deep(label),
:deep(.grid span) {
    color: #1f2937 !important; /* text-gray-800 */
}
/* Specificity for modal content to be sure */
.fixed.inset-0 input,
.fixed.inset-0 textarea,
.fixed.inset-0 select {
    color: #111827 !important;
}
</style>
