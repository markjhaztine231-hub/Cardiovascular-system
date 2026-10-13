<template>
    <div
        class="min-h-screen bg-gray-50/50 py-8 font-sans selection:bg-[#0f8c8e] selection:text-white"
    >
        <!-- Toast Notifications -->
        <Toast />
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <header
                class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h1
                        class="text-3xl font-black tracking-tight text-gray-900"
                    >
                        Receptionist Dashboard
                    </h1>
                    <p class="mt-1 text-sm font-medium text-gray-500">
                        Manage Queues, Transfers, and Doctor Schedules
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <!-- Notification Bell -->
                    <div class="relative">
                        <div
                            v-if="showRecepNotifications"
                            class="fixed inset-0 z-40"
                            @click="showRecepNotifications = false"
                        ></div>
                        <button
                            @click="
                                showRecepNotifications = !showRecepNotifications
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
                                v-if="recepUnreadCount > 0"
                                class="absolute top-1.5 right-1.5 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[9px] font-black text-white ring-2 ring-white"
                                >{{ recepUnreadCount }}</span
                            >
                        </button>
                        <div
                            v-if="showRecepNotifications"
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
                                        recepNotifications.length
                                    }}
                                    Alert(s)</span
                                >
                            </div>
                            <div
                                class="max-h-72 divide-y divide-gray-50 overflow-y-auto"
                            >
                                <div
                                    v-if="recepNotifications.length === 0"
                                    class="p-6 text-center text-sm text-gray-500"
                                >
                                    No alerts right now.
                                </div>
                                <div
                                    v-for="notif in recepNotifications"
                                    :key="notif.id"
                                    class="p-4"
                                    :class="notif.urgent ? 'bg-amber-50' : ''"
                                >
                                    <p
                                        class="text-sm font-bold"
                                        :class="
                                            notif.urgent
                                                ? 'text-amber-800'
                                                : 'text-gray-800'
                                        "
                                    >
                                        {{ notif.title }}
                                    </p>
                                    <p class="mt-1 text-xs text-gray-600">
                                        {{ notif.message }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <Link
                        href="/profile"
                        class="inline-flex items-center rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-bold text-gray-700 transition-all hover:bg-gray-50"
                        >My Profile</Link
                    >
                    <Link
                        href="/appointments/returning"
                        class="inline-flex items-center rounded-xl border border-teal-200 bg-teal-50 px-4 py-2.5 text-sm font-bold text-[#0f8c8e] transition-all hover:bg-teal-100"
                        >Returning Patient</Link
                    >
                    <Link
                        href="/appointments/create"
                        class="inline-flex items-center rounded-xl bg-[#0f8c8e] px-4 py-2.5 text-sm font-bold text-white shadow-sm transition-all hover:bg-[#0c7072]"
                        >New Walk-In</Link
                    >
                    <LogoutButton>
                        <button
                            type="button"
                            class="inline-flex items-center rounded-xl border border-red-100 bg-red-50 px-4 py-2.5 text-sm font-bold text-red-700 transition-all hover:bg-red-100"
                        >
                            Sign out
                        </button>
                    </LogoutButton>
                </div>
            </header>

            <!-- Stats Grid -->
            <div class="mb-8 grid grid-cols-1 gap-6 md:grid-cols-3">
                <div
                    class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm"
                >
                    <p
                        class="text-sm font-bold tracking-widest text-gray-500 uppercase"
                    >
                        Total Registered
                    </p>
                    <p class="mt-2 text-4xl font-black text-gray-900">
                        {{ stats?.totalPatients || 0 }}
                    </p>
                </div>
                <div
                    class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm"
                >
                    <p
                        class="text-sm font-bold tracking-widest text-gray-500 uppercase"
                    >
                        Appointments Today
                    </p>
                    <p class="mt-2 text-4xl font-black text-blue-600">
                        {{ stats?.appointmentsToday || 0 }}
                    </p>
                </div>
                <div
                    class="rounded-2xl border border-b-4 border-gray-200 border-b-emerald-500 bg-white p-6 shadow-sm"
                >
                    <p
                        class="text-sm font-bold tracking-widest text-gray-500 uppercase"
                    >
                        Completed Today
                    </p>
                    <p class="mt-2 text-4xl font-black text-emerald-600">
                        {{ stats?.completedToday || 0 }}
                    </p>
                </div>
            </div>

            <!-- Completed Today by Service -->
            <div class="mb-8">
                <h2
                    class="mb-3 text-xs font-extrabold tracking-widest text-gray-400 uppercase"
                >
                    Completed Today by Service
                </h2>
                <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                    <div
                        class="rounded-2xl border border-l-4 border-gray-200 border-l-indigo-500 bg-white p-5 shadow-sm"
                    >
                        <p
                            class="text-xs font-bold tracking-widest text-gray-500 uppercase"
                        >
                            Consultation
                        </p>
                        <p class="mt-1 text-3xl font-black text-indigo-600">
                            {{ stats?.completedConsultationToday || 0 }}
                        </p>
                    </div>
                    <div
                        class="rounded-2xl border border-l-4 border-gray-200 border-l-fuchsia-500 bg-white p-5 shadow-sm"
                    >
                        <p
                            class="text-xs font-bold tracking-widest text-gray-500 uppercase"
                        >
                            Procedure
                        </p>
                        <p class="mt-1 text-3xl font-black text-fuchsia-600">
                            {{ stats?.completedProcedureToday || 0 }}
                        </p>
                    </div>
                    <div
                        class="rounded-2xl border border-l-4 border-gray-200 border-l-cyan-500 bg-white p-5 shadow-sm"
                    >
                        <p
                            class="text-xs font-bold tracking-widest text-gray-500 uppercase"
                        >
                            Consultation &amp; Procedure
                        </p>
                        <p class="mt-1 text-3xl font-black text-cyan-600">
                            {{
                                stats?.completedConsultationProcedureToday || 0
                            }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- THE MASTER QUEUE -->
            <div
                class="mb-8 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
            >
                <div class="border-b border-gray-100 bg-gray-50/50 px-6 py-5">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-black text-gray-800">
                            Clinic Queue (Today)
                        </h3>
                        <button
                            @click="calendarOpen = !calendarOpen"
                            class="inline-flex items-center gap-2 rounded-lg border px-3.5 py-2 text-xs font-bold shadow-sm transition-all"
                            :class="
                                calendarOpen
                                    ? 'border-[#0f8c8e] bg-[#0f8c8e] text-white hover:bg-[#0c7072]'
                                    : 'border-[#0f8c8e]/40 bg-white text-[#0f8c8e] hover:border-[#0f8c8e] hover:bg-[#0f8c8e]/10'
                            "
                            :title="
                                calendarOpen
                                    ? 'Hide upcoming calendar'
                                    : 'Show upcoming calendar'
                            "
                        >
                            <svg
                                class="h-4 w-4"
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
                            {{
                                calendarOpen ? 'Hide Calendar' : 'Show Calendar'
                            }}
                        </button>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full border-collapse text-left">
                        <thead>
                            <tr class="border-b border-gray-100 bg-white">
                                <th
                                    class="px-6 py-4 text-xs font-extrabold tracking-widest text-gray-400 uppercase"
                                >
                                    Time
                                </th>
                                <th
                                    class="px-6 py-4 text-xs font-extrabold tracking-widest text-gray-400 uppercase"
                                >
                                    Patient
                                </th>
                                <th
                                    class="px-6 py-4 text-xs font-extrabold tracking-widest text-gray-400 uppercase"
                                >
                                    Assigned Doctor
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
                                v-for="appt in masterQueue"
                                :key="appt.id"
                                class="transition-colors hover:bg-gray-50"
                            >
                                <td
                                    class="px-6 py-4 text-sm font-bold text-gray-900"
                                >
                                    {{ appt.time }}
                                </td>
                                <td
                                    class="px-6 py-4 text-sm font-bold text-gray-700"
                                >
                                    {{ appt.patient?.name || 'Unknown' }}
                                </td>
                                <td
                                    class="px-6 py-4 text-sm font-bold text-[#0f8c8e]"
                                >
                                    {{ appt.doctor?.name || 'Unassigned' }}
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        class="rounded-md border px-2.5 py-1 text-xs font-bold tracking-wider uppercase"
                                        :class="
                                            appt.status === 'Completed'
                                                ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
                                                : appt.status === 'No-Show'
                                                  ? 'border-red-200 bg-red-50 text-red-700'
                                                  : appt.status ===
                                                      'Reschedule Requested'
                                                    ? 'border-amber-200 bg-amber-50 text-amber-700'
                                                    : 'border-yellow-200 bg-yellow-50 text-yellow-700'
                                        "
                                    >
                                        {{ appt.status || 'Pending' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div
                                        v-if="
                                            appt.status !== 'Completed' &&
                                            appt.status !== 'No-Show' &&
                                            appt.status !== 'Cancelled'
                                        "
                                        class="flex justify-end gap-2"
                                    >
                                        <button
                                            @click="openTransfer(appt)"
                                            class="inline-flex items-center gap-1 rounded-lg border border-blue-200 bg-blue-50 px-3 py-1.5 text-[11px] font-bold text-blue-700 transition-colors hover:bg-blue-600 hover:text-white"
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
                                                    d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"
                                                />
                                            </svg>
                                            Transfer
                                        </button>
                                        <button
                                            @click="openQueueReschedule(appt)"
                                            class="inline-flex items-center gap-1 rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-[11px] font-bold text-amber-700 transition-colors hover:bg-amber-600 hover:text-white"
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
                                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                                                />
                                            </svg>
                                            Reschedule
                                        </button>
                                        <a
                                            v-if="appt.google_event_url"
                                            :href="appt.google_event_url"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="inline-flex items-center gap-1 rounded-lg border border-[#4285f4]/30 bg-[#4285f4]/10 px-3 py-1.5 text-[11px] font-bold text-[#4285f4] transition-all hover:bg-[#4285f4]/20"
                                        >
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor">
                                                <path d="M19.5 3h-2.25V1.5h-1.5V3h-7.5V1.5h-1.5V3H4.5A1.5 1.5 0 003 4.5v15A1.5 1.5 0 004.5 21h15a1.5 1.5 0 001.5-1.5v-15A1.5 1.5 0 0019.5 3zm0 16.5h-15V9h15v10.5zm0-12h-15V4.5h2.25V6h1.5V4.5h7.5V6h1.5V4.5h2.25V7.5z"/>
                                            </svg>
                                            Calendar
                                        </a>
                                        <button
                                            @click="openStudyForm(appt)"
                                            class="inline-flex items-center gap-1.5 rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-[11px] font-bold text-indigo-700 transition-all hover:bg-indigo-600 hover:text-white"
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
                                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                                />
                                            </svg>
                                            Study Form
                                        </button>
                                        <button
                                            @click="openScanModal(appt)"
                                            class="inline-flex items-center gap-1.5 rounded-lg border border-teal-200 bg-teal-50 px-3 py-1.5 text-[11px] font-bold text-teal-700 transition-all hover:bg-teal-600 hover:text-white"
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
                                                    d="M7 16a4 4 0 01-.88-7.9A5 5 0 0115.9 6M15 13l4 4m0-4l-4 4"
                                                />
                                            </svg>
                                            Upload Image
                                            <span
                                                v-if="appt.scan_count"
                                                class="ml-0.5 rounded-full px-1.5 py-px text-[10px] font-extrabold"
                                                :class="
                                                    appt.scans_in_progress
                                                        ? 'bg-blue-100 text-blue-700'
                                                        : 'bg-teal-100 text-teal-800'
                                                "
                                            >
                                                {{
                                                    appt.scans_in_progress
                                                        ? '…'
                                                        : appt.scan_count
                                                }}
                                            </span>
                                        </button>
                                        <button
                                            @click="markNoShow(appt.id)"
                                            class="inline-flex items-center gap-1 rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-[11px] font-bold text-red-700 transition-colors hover:bg-red-600 hover:text-white"
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
                                                    d="M6 18L18 6M6 6l12 12"
                                                />
                                            </svg>
                                            No-Show
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="masterQueue.length === 0">
                                <td
                                    colspan="5"
                                    class="px-6 py-8 text-center font-medium text-gray-500"
                                >
                                    No appointments today.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- UPCOMING CALENDAR (Google Calendar style) -->
                <div
                    v-if="calendarOpen"
                    class="border-t border-gray-100 bg-white"
                >
                    <div
                        class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-6 py-3"
                    >
                        <div class="flex items-center gap-1.5">
                            <button
                                @click="goToToday"
                                class="rounded-lg border border-gray-300 bg-white px-3.5 py-1.5 text-xs font-bold text-gray-700 shadow-sm transition-colors hover:bg-gray-100"
                            >
                                Today
                            </button>
                            <button
                                @click="shiftMonth(-1)"
                                class="rounded-lg border border-gray-300 bg-white p-1.5 text-sm font-bold text-gray-500 transition-colors hover:bg-gray-100"
                                title="Previous month"
                            >
                                ‹
                            </button>
                            <button
                                @click="shiftMonth(1)"
                                class="rounded-lg border border-gray-300 bg-white p-1.5 text-sm font-bold text-gray-500 transition-colors hover:bg-gray-100"
                                title="Next month"
                            >
                                ›
                            </button>
                        </div>
                        <h3 class="text-sm font-black text-gray-800">
                            {{ calendarLabel }}
                        </h3>
                        <span
                            class="text-[10px] font-extrabold tracking-widest text-gray-400 uppercase"
                        >
                            {{ dayCount }} upcoming appointment(s)
                        </span>
                    </div>

                    <div
                        class="grid grid-cols-7 border-b border-gray-100 bg-gray-50/60"
                    >
                        <div
                            v-for="day in [
                                'Sun',
                                'Mon',
                                'Tue',
                                'Wed',
                                'Thu',
                                'Fri',
                                'Sat',
                            ]"
                            :key="day"
                            class="border-r border-gray-100 px-1 py-2 text-center text-[10px] font-extrabold tracking-widest text-gray-500 uppercase last:border-r-0"
                        >
                            {{ day }}
                        </div>
                    </div>

                    <div class="divide-y divide-gray-100 border-b border-gray-100">
                        <div
                            v-for="(week, weekIndex) in calendarWeeks"
                            :key="weekIndex"
                            class="grid grid-cols-7"
                        >
                            <div
                                v-for="cell in week"
                                :key="cell.key"
                                @click="selectDay(cell)"
                                class="min-h-[6rem] cursor-pointer border-r border-gray-100 p-1.5 transition-colors last:border-r-0 hover:bg-teal-50"
                                :class="[
                                    cell.inMonth
                                        ? 'bg-white'
                                        : 'bg-gray-50/80',
                                    isToday(cell.key)
                                        ? 'ring-1 ring-inset ring-[#0f8c8e]/50'
                                        : '',
                                ]"
                            >
                                <div
                                    class="mb-1 flex items-center justify-between gap-1"
                                >
                                    <span
                                        v-if="cell.activity"
                                        class="rounded px-1 py-0.5 text-[9px] font-extrabold"
                                        :class="activityBadgeClass(cell.activity)"
                                        :title="activityTooltip(cell.activity)"
                                    >
                                        {{ cell.activity.completed }}/{{
                                            cell.activity.total
                                        }}
                                    </span>
                                    <span v-else class="w-0"></span>
                                    <span
                                        class="flex h-6 w-6 items-center justify-center rounded-full text-xs font-bold"
                                        :class="
                                            isToday(cell.key)
                                                ? 'bg-[#0f8c8e] text-white'
                                                : cell.inMonth
                                                  ? 'text-gray-700'
                                                  : 'text-gray-300'
                                        "
                                    >
                                        {{ cell.day }}
                                    </span>
                                </div>
                                <div class="flex flex-col gap-0.5">
                                    <div
                                        v-for="chip in cell.appts.slice(0, 3)"
                                        :key="chip.id"
                                        class="truncate rounded border-l-2 px-1 py-0.5 text-[9px] leading-tight font-bold shadow-sm"
                                        :class="chipClass(chip.status)"
                                        :title="
                                            `${chip.patient?.name || 'Unknown'} — ${chip.time}`
                                        "
                                    >
                                        {{ chip.time }}
                                        {{ chip.patient?.name || 'Unknown' }}
                                    </div>
                                    <span
                                        v-if="cell.appts.length > 3"
                                        class="px-1 text-[9px] font-bold text-gray-500"
                                    >
                                        +{{ cell.appts.length - 3 }} more
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Selected-day agenda -->
                    <div class="bg-gray-50/50 px-6 py-4">
                        <h4
                            class="text-xs font-extrabold tracking-widest text-gray-500 uppercase"
                        >
                            {{ selectedDayLabel }}
                        </h4>
                        <div
                            v-if="selectedDayActivity"
                            class="mt-2 flex flex-wrap items-center gap-2 text-xs"
                        >
                            <span class="font-bold text-gray-700">
                                {{ selectedDayActivity.total }} appointment(s)
                            </span>
                            <span
                                class="rounded-md border border-emerald-200 bg-emerald-50 px-2 py-0.5 font-bold text-emerald-700"
                            >
                                {{ selectedDayActivity.completed }} completed
                            </span>
                            <span
                                v-if="selectedDayActivity.completed_consultation"
                                class="rounded-md border border-indigo-200 bg-indigo-50 px-2 py-0.5 font-bold text-indigo-700"
                            >
                                Consultation
                                {{ selectedDayActivity.completed_consultation }}
                            </span>
                            <span
                                v-if="selectedDayActivity.completed_procedure"
                                class="rounded-md border border-fuchsia-200 bg-fuchsia-50 px-2 py-0.5 font-bold text-fuchsia-700"
                            >
                                Procedure
                                {{ selectedDayActivity.completed_procedure }}
                            </span>
                            <span
                                v-if="
                                    selectedDayActivity.completed_consultation_procedure
                                "
                                class="rounded-md border border-cyan-200 bg-cyan-50 px-2 py-0.5 font-bold text-cyan-700"
                            >
                                Consult &amp; Proc
                                {{
                                    selectedDayActivity.completed_consultation_procedure
                                }}
                            </span>
                        </div>
                        <div
                            v-if="selectedAppts.length > 0"
                            class="mt-2 divide-y divide-gray-100"
                        >
                            <div
                                v-for="appt in selectedAppts"
                                :key="appt.id"
                                class="flex flex-col gap-1 py-2.5 sm:flex-row sm:items-center sm:justify-between sm:gap-4"
                            >
                                <div
                                    class="flex min-w-0 flex-1 items-center gap-3"
                                >
                                    <span
                                        class="w-16 shrink-0 text-xs font-black text-gray-900"
                                    >
                                        {{ appt.time }}
                                    </span>
                                    <span
                                        class="truncate text-xs font-bold text-gray-700"
                                    >
                                        {{ appt.patient?.name || 'Unknown' }}
                                    </span>
                                    <span
                                        class="truncate text-xs font-medium text-[#0f8c8e]"
                                    >
                                        {{ appt.doctor?.name || 'Unassigned' }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span
                                        class="text-[10px] font-medium text-gray-500"
                                    >
                                        {{ appt.service_type }}
                                    </span>
                                    <span
                                        class="rounded-md border px-2 py-0.5 text-[10px] font-bold tracking-wider uppercase"
                                        :class="statusClass(appt.status)"
                                    >
                                        {{ appt.status }}
                                    </span>
                                </div>
                                <div
                                    v-if="
                                        appt.status !== 'Completed' &&
                                        appt.status !== 'No-Show' &&
                                        appt.status !== 'Cancelled'
                                    "
                                    class="flex flex-wrap gap-1.5"
                                >
                                    <button
                                        @click="openTransfer(appt)"
                                        class="rounded-lg border border-blue-200 bg-blue-50 px-2.5 py-1 text-[11px] font-bold text-blue-700 transition-colors hover:bg-blue-600 hover:text-white"
                                    >
                                        Transfer
                                    </button>
                                    <button
                                        @click="openQueueReschedule(appt)"
                                        class="rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-1 text-[11px] font-bold text-amber-700 transition-colors hover:bg-amber-600 hover:text-white"
                                    >
                                        Reschedule
                                    </button>
                                    <button
                                        @click="openScanModal(appt)"
                                        class="rounded-lg border border-teal-200 bg-teal-50 px-2.5 py-1 text-[11px] font-bold text-teal-700 transition-colors hover:bg-teal-600 hover:text-white"
                                    >
                                        Upload Image
                                    </button>
                                    <button
                                        @click="cancelAppointment(appt.id)"
                                        class="rounded-lg border border-red-200 bg-red-50 px-2.5 py-1 text-[11px] font-bold text-red-700 transition-colors hover:bg-red-600 hover:text-white"
                                    >
                                        Cancel
                                    </button>
                                </div>
                            </div>
                        </div>
                        <p
                            v-else
                            class="mt-2 text-xs font-medium text-gray-500"
                        >
                            No appointments scheduled for this day.
                        </p>
                    </div>
                </div>
            </div>

            <!-- DAILY ACTIVITY HISTORY -->
            <div
                class="mb-8 rounded-2xl border border-gray-200 bg-white shadow-sm"
            >
                <div class="border-b border-gray-200 px-6 py-4">
                    <h3 class="font-bold text-gray-800">
                        Daily Activity History
                    </h3>
                    <p class="mt-0.5 text-xs text-gray-500">
                        Appointments and completed visits per calendar day (most
                        recent 14)
                    </p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead
                            class="bg-gray-50 text-[11px] font-bold tracking-wider text-gray-500 uppercase"
                        >
                            <tr>
                                <th class="px-6 py-3">Date</th>
                                <th class="px-6 py-3 text-center">Total</th>
                                <th class="px-6 py-3 text-center">Completed</th>
                                <th class="px-6 py-3 text-center">
                                    Consultation
                                </th>
                                <th class="px-6 py-3 text-center">Procedure</th>
                                <th class="px-6 py-3 text-center">
                                    Consult &amp; Proc
                                </th>
                                <th class="px-6 py-3 text-center">Rate</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr
                                v-for="row in recentActivity"
                                :key="row.key"
                                class="hover:bg-gray-50"
                            >
                                <td class="px-6 py-3 font-semibold text-gray-700">
                                    {{ row.label }}
                                </td>
                                <td class="px-6 py-3 text-center text-gray-600">
                                    {{ row.total }}
                                </td>
                                <td
                                    class="px-6 py-3 text-center font-bold text-emerald-600"
                                >
                                    {{ row.completed }}
                                </td>
                                <td class="px-6 py-3 text-center text-indigo-600">
                                    {{ row.completed_consultation }}
                                </td>
                                <td class="px-6 py-3 text-center text-fuchsia-600">
                                    {{ row.completed_procedure }}
                                </td>
                                <td class="px-6 py-3 text-center text-cyan-600">
                                    {{ row.completed_consultation_procedure }}
                                </td>
                                <td class="px-6 py-3 text-center">
                                    <span
                                        class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-bold"
                                        :class="
                                            row.completed >= row.total
                                                ? 'bg-emerald-100 text-emerald-700'
                                                : 'bg-gray-100 text-gray-600'
                                        "
                                    >
                                        {{
                                            row.total > 0
                                                ? Math.round(
                                                      (row.completed /
                                                          row.total) *
                                                          100,
                                                  )
                                                : 0
                                        }}%
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="recentActivity.length === 0">
                                <td
                                    colspan="7"
                                    class="px-6 py-8 text-center text-sm text-gray-500"
                                >
                                    No appointment history yet.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- RESCHEDULE REQUESTS -->
            <div
                v-if="rescheduleRequests.length > 0"
                class="mb-8 overflow-hidden rounded-2xl border border-amber-200 bg-white shadow-sm"
            >
                <div
                    class="flex items-center justify-between border-b border-amber-100 bg-amber-50 px-6 py-4"
                >
                    <div class="flex items-center gap-3">
                        <svg
                            class="h-5 w-5 text-amber-600"
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
                        <h3 class="text-base font-black text-amber-800">
                            Reschedule Requests
                        </h3>
                    </div>
                    <span
                        class="rounded-full bg-amber-500 px-2.5 py-0.5 text-xs font-black text-white"
                        >{{ rescheduleRequests.length }}</span
                    >
                </div>
                <div class="divide-y divide-gray-50">
                    <div
                        v-for="req in rescheduleRequests"
                        :key="req.id"
                        class="flex flex-col gap-4 px-6 py-5 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div class="flex-1">
                            <p class="text-sm font-black text-gray-900">
                                {{ req.patient?.name || 'Unknown Patient' }}
                            </p>
                            <p class="mt-0.5 text-xs font-medium text-gray-500">
                                with {{ req.doctor?.name || 'Unassigned' }}
                            </p>
                        </div>
                        <div
                            class="min-w-35 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-center"
                        >
                            <p
                                class="text-[10px] font-extrabold tracking-widest text-gray-400 uppercase"
                            >
                                Current
                            </p>
                            <p class="text-xs font-bold text-gray-700">
                                {{ req.current_date }}
                            </p>
                            <p class="text-xs text-gray-500">
                                {{ req.current_time }}
                            </p>
                        </div>
                        <svg
                            class="hidden h-5 w-5 shrink-0 text-gray-400 sm:block"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M13 7l5 5m0 0l-5 5m5-5H6"
                            />
                        </svg>
                        <div
                            class="min-w-35 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-center"
                        >
                            <p
                                class="text-[10px] font-extrabold tracking-widest text-amber-600 uppercase"
                            >
                                Requested
                            </p>
                            <p class="text-xs font-bold text-amber-800">
                                {{ req.requested_date }}
                            </p>
                            <p class="text-xs text-amber-600">
                                {{ req.requested_time }}
                            </p>
                        </div>
                        <div class="flex gap-2 sm:ml-4">
                            <button
                                @click="openEditReschedule(req)"
                                class="rounded-lg bg-blue-600 px-4 py-2 text-xs font-bold text-white shadow-sm transition-colors hover:bg-blue-700"
                            >
                                ✎ Edit
                            </button>
                            <button
                                @click="approveReschedule(req.id)"
                                class="rounded-lg bg-emerald-600 px-4 py-2 text-xs font-bold text-white shadow-sm transition-colors hover:bg-emerald-700"
                            >
                                ✓ Approve
                            </button>
                            <button
                                @click="denyReschedule(req.id)"
                                class="rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-xs font-bold text-red-600 transition-colors hover:bg-red-100"
                            >
                                ✕ Deny
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
                <!-- SCHEDULE PLOTTER -->
                <div
                    class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm"
                >
                    <h3 class="mb-6 text-lg font-black text-gray-800">
                        {{
                            isEditing
                                ? 'Weekly Availability'
                                : 'Plot Weekly Availabilities'
                        }}
                    </h3>
                    <form
                        @submit.prevent="submitSchedule"
                        class="mb-6 border-b border-gray-100 pb-6"
                    >
                        <div class="mb-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div class="md:col-span-2">
                                <label
                                    class="mb-1 block text-xs font-extrabold tracking-widest text-gray-500 uppercase"
                                    >Select Doctor</label
                                >
                                <select
                                    v-model="scheduleForm.doctor_id"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-900 outline-none focus:border-[#0f8c8e]"
                                    required
                                >
                                    <option value="" disabled>Select...</option>
                                    <option
                                        v-for="doc in doctors"
                                        :key="doc.id"
                                        :value="doc.id"
                                    >
                                        {{ doc.name }} ({{ doc.specialty }})
                                    </option>
                                </select>
                            </div>
                            <div class="md:col-span-2">
                                <label
                                    class="mb-1 block text-xs font-extrabold tracking-widest text-gray-500 uppercase"
                                    >Select Days</label
                                >
                                <div class="flex flex-wrap gap-2">
                                    <label
                                        v-for="day in [
                                            'Monday',
                                            'Tuesday',
                                            'Wednesday',
                                            'Thursday',
                                            'Friday',
                                            'Saturday',
                                            'Sunday',
                                        ]"
                                        :key="day"
                                        class="cursor-pointer"
                                    >
                                        <input
                                            type="checkbox"
                                            :value="day"
                                            v-model="scheduleForm.day_of_week"
                                            class="peer sr-only"
                                        />
                                        <div
                                            class="rounded-lg border border-gray-200 px-3 py-1.5 text-sm font-medium text-gray-600 transition-colors peer-checked:border-[#0f8c8e] peer-checked:bg-[#0f8c8e] peer-checked:text-white hover:bg-gray-50"
                                        >
                                            {{ day.substring(0, 3) }}
                                        </div>
                                    </label>
                                </div>
                            </div>
                            <div>
                                <label
                                    class="mb-1 block text-xs font-extrabold tracking-widest text-gray-500 uppercase"
                                    >Start Time</label
                                >
                                <input
                                    type="time"
                                    v-model="scheduleForm.start_time"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-2 py-2 text-sm font-medium text-gray-900 outline-none focus:border-[#0f8c8e]"
                                    required
                                />
                            </div>
                            <div>
                                <label
                                    class="mb-1 block text-xs font-extrabold tracking-widest text-gray-500 uppercase"
                                    >End Time</label
                                >
                                <input
                                    type="time"
                                    v-model="scheduleForm.end_time"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-2 py-2 text-sm font-medium text-gray-900 outline-none focus:border-[#0f8c8e]"
                                    required
                                />
                            </div>
                        </div>
                        <div class="flex gap-3">
                            <button
                                v-if="isEditing"
                                type="button"
                                @click="cancelEdit"
                                class="w-1/3 rounded-lg border border-gray-300 bg-white py-2.5 font-bold text-gray-600 shadow-sm hover:bg-gray-50"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="scheduleForm.processing"
                                class="flex-1 rounded-lg bg-[#0f8c8e] py-2.5 font-bold text-white shadow-sm hover:bg-[#0c7072] disabled:opacity-50"
                            >
                                {{
                                    scheduleForm.processing
                                        ? 'Saving...'
                                        : isEditing
                                          ? 'Update Schedule'
                                          : 'Save Recurring Schedule'
                                }}
                            </button>
                        </div>
                    </form>

                    <h4
                        class="mb-3 text-xs font-extrabold tracking-widest text-gray-400 uppercase"
                    >
                        Active Weekly Schedules
                    </h4>
                    <ul
                        class="max-h-64 divide-y divide-gray-100 overflow-y-auto pr-2 text-sm"
                    >
                        <li
                            v-for="sch in schedules"
                            :key="sch.id"
                            class="group flex items-center justify-between py-3"
                        >
                            <div class="flex-1">
                                <p class="font-bold text-gray-900">
                                    {{ sch.doctor?.name || 'Unknown Doctor' }}
                                </p>
                                <p
                                    class="mt-0.5 text-[10px] leading-tight font-extrabold tracking-widest text-gray-500 uppercase"
                                >
                                    EVERY {{ formatDays(sch.day_of_week) }}
                                </p>
                            </div>
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex items-center gap-2 text-xs font-bold text-[#0f8c8e]"
                                >
                                    <span
                                        class="rounded border border-teal-100 bg-teal-50 px-2 py-1"
                                    >
                                        {{ sch.start_time?.substring(0, 5) }} –
                                        {{ sch.end_time?.substring(0, 5) }}
                                    </span>
                                </div>
                                <div class="flex gap-2">
                                    <button
                                        @click="editSchedule(sch)"
                                        class="text-blue-500 hover:text-blue-700"
                                    >
                                        <svg
                                            class="h-4 w-4"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"
                                            />
                                        </svg>
                                    </button>
                                    <button
                                        @click="deleteSchedule(sch.id)"
                                        class="text-red-500 hover:text-red-700"
                                    >
                                        <svg
                                            class="h-4 w-4"
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
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>

                <!-- AUDIT LOGS -->
                <div
                    class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm"
                >
                    <h3 class="mb-6 text-lg font-black text-gray-800">
                        Recent Audit Logs
                    </h3>
                    <ul class="space-y-4 text-sm">
                        <li
                            v-for="(log, i) in auditLogs"
                            :key="i"
                            class="border-b border-gray-50 pb-4 last:border-0"
                        >
                            <p class="font-medium text-gray-800">
                                <span class="font-black text-[#0f8c8e]">{{
                                    log.user || 'System'
                                }}</span>
                                {{ log.action }}
                            </p>
                            <p class="mt-1 text-xs text-gray-400">
                                {{ log.time }}
                            </p>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- TRANSFER MODAL -->
        <div
            v-if="isTransferOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
        >
            <div
                class="w-full max-w-md transform overflow-hidden rounded-2xl bg-white shadow-2xl transition-all"
            >
                <div
                    class="flex items-center justify-between bg-[#146b8a] px-6 py-4 text-white"
                >
                    <h3 class="text-lg font-bold">Transfer Patient</h3>
                    <button
                        @click="isTransferOpen = false"
                        class="text-teal-200 hover:text-white"
                    >
                        ✕
                    </button>
                </div>
                <div class="p-6">
                    <p class="mb-4 text-sm text-gray-600">
                        Select a new doctor for
                        <b class="text-gray-900">{{
                            activeAppt?.patient?.name
                        }}</b
                        >.
                    </p>
                    <form @submit.prevent="submitTransfer">
                        <select
                            v-model="transferForm.doctor_id"
                            class="mb-6 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 font-medium text-gray-800 outline-none focus:border-[#0f8c8e]"
                            required
                        >
                            <option value="" disabled>Select Doctor...</option>
                            <option
                                v-for="doc in doctors"
                                :key="doc.id"
                                :value="doc.id"
                            >
                                {{ doc.name }} ({{ doc.specialty }})
                            </option>
                        </select>
                        <div
                            class="flex justify-end gap-3 border-t border-gray-100 pt-4"
                        >
                            <button
                                type="button"
                                @click="isTransferOpen = false"
                                class="px-5 py-2.5 text-sm font-bold text-gray-500 hover:text-gray-800"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="transferForm.processing"
                                class="rounded-xl bg-[#0f8c8e] px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-[#0c7072] disabled:opacity-50"
                            >
                                Confirm Transfer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- EDIT RESCHEDULE MODAL (from pending request) -->
        <div
            v-if="isEditingReschedule"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
        >
            <div
                class="w-full max-w-md transform overflow-hidden rounded-2xl bg-white shadow-2xl transition-all"
            >
                <div
                    class="flex items-center justify-between bg-blue-600 px-6 py-4 text-white"
                >
                    <h3 class="text-lg font-bold">Edit Reschedule Request</h3>
                    <button
                        @click="cancelEditReschedule"
                        class="text-blue-200 hover:text-white"
                    >
                        ✕
                    </button>
                </div>
                <div class="p-6">
                    <p class="mb-6 text-sm text-gray-600">
                        Edit the new appointment time for
                        <b class="text-gray-900">{{
                            editingReschedule?.patient?.name
                        }}</b
                        >.
                    </p>
                    <form @submit.prevent="submitEditReschedule">
                        <div class="mb-4">
                            <label
                                class="mb-2 block text-sm font-bold text-gray-700"
                            >
                                New Date
                            </label>
                            <input
                                v-model="rescheduleEditForm.requested_date"
                                type="date"
                                class="w-full rounded-lg border border-gray-300 px-4 py-2 text-gray-800 outline-none focus:border-blue-500 focus:ring-blue-500"
                                required
                            />
                        </div>
                        <div class="mb-6">
                            <label
                                class="mb-2 block text-sm font-bold text-gray-700"
                            >
                                New Time
                            </label>
                            <input
                                v-model="rescheduleEditForm.requested_time"
                                type="time"
                                class="w-full rounded-lg border border-gray-300 px-4 py-2 text-gray-800 outline-none focus:border-blue-500 focus:ring-blue-500"
                                required
                            />
                        </div>
                        <div
                            class="flex justify-end gap-3 border-t border-gray-100 pt-4"
                        >
                            <button
                                type="button"
                                @click="cancelEditReschedule"
                                class="px-5 py-2.5 text-sm font-bold text-gray-500 hover:text-gray-800"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="rescheduleEditForm.processing"
                                class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-blue-700 disabled:opacity-50"
                            >
                                Save & Approve
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- QUEUE RESCHEDULE MODAL (direct reschedule from queue) -->
        <div
            v-if="isQueueRescheduleOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
        >
            <div
                class="w-full max-w-md transform overflow-hidden rounded-2xl bg-white shadow-2xl transition-all"
            >
                <div
                    class="flex items-center justify-between bg-amber-600 px-6 py-4 text-white"
                >
                    <h3 class="text-lg font-bold">Reschedule Appointment</h3>
                    <button
                        @click="cancelQueueReschedule"
                        class="text-amber-200 hover:text-white"
                    >
                        ✕
                    </button>
                </div>
                <div class="p-6">
                    <p class="mb-6 text-sm text-gray-600">
                        Move
                        <b class="text-gray-900">{{
                            queueRescheduleAppt?.patient?.name
                        }}</b>
                        to a new date and time. The patient will be notified.
                    </p>
                    <form @submit.prevent="submitQueueReschedule">
                        <div class="mb-4">
                            <label
                                class="mb-2 block text-sm font-bold text-gray-700"
                            >
                                New Date
                            </label>
                            <input
                                v-model="queueRescheduleForm.date"
                                type="date"
                                :min="today"
                                class="w-full rounded-lg border border-gray-300 px-4 py-2 text-gray-800 outline-none focus:border-amber-500 focus:ring-amber-500"
                                required
                            />
                        </div>
                        <div class="mb-6">
                            <label
                                class="mb-2 block text-sm font-bold text-gray-700"
                            >
                                New Time
                            </label>
                            <input
                                v-model="queueRescheduleForm.time"
                                type="time"
                                class="w-full rounded-lg border border-gray-300 px-4 py-2 text-gray-800 outline-none focus:border-amber-500 focus:ring-amber-500"
                                required
                            />
                        </div>
                        <div
                            class="flex justify-end gap-3 border-t border-gray-100 pt-4"
                        >
                            <button
                                type="button"
                                @click="cancelQueueReschedule"
                                class="px-5 py-2.5 text-sm font-bold text-gray-500 hover:text-gray-800"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="queueRescheduleForm.processing"
                                class="rounded-lg bg-amber-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-amber-700 disabled:opacity-50"
                            >
                                Confirm Reschedule
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- RECEPTIONIST STUDY PRE-FILL MODAL (Echo / TMT / Doppler) -->
        <div
            v-if="studyFormOpen"
            class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/60 p-4 backdrop-blur-sm"
        >
            <div
                class="my-6 w-full max-w-7xl transform overflow-hidden rounded-2xl bg-white shadow-2xl transition-all"
            >
                <div
                    class="sticky top-0 z-10 flex items-center justify-between rounded-t-2xl bg-indigo-700 px-6 py-4 text-white"
                >
                    <div>
                        <h3 class="text-lg font-bold">
                            Pre-fill Echo / TMT / Doppler / Stress Echo / ABPM
                        </h3>
                        <p class="text-xs text-indigo-200">
                            Recording measurements for
                            <b class="text-white">{{
                                studyAppt?.patient?.name
                            }}</b>
                            — the doctor will see them pre-filled in the
                            consultation.
                        </p>
                    </div>
                    <button
                        @click="closeStudyForm"
                        class="text-indigo-200 hover:text-white"
                    >
                        ✕
                    </button>
                </div>

                <div v-if="studyLoading" class="p-10 text-center">
                    <p class="text-sm font-bold text-gray-500">
                        Loading saved study data…
                    </p>
                </div>

                <div v-else class="p-6">
                    <div
                        class="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_360px]"
                    >
                        <!-- LEFT: study form -->
                        <div class="min-w-0">
                            <div
                                v-if="studyLocked"
                                class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800"
                            >
                                This appointment has already been completed by
                                the doctor, so its study records are final and
                                can no longer be edited.
                            </div>

                            <div
                                class="mb-6 flex flex-wrap gap-2 border-b border-gray-100 pb-4"
                            >
                                <button
                                    v-for="tab in studyTabs"
                                    :key="tab.value"
                                    type="button"
                                    @click="activeStudyTab = tab.value"
                                    class="rounded-lg px-4 py-2 text-sm font-bold transition-colors"
                                    :class="
                                        activeStudyTab === tab.value
                                            ? 'bg-indigo-600 text-white'
                                            : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                                    "
                                >
                                    {{ tab.label }}
                                </button>
                            </div>

                            <div class="max-h-[62vh] overflow-y-auto pr-2">
                                <EchoForm
                                    v-if="activeStudyTab === 'echo'"
                                    v-model:echo-data="studyForm.echo_data"
                                    :patient-name="studyAppt?.patient?.name ?? ''"
                                    restricted
                                />
                                <TmtForm
                                    v-if="activeStudyTab === 'tmt'"
                                    v-model:tmt-data="studyForm.tmt_data"
                                    :echo-sex="studyForm.echo_data.sex"
                                    :patient-name="studyAppt?.patient?.name ?? ''"
                                    restricted
                                />
                                <DopplerForm
                                    v-if="activeStudyTab === 'doppler'"
                                    v-model:doppler-data="studyForm.doppler_data"
                                    :echo-data="studyForm.echo_data"
                                    :patient-name="studyAppt?.patient?.name ?? ''"
                                    restricted
                                />
                                <StressEchoForm
                                    v-if="activeStudyTab === 'stress_echo'"
                                    v-model:stress-echo-data="studyForm.stress_echo_data"
                                    :patient-name="studyAppt?.patient?.name ?? ''"
                                    restricted
                                />
                                <AbpmForm
                                    v-if="activeStudyTab === 'abpm'"
                                    v-model:abpm-data="studyForm.abpm_data"
                                    :patient-name="studyAppt?.patient?.name ?? ''"
                                    restricted
                                />
                            </div>

                            <div
                                class="mt-6 flex items-center justify-end gap-3 border-t border-gray-100 pt-4"
                            >
                                <p
                                    v-if="studySavedAt"
                                    class="mr-auto text-xs font-semibold text-emerald-600"
                                >
                                    Saved study data will appear in the
                                    doctor's consultation form.
                                </p>
                                <button
                                    type="button"
                                    @click="closeStudyForm"
                                    class="px-5 py-2.5 text-sm font-bold text-gray-500 hover:text-gray-800"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="button"
                                    @click="submitStudyForm"
                                    :disabled="
                                        studyForm.processing ||
                                        studyBusy ||
                                        studyLocked
                                    "
                                    class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-indigo-700 disabled:opacity-50"
                                >
                                    {{
                                        studyForm.processing
                                            ? 'Saving…'
                                            : 'Save Study Data'
                                    }}
                                </button>
                            </div>
                        </div>

                        <!-- RIGHT: OCR scans & converted text -->
                        <aside class="min-w-0">
                            <section
                                class="rounded-xl border border-gray-200 bg-gray-50 p-4"
                            >
                                <h4
                                    class="mb-3 flex items-center justify-between text-xs font-extrabold tracking-widest text-gray-500 uppercase"
                                >
                                    Scans & converted text
                                    <span
                                        class="rounded-full bg-indigo-100 px-2 py-0.5 text-[10px] font-bold text-indigo-600"
                                    >
                                        {{ studyScans.length }}
                                    </span>
                                </h4>

                                <div
                                    v-if="studyScans.length > 0"
                                    class="max-h-[62vh] space-y-3 overflow-y-auto pr-1"
                                >
                                    <div
                                        v-for="doc in studyScans"
                                        :key="doc.id"
                                        class="rounded-lg border border-gray-200 bg-white p-3"
                                    >
                                        <div
                                            class="flex flex-wrap items-center justify-between gap-2"
                                        >
                                            <div
                                                class="flex min-w-0 items-center gap-2"
                                            >
                                                <span
                                                    class="shrink-0 rounded-md px-2 py-0.5 text-[10px] font-extrabold tracking-wide uppercase"
                                                    :class="
                                                        studyScanTypeBadge(
                                                            doc.type,
                                                        )
                                                    "
                                                >
                                                    {{
                                                        studyScanTypeLabel(
                                                            doc.type,
                                                        )
                                                    }}
                                                </span>
                                                <a
                                                    v-if="doc.url"
                                                    :href="doc.url"
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    class="truncate text-[13px] font-bold text-gray-800 hover:text-[#146b8a]"
                                                >
                                                    {{
                                                        doc.original_filename ||
                                                        `Scan #${doc.id}`
                                                    }}
                                                </a>
                                                <span
                                                    v-else
                                                    class="truncate text-[13px] font-bold text-gray-800"
                                                >
                                                    {{
                                                        doc.original_filename ||
                                                        `Scan #${doc.id}`
                                                    }}
                                                </span>
                                            </div>
                                            <div
                                                class="flex shrink-0 items-center gap-2"
                                            >
                                                <span
                                                    class="rounded-md px-2 py-0.5 text-[10px] font-bold"
                                                    :class="
                                                        studyScanStatusBadge(
                                                            doc.ocr_status,
                                                        )
                                                    "
                                                >
                                                    {{
                                                        studyScanStatusLabel(
                                                            doc.ocr_status,
                                                        )
                                                    }}
                                                </span>
                                                <span
                                                    v-if="
                                                        doc.ocr_confidence !==
                                                        null
                                                    "
                                                    class="rounded-md bg-gray-200 px-2 py-0.5 text-[10px] font-bold text-gray-600"
                                                >
                                                    {{
                                                        studyScanConfidence(
                                                            doc.ocr_confidence,
                                                        )
                                                    }}
                                                </span>
                                            </div>
                                        </div>

                                        <p
                                            v-if="doc.ocr_error"
                                            class="mt-2 text-xs font-semibold text-red-600"
                                        >
                                            {{ doc.ocr_error }}
                                        </p>

                                        <div
                                            class="mt-2 border-t border-gray-100 pt-2"
                                        >
                                            <button
                                                v-if="doc.ocr_text"
                                                type="button"
                                                @click="
                                                    toggleStudyScanText(doc.id)
                                                "
                                                class="text-xs font-bold text-[#146b8a] hover:underline"
                                            >
                                                {{
                                                    openStudyScanText.has(
                                                        doc.id,
                                                    )
                                                        ? 'Hide converted text'
                                                        : 'Show converted text'
                                                }}
                                            </button>
                                            <pre
                                                v-if="
                                                    openStudyScanText.has(doc.id)
                                                "
                                                class="mt-2 max-h-48 overflow-auto rounded-lg bg-gray-50 p-3 text-xs whitespace-pre-wrap break-words text-gray-700"
                                            >{{ doc.ocr_text }}</pre>
                                            <p
                                                v-if="
                                                    !doc.ocr_text &&
                                                    doc.ocr_status !==
                                                        'processing' &&
                                                    doc.ocr_status !== 'pending'
                                                "
                                                class="text-xs text-gray-400"
                                            >
                                                No converted text for this scan.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <p v-else class="text-xs text-gray-400">
                                    No scans uploaded for this visit yet.
                                </p>
                            </section>
                        </aside>
                    </div>
                </div>
            </div>
        </div>

        <!-- OCR scan upload -->
        <ScanUploadModal
            :open="scanModalOpen"
            :appointment-id="scanAppointment?.id ?? null"
            :patient-name="scanAppointment?.patient?.name"
            :scans="scanAppointment?.scans ?? []"
            @close="closeScanModal"
            @uploaded="onScansChanged"
        />
    </div>
</template>

<script setup lang="ts">
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import LogoutButton from '@/components/LogoutButton.vue';
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import ScanUploadModal from '@/components/ScanUploadModal.vue';
import EchoForm from '@/components/Clinical/EchoForm.vue';
import TmtForm from '@/components/Clinical/TmtForm.vue';
import DopplerForm from '@/components/Clinical/DopplerForm.vue';
import StressEchoForm from '@/components/Clinical/StressEchoForm.vue';
import AbpmForm from '@/components/Clinical/AbpmForm.vue';
import Toast from '@/components/Toast.vue';
import { useToast } from '@/composables/useToast';
import { getEcho } from '@/echo';
import { blankStudyForms } from '@/utils/studyForms';

const { error: showError, success: showSuccess } = useToast();

// Surface server-side success/error flashes and validation errors as toasts —
// the other dashboards mount <Toast />; this page previously had no feedback.
const page = usePage();
watch(
    () => (page.props as any).flash,
    (flash) => {
        if (flash?.success) {
showSuccess(flash.success);
}

        if (flash?.error) {
showError(flash.error);
}
    },
    { immediate: true },
);
watch(
    () => (page.props as any).errors,
    (errors) => {
        if (errors && typeof errors === 'object' && Object.keys(errors).length) {
            showError(Object.values(errors as Record<string, string>).join('\n'));
        }
    },
);

const props = defineProps<{
    user?: any;
    stats?: any;
    doctors: any[];
    masterQueue: any[];
    futureAppointments: any[];
    schedules: any[];
    auditLogs: any[];
    rescheduleRequests: any[];
}>();

const calendarOpen = ref(false);
const calendarCursor = ref(new Date());
const selectedDateKey = ref(dateKey(new Date()));

const scanModalOpen = ref(false);
const scanAppointment = ref<any>(null);

function openScanModal(appt: any): void {
    scanAppointment.value = appt;
    scanModalOpen.value = true;
}

function closeScanModal(): void {
    scanModalOpen.value = false;
}

/** Keep the queue's scan badges honest once OCR finishes. */
function onScansChanged(): void {
    router.reload({ only: ['masterQueue'] });
}

// ── Receptionist study pre-fill (Echo / TMT / Doppler) ─────────────
const studyFormOpen = ref(false);
const studyAppt = ref<any>(null);
const studyLoading = ref(false);
const studyBusy = ref(false);
const studyLocked = ref(false);
const studySavedAt = ref(false);
const activeStudyTab = ref<
    'echo' | 'tmt' | 'doppler' | 'stress_echo' | 'abpm'
>('echo');

const studyTabs = [
    { value: 'echo', label: 'Echo' },
    { value: 'tmt', label: 'TMT' },
    { value: 'doppler', label: 'Doppler' },
    { value: 'stress_echo', label: 'Stress Echo' },
    { value: 'abpm', label: 'ABPM' },
] as const;

const studyForm = useForm(blankStudyForms());

const studyScans = ref<any[]>([]);
const openStudyScanText = ref<Set<number>>(new Set());

function toggleStudyScanText(id: number): void {
    const next = new Set(openStudyScanText.value);

    if (next.has(id)) {
        next.delete(id);
    } else {
        next.add(id);
    }

    openStudyScanText.value = next;
}

const studyScanTypeLabels: Record<string, string> = {
    echo: 'Echo',
    tmt: 'TMT',
    doppler: 'Doppler',
    prescription: 'Prescription',
    lab_result: 'Lab Result',
    referral: 'Referral',
    other: 'Other',
};

function studyScanTypeLabel(type: string): string {
    return studyScanTypeLabels[type] ?? 'Scan';
}

const studyScanTypeBadges: Record<string, string> = {
    echo: 'bg-indigo-100 text-indigo-700',
    tmt: 'bg-fuchsia-100 text-fuchsia-700',
    doppler: 'bg-cyan-100 text-cyan-700',
    prescription: 'bg-emerald-100 text-emerald-700',
    lab_result: 'bg-amber-100 text-amber-700',
    referral: 'bg-blue-100 text-blue-700',
};

function studyScanTypeBadge(type: string): string {
    return studyScanTypeBadges[type] ?? 'bg-gray-100 text-gray-600';
}

function studyScanStatusLabel(status: string): string {
    switch (status) {
        case 'completed':
            return 'Recognised';
        case 'processing':
        case 'pending':
            return 'Processing';
        case 'failed':
            return 'Failed';
        default:
            return status || 'Queued';
    }
}

function studyScanStatusBadge(status: string): string {
    switch (status) {
        case 'completed':
            return 'bg-emerald-100 text-emerald-700';
        case 'processing':
        case 'pending':
            return 'bg-blue-100 text-blue-700';
        case 'failed':
            return 'bg-red-100 text-red-700';
        default:
            return 'bg-gray-200 text-gray-600';
    }
}

function studyScanConfidence(value: number | null): string {
    return value === null || value === undefined ? 'N/A' : `${Math.round(value * 100)}%`;
}

/** Merge saved values into the blank form, keeping only non-empty keys so an
 * older record never leaves stray empty strings in the form. */
function mergeStudyValues(target: Record<string, any>, source: Record<string, any> | null): void {
    if (!source || typeof source !== 'object') return;

    for (const [key, value] of Object.entries(source)) {
        if (!(key in target)) continue;
        if (value === '' || value === null || value === undefined) continue;

        if (Array.isArray(value)) {
            if (Array.isArray(target[key])) {
                if (target[key].length === 0 && value.length > 0) {
                    target[key] = (value as any[]).map((item) =>
                        typeof item === 'object' && item !== null
                            ? { ...item }
                            : item,
                    );
                    continue;
                }

                (value as any[]).forEach((item, i) => {
                    if (i < target[key].length && typeof item === 'object' && item !== null) {
                        mergeStudyValues(target[key][i], item);
                    }
                });
            }
            continue;
        }

        target[key] = value;
    }
}

async function openStudyForm(appt: any): Promise<void> {
    if (!appt) return;

    studyAppt.value = appt;
    studyForm.clearErrors();
    studyForm.reset();
    studyFormOpen.value = true;
    activeStudyTab.value = 'echo';
    studyLocked.value = false;
    studySavedAt.value = false;
    studyLoading.value = true;
    studyBusy.value = true;

    try {
        const res = await fetch(`/appointments/${appt.id}/study-data`, {
            credentials: 'same-origin',
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });

        if (!res.ok) {
            throw new Error('Could not load saved study data.');
        }

        const data = await res.json();

        studyLocked.value = data.status === 'completed';
        studySavedAt.value = data.saved === true;

        const blank = studyForm.data();

        if (data.saved) {
            mergeStudyValues(blank.echo_data, data.echo_data);
            mergeStudyValues(blank.tmt_data, data.tmt_data);
            mergeStudyValues(blank.doppler_data, data.doppler_data);
            mergeStudyValues(blank.stress_echo_data, data.stress_echo_data);
            mergeStudyValues(blank.abpm_data, data.abpm_data);
        }

        studyForm.setData(blank);

        studyScans.value = Array.isArray(data.documents) ? data.documents : [];
    } catch (err: any) {
        showError(err?.message ?? 'Could not load saved study data.');
    } finally {
        studyLoading.value = false;
        studyBusy.value = false;
    }
}

function closeStudyForm(): void {
    studyFormOpen.value = false;
    studyAppt.value = null;
    studyLocked.value = false;
    studySavedAt.value = false;
    studyScans.value = [];
    openStudyScanText.value = new Set();
    studyForm.reset();
    studyForm.clearErrors();
}

function submitStudyForm(): void {
    if (!studyAppt.value) return;

    studyForm.put(`/appointments/${studyAppt.value.id}/study-data`, {
        preserveScroll: true,
        onSuccess: () => {
            studySavedAt.value = true;
            studyLocked.value = false;
        },
        onError: (errors) => {
            showError(Object.values(errors).join('\n'));
        },
    });
}

function pad2(n: number): string {
    return String(n).padStart(2, '0');
}
function dateKey(d: Date): string {
    return `${d.getFullYear()}-${pad2(d.getMonth() + 1)}-${pad2(d.getDate())}`;
}
function isToday(key: string): boolean {
    return key === dateKey(new Date());
}

const apptsByDay = computed<Record<string, any[]>>(() => {
    const map: Record<string, any[]> = {};

    for (const group of props.futureAppointments || []) {
        for (const appt of group.appointments || []) {
            const iso =
                appt.date_iso ??
                dateKey(new Date(`${appt.date} 00:00:00`));
            (map[iso] = map[iso] || []).push(appt);
        }
    }

    return map;
});

type DayActivity = {
    total: number;
    completed: number;
    completed_consultation: number;
    completed_procedure: number;
    completed_consultation_procedure: number;
};

const dailyActivity = computed<Record<string, DayActivity>>(
    () => (props.stats?.dailyActivity ?? {}) as Record<string, DayActivity>,
);

function activityFor(key: string): DayActivity | null {
    return dailyActivity.value[key] ?? null;
}

function activityBadgeClass(a: DayActivity): string {
    if (a.total > 0 && a.completed >= a.total) {
        return 'bg-emerald-100 text-emerald-700';
    }

    if (a.completed > 0) {
        return 'bg-teal-100 text-teal-700';
    }

    return 'bg-gray-100 text-gray-500';
}

function activityTooltip(a: DayActivity): string {
    return `${a.completed} of ${a.total} completed — Consultation ${a.completed_consultation}, Procedure ${a.completed_procedure}, Consult & Procedure ${a.completed_consultation_procedure}`;
}

const selectedDayActivity = computed<DayActivity | null>(() =>
    activityFor(selectedDateKey.value),
);

const recentActivity = computed(() => {
    const todayKey = dateKey(new Date());

    return Object.entries(dailyActivity.value)
        .filter(([key, a]) => a.total > 0 && key <= todayKey)
        .sort(([a], [b]) => (a < b ? 1 : -1))
        .slice(0, 14)
        .map(([key, a]) => ({
            key,
            label: new Date(`${key}T00:00:00`).toLocaleDateString('en-US', {
                weekday: 'short',
                month: 'short',
                day: 'numeric',
            }),
            ...a,
        }));
});

const calendarLabel = computed(() =>
    calendarCursor.value.toLocaleDateString('en-US', {
        month: 'long',
        year: 'numeric',
    }),
);

const calendarWeeks = computed(() => {
    const y = calendarCursor.value.getFullYear();
    const m = calendarCursor.value.getMonth();
    const startOffset = new Date(y, m, 1).getDay();
    const daysInMonth = new Date(y, m + 1, 0).getDate();
    const totalCells = Math.ceil((startOffset + daysInMonth) / 7) * 7;
    const weeks: any[][] = [];

    for (let i = 0; i < totalCells; i++) {
        const date = new Date(y, m, i - startOffset + 1);
        const key = dateKey(date);
        const cell = {
            date,
            key,
            day: date.getDate(),
            inMonth: date.getMonth() === m,
            appts: apptsByDay.value[key] || [],
            activity: activityFor(key),
        };

        if (i % 7 === 0) {
weeks.push([]);
}

        weeks[weeks.length - 1].push(cell);
    }

    return weeks;
});

const dayCount = computed(() =>
    Object.values(apptsByDay.value).reduce(
        (sum, list) => sum + list.length,
        0,
    ),
);

const shiftMonth = (n: number) => {
    calendarCursor.value = new Date(
        calendarCursor.value.getFullYear(),
        calendarCursor.value.getMonth() + n,
        1,
    );
};
const goToToday = () => {
    calendarCursor.value = new Date();
    selectedDateKey.value = dateKey(new Date());
};
const selectDay = (cell: any) => {
    selectedDateKey.value = cell.key;
};

const selectedAppts = computed(() => apptsByDay.value[selectedDateKey.value] || []);
const selectedDayLabel = computed(() => {
    const [y, m, d] = selectedDateKey.value.split('-').map(Number);

    return new Date(y, m - 1, d).toLocaleDateString('en-US', {
        weekday: 'long',
        month: 'long',
        day: 'numeric',
        year: 'numeric',
    });
});

const chipClass = (status: string): string => {
    switch (status) {
        case 'Completed':
            return 'border-emerald-400 bg-emerald-50 text-emerald-700';
        case 'No-Show':
            return 'border-red-400 bg-red-50 text-red-700';
        case 'Reschedule Requested':
            return 'border-amber-400 bg-amber-50 text-amber-700';
        case 'Cancelled':
            return 'border-gray-300 bg-gray-100 text-gray-500';
        case 'Scheduled':
            return 'border-[#0f8c8e] bg-[#0f8c8e]/10 text-[#0f8c8e]';
        default:
            return 'border-yellow-400 bg-yellow-50 text-yellow-700';
    }
};

const statusClass = (status: string): string => {
    switch (status) {
        case 'Completed':
            return 'border-emerald-200 bg-emerald-50 text-emerald-700';
        case 'No-Show':
            return 'border-red-200 bg-red-50 text-red-700';
        case 'Reschedule Requested':
            return 'border-amber-200 bg-amber-50 text-amber-700';
        case 'Cancelled':
            return 'border-gray-200 bg-gray-50 text-gray-500';
        case 'Scheduled':
            return 'border-blue-200 bg-blue-50 text-blue-700';
        default:
            return 'border-yellow-200 bg-yellow-50 text-yellow-700';
    }
};

const showRecepNotifications = ref(false);
const isEditing = ref(false);
const isTransferOpen = ref(false);
const activeAppt = ref<any>(null);
const isEditingReschedule = ref(false);
const editingReschedule = ref<any>(null);
const isQueueRescheduleOpen = ref(false);
const queueRescheduleAppt = ref<any>(null);
// Local midnight-based end of yesterday — the server enforces
// `after_or_equal:today` in Asia/Manila, so the <input :min> must use the
// viewer's local date, not UTC (toISOString() can skew a day near midnight).
const nowLocal = new Date();
const today = `${nowLocal.getFullYear()}-${pad2(nowLocal.getMonth() + 1)}-${pad2(nowLocal.getDate())}`;

const scheduleForm = useForm({
    id: null as number | null,
    doctor_id: '',
    day_of_week: [] as string[],
    start_time: '08:00',
    end_time: '17:00',
});

const transferForm = useForm({
    doctor_id: '',
});

const rescheduleEditForm = useForm({
    requested_date: '',
    requested_time: '',
});

const queueRescheduleForm = useForm({
    date: '',
    time: '',
});

// ── Real-time appointment updates via WebSocket ────────────────────
let echoChannel: any = null;
let pollTimer: ReturnType<typeof setInterval> | null = null;

onMounted(() => {
    const echo = getEcho();

    if (echo) {
        echoChannel = echo.channel('receptionist');
        echoChannel
            .listen('AppointmentStatusChanged', () => {
                router.reload({
                    only: [
                        'masterQueue',
                        'futureAppointments',
                        'rescheduleRequests',
                        'stats',
                    ],
                });
            })
            .listen('RescheduleRequested', () => {
                router.reload({
                    only: [
                        'masterQueue',
                        'futureAppointments',
                        'rescheduleRequests',
                        'stats',
                    ],
                });
            });
    }

    // Polling fallback — refreshes queue every 15 seconds
    pollTimer = setInterval(() => {
        router.reload({
            only: [
                'masterQueue',
                'futureAppointments',
                'rescheduleRequests',
                'stats',
            ],
        });
    }, 15000);
});

onUnmounted(() => {
    if (pollTimer) {
        clearInterval(pollTimer);
        pollTimer = null;
    }

    if (echoChannel) {
        const echo = getEcho();

        if (echo) {
            echo.leave('receptionist');
        }

        echoChannel = null;
    }
});
// ────────────────────────────────────────────────────────────────────

// Notifications Logic
const recepNotifications = computed(() => {
    const notifs = [];

    if (props.rescheduleRequests.length > 0) {
        notifs.push({
            id: 'reschedules',
            title: `${props.rescheduleRequests.length} Reschedule Request(s)`,
            message: 'Patients are waiting for approval.',
            urgent: true,
        });
    }

    return notifs;
});

const recepUnreadCount = computed(() => props.rescheduleRequests.length);

// Format Days helper
const formatDays = (daysData: any): string => {
    if (!daysData) {
        return 'N/A';
    }

    if (Array.isArray(daysData)) {
        return daysData.join(', ');
    }

    try {
        const parsed = JSON.parse(daysData);

        return Array.isArray(parsed) ? parsed.join(', ') : daysData;
    } catch {
        return String(daysData);
    }
};

// The backend sends display strings ("Sep 23, 2026" / "10:00 AM") for the
// reschedule cards; the edit modal needs ISO values or the native date/time
// inputs render empty and `required` silently blocks the submit button.
const isoDateFromDisplay = (v?: string | null): string => {
    if (!v) {
return '';
}

    const d = new Date(v);

    if (isNaN(d.getTime())) {
return '';
}

    return `${d.getFullYear()}-${pad2(d.getMonth() + 1)}-${pad2(d.getDate())}`;
};

const isoTimeFromDisplay = (v?: string | null): string => {
    if (!v) {
return '';
}

    const m = v.match(/^(\d{1,2}):(\d{2})\s*(AM|PM|am|pm)?$/);

    if (!m) {
return '';
}

    let h = Number(m[1]);
    const suffix = (m[3] || '').toUpperCase();

    if (suffix) {
        if (suffix === 'PM' && h !== 12) {
h += 12;
}

        if (suffix === 'AM' && h === 12) {
h = 0;
}
    }

    return `${pad2(h)}:${m[2]}`;
};

// Actions
const submitSchedule = () => {
    if (isEditing.value) {
        scheduleForm.put(`/receptionist/schedules/${scheduleForm.id}`, {
            preserveState: false,
            onSuccess: () => {
                cancelEdit();
                router.reload({ only: ['schedules'] });
            },
            onError: (errors) => {
                const msg = Object.values(errors).join('\n');
                showError(msg || 'Failed to save schedule');
            },
        });
    } else {
        scheduleForm.post('/receptionist/schedules', {
            preserveState: false,
            onSuccess: () => {
                scheduleForm.reset();
                router.reload({ only: ['schedules'] });
            },
            onError: (errors) => {
                const msg = Object.values(errors).join('\n');
                showError(msg || 'Failed to save schedule');
            },
        });
    }
};

const DAY_ORDER = [
    'Monday',
    'Tuesday',
    'Wednesday',
    'Thursday',
    'Friday',
    'Saturday',
    'Sunday',
];

const toDayNames = (daysData: any): string[] => {
    if (!daysData) {
return [];
}

    const list = Array.isArray(daysData)
        ? daysData
        : (() => {
              try {
                  const parsed = JSON.parse(daysData);

                  return Array.isArray(parsed) ? parsed : [];
              } catch {
                  return [];
              }
          })();

    return list.map((d: any) =>
        typeof d === 'number' || /^\d+$/.test(String(d))
            ? DAY_ORDER[Number(d) - 1] ?? String(d)
            : String(d),
    );
};

const editSchedule = (sch: any) => {
    isEditing.value = true;
    scheduleForm.id = sch.id;
    scheduleForm.doctor_id = sch.doctor_id;
    scheduleForm.start_time = sch.start_time?.substring(0, 5);
    scheduleForm.end_time = sch.end_time?.substring(0, 5);
    scheduleForm.day_of_week = toDayNames(sch.day_of_week);
};

const cancelEdit = () => {
    isEditing.value = false;
    scheduleForm.reset();
};

const deleteSchedule = (id: number) => {
    if (confirm('Delete this schedule?')) {
        router.delete(`/receptionist/schedules/${id}`, {
            preserveScroll: true,
        });
    }
};

const markNoShow = (id: number) => {
    if (confirm('Mark as No-Show?')) {
        router.put(
            `/appointments/${id}/status`,
            { status: 'No-Show' },
            { preserveScroll: true },
        );
    }
};

const cancelAppointment = (id: number) => {
    if (confirm('Cancel this appointment?')) {
        router.put(
            `/appointments/${id}/status`,
            { status: 'Cancelled' },
            { preserveScroll: true },
        );
    }
};

const openTransfer = (appt: any) => {
    activeAppt.value = appt;
    transferForm.doctor_id = appt.doctor?.id || '';
    isTransferOpen.value = true;
};

const submitTransfer = () => {
    transferForm.put(`/appointments/${activeAppt.value.id}/transfer`, {
        onSuccess: () => (isTransferOpen.value = false),
    });
};

const approveReschedule = (id: number) => {
    router.post(
        `/receptionist/appointments/${id}/approve-reschedule`,
        {},
        { preserveScroll: true },
    );
};

const denyReschedule = (id: number) => {
    if (confirm('Deny request?')) {
        router.post(
            `/receptionist/appointments/${id}/deny-reschedule`,
            {},
            { preserveScroll: true },
        );
    }
};

const openEditReschedule = (req: any) => {
    editingReschedule.value = req;
    rescheduleEditForm.requested_date = req.requested_date_iso || isoDateFromDisplay(req.requested_date);
    rescheduleEditForm.requested_time = req.requested_time_iso || isoTimeFromDisplay(req.requested_time);
    isEditingReschedule.value = true;
};

const cancelEditReschedule = () => {
    isEditingReschedule.value = false;
    editingReschedule.value = null;
    rescheduleEditForm.reset();
};

const submitEditReschedule = () => {
    router.post(
        `/receptionist/appointments/${editingReschedule.value.id}/edit-reschedule`,
        {
            date: rescheduleEditForm.requested_date,
            time: rescheduleEditForm.requested_time,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                cancelEditReschedule();
            },
            onError: (errors) => {
                showError(Object.values(errors).join('\n'));
            },
        },
    );
};

const openQueueReschedule = (appt: any) => {
    queueRescheduleAppt.value = appt;
    queueRescheduleForm.date = '';
    queueRescheduleForm.time = '';
    isQueueRescheduleOpen.value = true;
};

const cancelQueueReschedule = () => {
    isQueueRescheduleOpen.value = false;
    queueRescheduleAppt.value = null;
    queueRescheduleForm.reset();
};

const submitQueueReschedule = () => {
    router.post(
        `/receptionist/appointments/${queueRescheduleAppt.value.id}/edit-reschedule`,
        {
            date: queueRescheduleForm.date,
            time: queueRescheduleForm.time,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                cancelQueueReschedule();
            },
            onError: (errors) => {
                showError(Object.values(errors).join('\n'));
            },
        },
    );
};
</script>

<style scoped>
input,
select,
option {
    color: #111827 !important;
}
</style>
