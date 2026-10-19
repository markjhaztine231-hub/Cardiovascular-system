<template>
    <div
        class="flex h-screen bg-[#f4f7fa] font-sans selection:bg-[#005bb5] selection:text-white"
    >
        <!-- Toast Notifications -->
        <Toast />
        <!-- Mobile backdrop -->
        <div
            v-if="isMobileOpen"
            class="fixed inset-0 z-40 bg-black/50 md:hidden"
            @click="closeMobile"
        ></div>

        <!-- PATIENT SIDEBAR -->
        <aside
            class="fixed inset-y-0 left-0 z-50 flex shrink-0 flex-col bg-[#003366] text-white shadow-xl transition-[width,transform] duration-300 md:static md:z-20 md:flex"
            :class="[
                isMobileOpen
                    ? 'translate-x-0'
                    : '-translate-x-full md:translate-x-0',
                isOpen ? 'md:w-64' : 'md:w-0 md:overflow-hidden',
            ]"
        >
            <div class="flex h-full w-64 shrink-0 flex-col">
                <div
                    class="flex h-20 items-center border-b border-[#004080] bg-[#002244] px-6"
                >
                    <div
                        class="mr-3 rounded-lg bg-white p-2 text-xl font-bold text-[#003366] shadow-md"
                    >
                        ♥
                    </div>
                    <div class="flex-1">
                        <h1
                            class="text-lg leading-tight font-bold tracking-wide"
                        >
                            CardioCare
                        </h1>
                        <p
                            class="text-[10px] tracking-widest text-blue-200 uppercase"
                        >
                            Patient Portal
                        </p>
                    </div>
                    <button
                        type="button"
                        class="ml-2 hidden rounded-md p-1.5 text-blue-200 transition-colors hover:bg-[#004080] hover:text-white md:inline-flex"
                        @click="toggleDesktop"
                        aria-label="Toggle sidebar"
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
                                d="M4 6h16M4 12h16M4 18h16"
                            />
                        </svg>
                    </button>
                </div>

                <nav class="flex-1 overflow-y-auto py-6">
                    <div
                        class="mb-2 px-6 text-xs font-semibold tracking-wider text-blue-300 uppercase"
                    >
                        My Health
                    </div>
                    <ul class="space-y-1">
                        <li>
                            <button
                                @click="
                                    activeTab = 'dashboard';
                                    closeMobile();
                                "
                                class="flex w-full items-center px-6 py-3 text-left transition-colors"
                                :class="
                                    activeTab === 'dashboard'
                                        ? 'border-l-4 border-white bg-[#004080] font-medium text-white shadow-inner'
                                        : 'border-l-4 border-transparent text-blue-100 hover:bg-[#004080]'
                                "
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
                                        d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"
                                    />
                                </svg>
                                Home Dashboard
                            </button>
                        </li>
                        <li>
                            <button
                                @click="
                                    activeTab = 'medications';
                                    closeMobile();
                                "
                                class="flex w-full items-center justify-between px-6 py-3 text-left transition-colors"
                                :class="
                                    activeTab === 'medications'
                                        ? 'border-l-4 border-white bg-[#004080] font-medium text-white shadow-inner'
                                        : 'border-l-4 border-transparent text-blue-100 hover:bg-[#004080]'
                                "
                            >
                                <div class="flex items-center">
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
                                            d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"
                                        />
                                    </svg>
                                    My Medications
                                </div>
                                <span
                                    v-if="unreadCount > 0"
                                    class="flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-[10px] font-bold text-white"
                                >
                                    {{ unreadCount }}
                                </span>
                                <span
                                    v-else-if="pendingDosesCount > 0"
                                    class="flex h-5 w-5 items-center justify-center"
                                >
                                    <span class="relative flex h-3 w-3">
                                        <span
                                            class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"
                                        ></span>
                                        <span
                                            class="relative inline-flex h-3 w-3 rounded-full bg-emerald-500"
                                        ></span>
                                    </span>
                                </span>
                            </button>
                        </li>
                        <li>
                            <button
                                @click="
                                    activeTab = 'records';
                                    closeMobile();
                                "
                                class="flex w-full items-center px-6 py-3 text-left transition-colors"
                                :class="
                                    activeTab === 'records'
                                        ? 'border-l-4 border-white bg-[#004080] font-medium text-white shadow-inner'
                                        : 'border-l-4 border-transparent text-blue-100 hover:bg-[#004080]'
                                "
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
                                Medical Records
                            </button>
                        </li>
                    </ul>
                </nav>

                <div class="space-y-1 border-t border-[#004080] p-4">
                    <Link
                        href="/profile"
                        class="flex w-full items-center rounded-lg px-4 py-2 text-sm font-medium text-blue-200 transition-colors hover:bg-[#004080] hover:text-white"
                        @click="closeMobile"
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
                            class="flex w-full items-center rounded-lg px-4 py-2 text-sm font-medium text-blue-200 transition-colors hover:bg-[#004080] hover:text-white"
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
            </div>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="relative flex h-screen flex-1 flex-col overflow-hidden">
            <div
                class="z-10 flex h-16 shrink-0 items-center gap-3 border-b border-gray-200 bg-white px-4 shadow-sm"
            >
                <button
                    v-if="!isSidebarOpen"
                    type="button"
                    class="inline-flex items-center justify-center rounded-md p-2 text-gray-600 transition-colors hover:bg-gray-100 hover:text-gray-900"
                    @click="toggle"
                    aria-label="Open sidebar"
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
                            d="M4 6h16M4 12h16M4 18h16"
                        />
                    </svg>
                </button>
                <div class="flex items-center gap-2">
                    <div
                        class="rounded-lg bg-[#003366] p-1.5 text-sm font-bold text-white"
                    >
                        ♥
                    </div>
                    <span class="text-sm font-bold text-[#003366]"
                        >CardioCare - Patient</span
                    >
                </div>
            </div>
            <!-- Toast Notification -->
            <div
                v-if="showToast"
                class="animate-fade-in-up absolute right-8 bottom-8 z-50"
            >
                <div
                    class="flex items-start gap-4 rounded-xl border-l-4 bg-white p-5 shadow-2xl"
                    :class="
                        toastType === 'reminder'
                            ? 'border-emerald-500'
                            : 'border-[#005bb5]'
                    "
                >
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full"
                        :class="
                            toastType === 'reminder'
                                ? 'bg-emerald-100'
                                : 'bg-blue-100'
                        "
                    >
                        <svg
                            v-if="toastType === 'reminder'"
                            class="h-5 w-5 text-emerald-600"
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
                        <svg
                            v-else
                            class="h-5 w-5 text-[#005bb5]"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"
                            />
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-gray-900">
                            {{ toastTitle }}
                        </h4>
                        <p class="mt-1 text-xs text-gray-600">
                            {{ toastMessage }}
                        </p>
                        <button
                            @click="viewMedications"
                            class="mt-2 text-xs font-bold text-[#005bb5] hover:underline"
                        >
                            View Medications
                        </button>
                    </div>
                    <button
                        @click="showToast = false"
                        class="text-gray-400 hover:text-gray-600"
                    >
                        ✕
                    </button>
                </div>
            </div>

            <!-- Top Navbar -->
            <header
                class="flex h-20 shrink-0 items-center justify-between border-b border-gray-200 bg-white px-8 shadow-sm"
            >
                <h2 class="text-xl font-bold text-gray-800 capitalize">
                    {{ activeTab.replace('-', ' ') }}
                </h2>

                <div class="flex items-center gap-6">
                    <div class="relative">
                        <!-- Backdrop: click anywhere outside to close -->
                        <div
                            v-if="showNotifications"
                            class="fixed inset-0 z-40"
                            @click="showNotifications = false"
                        ></div>
                        <button
                            @click="toggleNotifications"
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
                                ></path>
                            </svg>
                            <span
                                v-if="unreadCount > 0"
                                class="absolute top-1.5 right-1.5 flex h-3 w-3 items-center justify-center rounded-full bg-red-500 ring-2 ring-white"
                            ></span>
                        </button>

                        <div
                            v-if="showNotifications"
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
                                    >{{ unreadCount }} New</span
                                >
                            </div>
                            <div class="max-h-72 overflow-y-auto">
                                <div
                                    v-if="notifications.length === 0"
                                    class="p-6 text-center text-sm text-gray-500"
                                >
                                    No new notifications.
                                </div>
                                <div
                                    v-for="notif in notifications"
                                    :key="notif.id"
                                    @click="viewMedications"
                                    class="cursor-pointer border-b border-gray-50 p-4 transition-colors hover:bg-blue-50/50"
                                >
                                    <p
                                        class="flex justify-between text-sm font-bold text-gray-800"
                                    >
                                        {{ notif.title }}
                                        <span
                                            v-if="!notif.read"
                                            class="h-2 w-2 rounded-full bg-red-500"
                                        ></span>
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
                                Patient ID: PT-{{
                                    String(user.id).padStart(4, '0')
                                }}
                            </p>
                        </div>
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-full bg-[#005bb5] font-bold text-white shadow-sm"
                        >
                            {{ user.name.charAt(0) }}
                        </div>
                    </div>
                </div>
            </header>

            <div class="relative flex-1 overflow-y-auto p-8">
                <div class="mx-auto max-w-5xl">
                    <!-- ============================================== -->
                    <!-- TAB 1: DASHBOARD                               -->
                    <!-- ============================================== -->
                    <div
                        v-if="activeTab === 'dashboard'"
                        class="animate-fade-in-up space-y-8"
                    >
                        <div
                            class="relative overflow-hidden rounded-2xl bg-linear-to-r from-[#005bb5] to-[#00abce] p-8 text-white shadow-lg"
                        >
                            <div class="relative z-10">
                                <h2
                                    class="mb-2 text-3xl font-black tracking-tight"
                                >
                                    Welcome Back, {{ user.name.split(' ')[0] }}.
                                </h2>
                                <p class="font-medium opacity-90">
                                    Manage your cardiovascular health and
                                    appointments securely.
                                </p>
                            </div>
                            <div
                                class="pointer-events-none absolute top-0 right-0 translate-x-4 -translate-y-8 transform text-9xl opacity-20"
                            >
                                ♥
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-8 md:grid-cols-2">
                            <!-- Next Appointment Card -->
                            <div
                                class="relative flex flex-col justify-between overflow-hidden rounded-2xl border border-gray-200 bg-white p-8 shadow-sm"
                            >
                                <div>
                                    <h3
                                        class="mb-6 flex items-center border-b border-gray-100 pb-3 text-xs font-extrabold tracking-widest text-gray-500 uppercase"
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
                                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                                            />
                                        </svg>
                                        Your Next Appointment
                                    </h3>

                                    <div
                                        v-if="nextAppointment"
                                        class="mb-6 flex items-start gap-5"
                                    >
                                        <div
                                            class="min-w-20 rounded-xl border border-blue-100 bg-blue-50 p-4 text-center"
                                        >
                                            <div
                                                class="text-xs font-black text-blue-600 uppercase"
                                            >
                                                {{
                                                    formatMonth(
                                                        nextAppointment.date,
                                                    )
                                                }}
                                            </div>
                                            <div
                                                class="text-3xl font-black text-[#003366]"
                                            >
                                                {{
                                                    formatDay(
                                                        nextAppointment.date,
                                                    )
                                                }}
                                            </div>
                                        </div>
                                        <div>
                                            <h4
                                                class="text-xl font-black text-gray-900"
                                            >
                                                {{
                                                    nextAppointment.service_type
                                                }}
                                            </h4>
                                            <p
                                                class="mt-1 font-medium text-gray-600"
                                            >
                                                {{
                                                    formatTime(
                                                        nextAppointment.time,
                                                    )
                                                }}
                                            </p>
                                            <p
                                                class="mt-1 text-sm text-gray-500"
                                            >
                                                Dr.
                                                {{
                                                    nextAppointment.doctor?.name
                                                        ? nextAppointment.doctor.name.replace(
                                                              'Dr. ',
                                                              '',
                                                          )
                                                        : 'Pending Assignment'
                                                }}
                                            </p>
                                            <span
                                                class="mt-3 inline-block rounded border px-3 py-1 text-xs font-bold tracking-wider uppercase"
                                                :class="{
                                                    'border-amber-200 bg-amber-50 text-amber-700':
                                                        nextAppointment.status ===
                                                        'Reschedule Requested',
                                                    'border-blue-200 bg-blue-100 text-blue-800':
                                                        ![
                                                            'Reschedule Requested',
                                                        ].includes(
                                                            nextAppointment.status,
                                                        ),
                                                }"
                                            >
                                                {{ nextAppointment.status }}
                                            </span>

                                            <p
                                                v-if="
                                                    nextAppointment.status ===
                                                    'Reschedule Requested'
                                                "
                                                class="mt-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-700"
                                            >
                                                ⏳ Your reschedule request is
                                                pending receptionist approval.
                                            </p>

                                            <!-- Google Calendar link -->
                                        </div>
                                    </div>

                                    <div
                                        v-else
                                        class="rounded-xl border border-dashed border-gray-200 bg-gray-50 py-10 text-center text-gray-400"
                                    >
                                        <svg
                                            class="mx-auto mb-3 h-12 w-12 opacity-50"
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
                                        <p class="font-bold text-gray-600">
                                            No upcoming appointments.
                                        </p>
                                        <p class="mt-1 text-sm">
                                            Book a new consultation to get
                                            started.
                                        </p>
                                    </div>
                                </div>

                                <div
                                    v-if="nextAppointment"
                                    class="mt-4 flex gap-3"
                                >
                                    <button
                                        v-if="
                                            nextAppointment.status !==
                                            'Reschedule Requested'
                                        "
                                        @click="isRescheduleOpen = true"
                                        class="flex-1 rounded-xl border-2 border-[#005bb5] py-3 text-sm font-bold text-[#005bb5] transition duration-200 hover:bg-blue-50"
                                    >
                                        Request Reschedule
                                    </button>
                                    <button
                                        @click="cancelAppointment"
                                        :disabled="cancelForm.processing"
                                        class="flex-1 rounded-xl border-2 border-red-400 py-3 text-sm font-bold text-red-500 transition duration-200 hover:bg-red-50 disabled:opacity-50"
                                    >
                                        Cancel Appointment
                                    </button>
                                </div>

                                <div
                                    v-if="props.calendarDownloadUrl"
                                    class="mt-4 rounded-2xl border border-blue-100 bg-blue-50/60 p-5"
                                >
                                    <div class="flex items-start gap-3">
                                        <svg
                                            class="mt-0.5 h-5 w-5 shrink-0 text-[#005bb5]"
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
                                        <div class="flex-1">
                                            <p
                                                class="text-sm font-black text-[#003366]"
                                            >
                                                Sync to your calendar
                                            </p>
                                            <p
                                                class="mt-0.5 text-xs text-gray-600"
                                            >
                                                Add your appointments and
                                                medication reminders to any
                                                calendar app (.ics).
                                            </p>
                                            <div
                                                class="mt-3 flex flex-wrap gap-2"
                                            >
                                                <button
                                                    type="button"
                                                    @click="downloadCalendar"
                                                    class="rounded-lg bg-[#005bb5] px-4 py-2 text-xs font-bold text-white transition duration-200 hover:bg-[#003366]"
                                                >
                                                    Download .ics
                                                </button>
                                                <button
                                                    type="button"
                                                    @click="copyCalendarFeed"
                                                    class="rounded-lg border border-[#005bb5] px-4 py-2 text-xs font-bold text-[#005bb5] transition duration-200 hover:bg-white"
                                                >
                                                    {{
                                                        calendarLinkCopied
                                                            ? 'Link copied!'
                                                            : 'Copy subscribe link'
                                                    }}
                                                </button>
                                            </div>
                                            <p
                                                v-if="calendarLinkCopied"
                                                class="mt-2 text-xs font-semibold text-green-600"
                                            >
                                                Paste the link into your
                                                calendar's "Subscribe by URL"
                                                option for automatic updates.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div
                                v-if="hasActiveAppointment"
                                class="relative rounded-2xl border border-gray-200 bg-gray-50 p-8 shadow-sm"
                            >
                                <div class="text-center">
                                    <svg
                                        class="mx-auto h-12 w-12 text-gray-300"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="1.5"
                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                                        />
                                    </svg>
                                    <p class="mt-3 font-bold text-gray-600">
                                        You already have an upcoming appointment
                                    </p>
                                    <p class="mt-1 text-sm text-gray-500">
                                        Use the options above to reschedule or
                                        cancel your current appointment before
                                        booking a new one.
                                    </p>
                                </div>
                            </div>

                            <!-- Smart Booking Form -->
                            <div
                                v-if="!hasActiveAppointment"
                                class="relative rounded-2xl border border-gray-200 bg-white p-8 shadow-sm"
                            >
                                <h3
                                    class="mb-6 border-b border-gray-100 pb-3 text-lg font-black text-gray-800"
                                >
                                    Book New Appointment
                                </h3>

                                <form
                                    @submit.prevent="submitBooking"
                                    class="space-y-5"
                                >
                                    <div>
                                        <label
                                            class="mb-1.5 block text-xs font-extrabold tracking-widest text-gray-500 uppercase"
                                            >Select Date</label
                                        >
                                        <input
                                            type="date"
                                            v-model="bookingForm.date"
                                            :min="minDate"
                                            class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 font-medium text-gray-900 outline-none focus:ring-2 focus:ring-[#005bb5]"
                                            required
                                        />
                                        <!-- Error message if user selects an unavailable day -->
                                        <p
                                            v-if="dateError"
                                            class="mt-2 text-xs font-bold text-red-600"
                                        >
                                            {{ dateError }}
                                        </p>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label
                                                class="mb-1.5 block text-xs font-extrabold tracking-widest text-gray-500 uppercase"
                                                >Time</label
                                            >
                                            <select
                                                v-model="bookingForm.time"
                                                class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 font-medium text-gray-900 outline-none focus:ring-2 focus:ring-[#005bb5]"
                                                :disabled="
                                                    !bookingForm.date ||
                                                    availableTimeSlots.length ===
                                                        0
                                                "
                                                required
                                            >
                                                <option
                                                    value=""
                                                    disabled
                                                    class="text-gray-500"
                                                >
                                                    {{
                                                        !bookingForm.date
                                                            ? 'Pick a date first'
                                                            : slotsLoading
                                                              ? 'Loading slots...'
                                                              : availableTimeSlots.length ===
                                                                  0
                                                                ? 'No slots available'
                                                                : 'Select Time'
                                                    }}
                                                </option>
                                                <option
                                                    v-for="slot in availableTimeSlots"
                                                    :key="slot.value"
                                                    :value="slot.value"
                                                    class="text-gray-900"
                                                >
                                                    {{ slot.label }}
                                                </option>
                                            </select>
                                        </div>
                                        <div>
                                            <label
                                                class="mb-1.5 block text-xs font-extrabold tracking-widest text-gray-500 uppercase"
                                                >Service</label
                                            >
                                            <select
                                                v-model="bookingForm.service"
                                                class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 font-medium text-gray-900 outline-none focus:ring-2 focus:ring-[#005bb5]"
                                            >
                                                <option
                                                    value="Consultation"
                                                    class="text-gray-900"
                                                >
                                                    Consultation
                                                </option>
                                                <option
                                                    value="Procedure"
                                                    class="text-gray-900"
                                                >
                                                    Procedure
                                                </option>
                                                <option
                                                    value="Consultation and Procedure"
                                                    class="text-gray-900"
                                                >
                                                    Consultation and Procedure
                                                </option>
                                            </select>
                                        </div>
                                    </div>

                                    <button
                                        type="submit"
                                        :disabled="
                                            bookingForm.processing ||
                                            availableTimeSlots.length === 0
                                        "
                                        class="mt-2 w-full rounded-xl bg-[#005bb5] py-3.5 font-bold text-white shadow-md transition-all hover:bg-blue-800 disabled:opacity-50"
                                    >
                                        {{
                                            bookingForm.processing
                                                ? 'Securing Slot...'
                                                : 'Confirm Booking'
                                        }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- ============================================== -->
                    <!-- TAB 2: MEDICATIONS                             -->
                    <!-- ============================================== -->
                    <div
                        v-if="activeTab === 'medications'"
                        class="animate-fade-in-up space-y-6"
                    >
                        <!-- ── TODAY'S MEDICATION REMINDER ───────────── -->
                        <div
                            class="rounded-2xl border border-emerald-200 bg-white p-6 shadow-sm"
                        >
                            <h3
                                class="mb-4 flex items-center border-b border-emerald-100 pb-3 text-lg font-black text-emerald-800"
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
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                    />
                                </svg>
                                Today's Medication Tracker
                                <span
                                    class="ml-auto text-xs font-medium text-emerald-600"
                                >
                                    {{
                                        new Date().toLocaleDateString('en-US', {
                                            weekday: 'long',
                                            month: 'short',
                                            day: 'numeric',
                                        })
                                    }}
                                </span>
                            </h3>

                            <div
                                v-if="todayMeds.length === 0"
                                class="py-4 text-center text-sm text-gray-400"
                            >
                                No medications scheduled for today.
                            </div>

                            <div v-else class="space-y-3">
                                <div
                                    v-for="(med, idx) in todayMeds"
                                    :key="idx"
                                    class="rounded-xl border p-4 transition-all"
                                    :class="
                                        med.all_taken
                                            ? 'border-emerald-200 bg-emerald-50'
                                            : 'border-amber-200 bg-amber-50'
                                    "
                                >
                                    <div
                                        class="mb-3 flex items-center justify-between gap-3"
                                    >
                                        <div class="flex-1">
                                            <p
                                                class="font-bold text-gray-900 capitalize"
                                            >
                                                {{
                                                    displayMedicineName(
                                                        med.medicine,
                                                    )
                                                }}
                                            </p>
                                            <p class="text-xs text-gray-500">
                                                {{ med.dosage }} &middot;
                                                {{ med.frequency }}
                                                <span
                                                    class="ml-2 font-semibold"
                                                    :class="
                                                        med.all_taken
                                                            ? 'text-emerald-600'
                                                            : 'text-amber-600'
                                                    "
                                                >
                                                    {{ med.taken_doses }}/{{
                                                        med.expected_doses
                                                    }}
                                                    taken today
                                                </span>
                                            </p>
                                        </div>
                                        <div
                                            v-if="med.all_taken"
                                            class="flex items-center gap-1.5 rounded-lg bg-emerald-100 px-3 py-1.5 text-xs font-bold text-emerald-700"
                                        >
                                            All Doses Taken
                                        </div>
                                    </div>

                                    <div
                                        v-if="med.cooldown?.active"
                                        class="mb-3 rounded-lg border border-orange-200 bg-orange-50 px-3 py-2 text-xs font-semibold text-orange-700"
                                    >
                                        Safety cooldown in effect — you can
                                        record the next intake at
                                        {{
                                            formatDateTime(
                                                med.cooldown?.next_available_at,
                                            )
                                        }}.
                                    </div>

                                    <div
                                        class="flex flex-wrap items-center gap-2"
                                    >
                                        <div
                                            v-for="d in med.doses ?? []"
                                            :key="d.scheduled_time"
                                            class="flex items-center rounded-lg border px-3 py-2 text-sm"
                                            :class="doseChipClass(d)"
                                        >
                                            <span
                                                class="font-mono text-xs font-bold"
                                                >{{ d.scheduled_time }}</span
                                            >
                                            <span
                                                class="text-[10px] opacity-75"
                                            >
                                                {{
                                                    d.status === 'TAKEN'
                                                        ? d.adherence_flag ===
                                                          'LATE'
                                                            ? ' · Taken late'
                                                            : ' · Taken'
                                                        : d.status === 'MISSED'
                                                          ? ' · Missed — locked'
                                                          : d.blocked_by
                                                            ? doseBlockedLabel(
                                                                  d,
                                                                  med,
                                                              )
                                                            : ' · window'
                                                }}
                                            </span>
                                            <button
                                                v-if="
                                                    d.status === 'PENDING' &&
                                                    d.can_take
                                                "
                                                @click="
                                                    logDose(
                                                        med.appointment_id,
                                                        med.prescription_index,
                                                        med.medicine,
                                                        d.scheduled_time,
                                                        d.zone,
                                                    )
                                                "
                                                :disabled="
                                                    doseLoading[
                                                        `${med.appointment_id}-${med.prescription_index}`
                                                    ]
                                                "
                                                class="ml-2 flex items-center gap-1 rounded-md px-2.5 py-1 text-xs font-bold text-white shadow-sm transition-all disabled:opacity-50"
                                                :class="
                                                    d.zone === 'AMBER'
                                                        ? 'bg-amber-500 hover:bg-amber-600'
                                                        : 'bg-emerald-600 hover:bg-emerald-700'
                                                "
                                            >
                                                <svg
                                                    class="h-3 w-3"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M5 13l4 4L19 7"
                                                    />
                                                </svg>
                                                {{
                                                    d.zone === 'AMBER'
                                                        ? 'Record Intake (Late)'
                                                        : 'Record Intake'
                                                }}
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div
                                    class="rounded-lg border border-sky-100 bg-sky-50 p-3 text-[11px] leading-relaxed text-sky-800"
                                >
                                    <strong class="font-bold">
                                        Safe-Harbor Notice:
                                    </strong>
                                    This medication-intake journal is an
                                    informational adherence aid and is not a
                                    substitute for your cardiologist's
                                    instructions. Always follow your
                                    prescription exactly as written. If you miss
                                    a dose or feel unwell, contact your care
                                    team before making any adjustments to your
                                    medication.
                                </div>
                            </div>
                        </div>

                        <!-- ── TODAY'S SCHEDULE BY TIME OF DAY ───────── -->
                        <div
                            v-if="dayParts.length > 0"
                            class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm"
                        >
                            <h3
                                class="mb-4 flex items-center border-b border-gray-100 pb-3 text-lg font-black text-gray-800"
                            >
                                Today&rsquo;s Schedule
                            </h3>
                            <div
                                class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4"
                            >
                                <div
                                    v-for="part in dayParts"
                                    :key="part.key"
                                    class="rounded-xl border border-slate-200"
                                >
                                    <div
                                        class="rounded-t-xl bg-slate-50 px-3 py-2"
                                    >
                                        <p
                                            class="text-xs font-extrabold tracking-wide text-slate-600 uppercase"
                                        >
                                            {{ part.label }}
                                        </p>
                                        <p class="text-[10px] text-slate-400">
                                            {{ part.range }}
                                        </p>
                                    </div>
                                    <div class="space-y-1.5 p-2">
                                        <button
                                            v-for="{ med, dose } in part.doses"
                                            :key="`${med.appointment_id}-${med.prescription_index}-${dose.scheduled_time}`"
                                            type="button"
                                            class="flex w-full items-center justify-between gap-2 rounded-lg border px-2.5 py-1.5 text-left text-xs transition-colors"
                                            :class="doseChipClass(dose)"
                                            :disabled="!dose.can_take"
                                            @click="
                                                dose.can_take
                                                    ? logDose(
                                                          med.appointment_id,
                                                          med.prescription_index,
                                                          med.medicine,
                                                          dose.scheduled_time,
                                                          dose.zone,
                                                      )
                                                    : undefined
                                            "
                                        >
                                            <span
                                                class="font-bold capitalize"
                                                >{{
                                                    displayMedicineName(
                                                        med.medicine,
                                                    )
                                                }}</span
                                            >
                                            <span
                                                class="font-mono font-extrabold"
                                                >{{ dose.scheduled_time }}</span
                                            >
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ── ADHERENCE CHART ──────────────────────── -->
                        <div
                            v-if="adherenceData.length > 0"
                            class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm"
                        >
                            <h3
                                class="mb-4 flex items-center border-b border-gray-100 pb-3 text-lg font-black text-gray-800"
                            >
                                <svg
                                    class="mr-2 h-5 w-5 text-[#005bb5]"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                                    />
                                </svg>
                                Adherence Overview (Last 7 Days)
                            </h3>

                            <div
                                v-if="adherenceTrend"
                                class="mb-4 flex items-center gap-4 rounded-xl bg-slate-50 px-4 py-3"
                            >
                                <div>
                                    <p
                                        class="text-2xl leading-none font-black"
                                        :class="
                                            adherenceTrend.average >= 80
                                                ? 'text-emerald-600'
                                                : adherenceTrend.average >= 50
                                                  ? 'text-amber-600'
                                                  : 'text-red-600'
                                        "
                                    >
                                        {{ adherenceTrend.average }}%
                                    </p>
                                    <p
                                        class="text-[11px] font-semibold tracking-wide text-slate-500"
                                    >
                                        OVERALL 7-DAY
                                    </p>
                                </div>
                                <div class="h-10 w-px bg-slate-200"></div>
                                <p
                                    class="flex items-center gap-1 text-sm font-extrabold"
                                    :class="
                                        adherenceTrend.direction === 'up'
                                            ? 'text-emerald-600'
                                            : adherenceTrend.direction ===
                                                'down'
                                              ? 'text-red-600'
                                              : 'text-slate-500'
                                    "
                                >
                                    <span
                                        v-if="adherenceTrend.direction === 'up'"
                                        >&uarr;</span
                                    >
                                    <span
                                        v-else-if="
                                            adherenceTrend.direction === 'down'
                                        "
                                        >&darr;</span
                                    >
                                    <span v-else>&rarr;</span>
                                    <span>
                                        {{
                                            adherenceTrend.direction === 'up'
                                                ? `Improving (+${adherenceTrend.delta}%)`
                                                : adherenceTrend.direction ===
                                                    'down'
                                                  ? `Declining (${adherenceTrend.delta}%)`
                                                  : 'Steady'
                                        }}
                                    </span>
                                </p>
                            </div>

                            <div
                                v-for="(med, idx) in adherenceData"
                                :key="idx"
                                class="mb-5 last:mb-0"
                            >
                                <div
                                    class="mb-1.5 flex items-center justify-between"
                                >
                                    <span
                                        class="text-sm font-bold text-gray-700 capitalize"
                                        >{{ med.medicine }}</span
                                    >
                                    <div class="flex items-center gap-2">
                                        <span class="text-[10px] text-gray-400">
                                            {{
                                                med.last7Days.filter(
                                                    (d) => d.pct < 80,
                                                ).length
                                            }}/7 days below target
                                        </span>
                                        <span
                                            class="text-xs font-semibold"
                                            :class="
                                                med.average >= 80
                                                    ? 'text-emerald-600'
                                                    : med.average >= 50
                                                      ? 'text-amber-600'
                                                      : 'text-red-600'
                                            "
                                        >
                                            {{ med.average }}%
                                        </span>
                                    </div>
                                </div>
                                <div class="flex h-16 items-end gap-1">
                                    <div
                                        v-for="(day, di) in med.last7Days"
                                        :key="di"
                                        class="flex h-full flex-1 cursor-pointer flex-col items-center justify-end"
                                        @click="toggleDayDose(day)"
                                    >
                                        <div class="relative w-full">
                                            <div
                                                class="w-full rounded-t transition-all"
                                                :style="{
                                                    height: day.pct + '%',
                                                }"
                                                :class="
                                                    day.pct >= 80
                                                        ? 'bg-emerald-500'
                                                        : day.pct >= 50
                                                          ? 'bg-amber-400'
                                                          : 'bg-red-400'
                                                "
                                                :title="`${day.date}: ${day.taken}/${day.expected} doses (${day.pct}%)`"
                                            ></div>
                                            <div
                                                v-if="day.taken === 0"
                                                class="absolute inset-0 flex items-start justify-center pt-0.5"
                                            >
                                                <span
                                                    class="text-[10px] font-bold text-red-600 opacity-70"
                                                    >✕</span
                                                >
                                            </div>
                                        </div>
                                        <span
                                            class="mt-1 text-[9px] text-gray-400"
                                            >{{ day.label }}</span
                                        >
                                        <span
                                            class="text-[8px] font-medium"
                                            :class="
                                                day.pct >= 80
                                                    ? 'text-emerald-600'
                                                    : day.pct >= 50
                                                      ? 'text-amber-600'
                                                      : 'text-red-400'
                                            "
                                            >{{ day.taken }}/{{
                                                day.expected
                                            }}</span
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ── MEDICATION HISTORY ─────────────────────── -->
                        <div
                            class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm"
                        >
                            <h3
                                class="mb-4 flex items-center border-b border-gray-100 pb-3 text-lg font-black text-gray-800"
                            >
                                <svg
                                    class="mr-2 h-5 w-5 text-gray-500"
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
                                Dose History (Last 30 Days)
                            </h3>

                            <div
                                v-if="medHistoryLoading"
                                class="py-6 text-center text-sm text-gray-400"
                            >
                                Loading history...
                            </div>

                            <div
                                v-else-if="medHistory.length === 0"
                                class="py-8 text-center text-sm text-gray-400"
                            >
                                No doses logged yet. Start tracking by marking
                                medications as taken.
                            </div>

                            <div v-else class="space-y-1">
                                <div
                                    v-for="entry in medHistory"
                                    :key="entry.id"
                                    class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition-colors hover:bg-gray-50"
                                >
                                    <div
                                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-emerald-100"
                                    >
                                        <svg
                                            class="h-3.5 w-3.5 text-emerald-600"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M5 13l4 4L19 7"
                                            />
                                        </svg>
                                    </div>
                                    <span
                                        class="flex-1 truncate font-medium text-gray-800 capitalize"
                                    >
                                        {{ entry.medicine }}
                                    </span>
                                    <span
                                        class="text-xs whitespace-nowrap text-gray-500"
                                    >
                                        {{ entry.log_date }}
                                    </span>
                                    <span
                                        class="text-xs whitespace-nowrap text-gray-400"
                                    >
                                        {{ entry.taken_at }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ============================================== -->
                    <!-- TAB 3: MEDICAL RECORDS                         -->
                    <!-- ============================================== -->
                    <div
                        v-if="activeTab === 'records'"
                        class="animate-fade-in-up"
                    >
                        <div
                            class="rounded-2xl border border-gray-200 bg-white p-8 shadow-sm"
                        >
                            <h3
                                class="mb-6 border-b border-gray-100 pb-4 text-xl font-black text-gray-900"
                            >
                                Consultation History
                            </h3>

                            <div
                                v-if="history.length === 0"
                                class="py-12 text-center text-gray-400"
                            >
                                <p class="text-lg font-bold text-gray-500">
                                    No medical records found.
                                </p>
                            </div>

                            <div v-else class="space-y-6">
                                <div
                                    v-for="record in history"
                                    :key="record.id"
                                    class="rounded-xl border border-gray-200 bg-gray-50 p-6"
                                >
                                    <div
                                        class="mb-4 flex items-start justify-between border-b border-gray-200 pb-4"
                                    >
                                        <div>
                                            <h4
                                                class="text-lg font-black text-[#005bb5]"
                                            >
                                                {{
                                                    formatDateOnly(record.date)
                                                }}
                                            </h4>
                                            <p
                                                class="mt-1 text-sm font-bold text-gray-600"
                                            >
                                                {{ record.service_type }}
                                            </p>
                                        </div>
                                        <div class="text-right">
                                            <p
                                                class="text-sm font-bold text-gray-800"
                                            >
                                                Dr.
                                                {{
                                                    record.doctor?.name
                                                        ? record.doctor.name.replace(
                                                              'Dr. ',
                                                              '',
                                                          )
                                                        : 'Unknown'
                                                }}
                                            </p>
                                            <p
                                                class="text-xs font-bold text-gray-500 uppercase"
                                            >
                                                {{ record.doctor?.specialty }}
                                            </p>
                                        </div>
                                    </div>

                                    <div
                                        v-if="record.ai_summary"
                                        class="rounded-lg border border-purple-100 bg-purple-50 p-4"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- ============================================== -->
        <!-- RESCHEDULE MODAL                               -->
        <!-- ============================================== -->
        <div
            v-if="isRescheduleOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
        >
            <div
                class="animate-fade-in-up w-full max-w-md transform overflow-hidden rounded-2xl bg-white shadow-2xl transition-all"
            >
                <div
                    class="flex items-center justify-between bg-[#005bb5] px-6 py-4 text-white"
                >
                    <h3 class="text-lg font-bold">Reschedule Appointment</h3>
                    <button
                        @click="isRescheduleOpen = false"
                        class="text-blue-200 hover:text-white"
                    >
                        ✕
                    </button>
                </div>
                <div class="p-6">
                    <p class="mb-6 text-sm font-medium text-gray-600">
                        Please select a new date and time for your
                        <b class="text-gray-900">{{
                            nextAppointment?.service_type
                        }}</b
                        >.
                    </p>

                    <form @submit.prevent="submitReschedule">
                        <div class="mb-6 space-y-4">
                            <div>
                                <label
                                    class="mb-1.5 block text-xs font-extrabold tracking-widest text-gray-500 uppercase"
                                    >New Date</label
                                >
                                <input
                                    type="date"
                                    v-model="rescheduleForm.date"
                                    :min="minDate"
                                    class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 font-medium text-gray-900 focus:border-[#005bb5] focus:outline-none"
                                    required
                                />
                                <p
                                    v-if="rescheduleDateError"
                                    class="mt-2 text-xs font-bold text-red-600"
                                >
                                    {{ rescheduleDateError }}
                                </p>
                            </div>
                            <div>
                                <label
                                    class="mb-1.5 block text-xs font-extrabold tracking-widest text-gray-500 uppercase"
                                    >New Time</label
                                >
                                <select
                                    v-model="rescheduleForm.time"
                                    class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 font-medium text-gray-900 focus:border-[#005bb5] focus:outline-none"
                                    :disabled="
                                        !rescheduleForm.date ||
                                        rescheduleTimeSlots.length === 0
                                    "
                                    required
                                >
                                    <option
                                        value=""
                                        disabled
                                        class="text-gray-500"
                                    >
                                        {{
                                            !rescheduleForm.date
                                                ? 'Pick a date first'
                                                : slotsLoading
                                                  ? 'Loading slots...'
                                                  : rescheduleTimeSlots.length ===
                                                      0
                                                    ? 'No slots available'
                                                    : 'Select Time'
                                        }}
                                    </option>
                                    <option
                                        v-for="slot in rescheduleTimeSlots"
                                        :key="slot.value"
                                        :value="slot.value"
                                        class="text-gray-900"
                                    >
                                        {{ slot.label }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div
                            class="flex justify-end gap-3 border-t border-gray-100 pt-4"
                        >
                            <button
                                type="button"
                                @click="isRescheduleOpen = false"
                                class="px-5 py-2.5 text-sm font-bold text-gray-500 transition-colors hover:text-gray-800"
                            >
                                Keep Original Date
                            </button>
                            <button
                                type="submit"
                                :disabled="
                                    rescheduleForm.processing ||
                                    rescheduleTimeSlots.length === 0
                                "
                                class="rounded-xl bg-[#005bb5] px-5 py-2.5 text-sm font-bold text-white shadow-sm transition-all hover:bg-blue-800 disabled:opacity-50"
                            >
                                {{
                                    rescheduleForm.processing
                                        ? 'Submitting...'
                                        : 'Submit Request'
                                }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { useForm, Link, router } from '@inertiajs/vue3';
import LogoutButton from '@/components/LogoutButton.vue';
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import Toast from '@/components/Toast.vue';
import { useToast } from '@/composables/useToast';
import { useSidebar } from '@/composables/useSidebar';
import { getEcho } from '@/echo';
import {
    sanitizeText,
    checkMedicationInteractions,
    validatePrescription,
} from '@/utils/validation';

// Type definitions
interface Prescription {
    id?: string | number;
    medicine: string;
    dosage: string;
    frequency: string;
    duration: string | number;
    instructions?: string;
    startDate?: string;
    endDate?: string;
    prescribedBy?: string;
    prescribedAt?: string;
}

interface Doctor {
    id?: string | number;
    name: string;
    specialty?: string;
}

interface Appointment {
    id: string | number;
    patient_id: string | number;
    date: string;
    time: string;
    status: string;
    service: string;
    service_type?: string;
    doctor?: Doctor;
    prescriptions?: Prescription[];
    google_event_url?: string;
}

interface ConsultationRecord {
    id: string | number;
    date: string;
    service_type?: string;
    diagnosis?: string;
    ai_summary?: string;
    doctor?: Doctor;
    prescriptions: Prescription[];
}

interface Notification {
    id: string | number;
    title: string;
    message: string;
    time: string;
    read: boolean;
}

interface User {
    id: string | number;
    name: string;
    email: string;
}

interface TimeSlot {
    value: string;
    label: string;
}

interface TodayDose {
    scheduled_time: string;
    zone: string;
    status: string;
    adherence_flag: string | null;
    taken_at: string | null;
    can_take: boolean;
    blocked_by: string | null;
}

interface CooldownInfo {
    active: boolean;
    last_taken_at: string | null;
    next_available_at: string | null;
}

interface TodayMed {
    appointment_id: number;
    prescription_index: number;
    medicine: string;
    dosage: string;
    frequency: string;
    duration: string;
    expected_doses: number;
    taken_doses: number;
    all_taken: boolean;
    prescribed_at: string;
    doses: TodayDose[];
    cooldown: CooldownInfo | null;
}

interface AdherenceDay {
    date: string;
    taken: number;
    expected: number;
    label: string;
    pct: number;
    prescriptionIndex: number;
    appointmentId: number;
}

interface AdherenceMed {
    medicine: string;
    dosage: string;
    frequency: string;
    average: number;
    last7Days: AdherenceDay[];
}

const { success: showSuccess, error: showError } = useToast();
const {
    isOpen,
    isMobileOpen,
    isSidebarOpen,
    toggleDesktop,
    toggle,
    closeMobile,
} = useSidebar('patient');

const props = defineProps<{
    user: User;
    nextAppointment?: Appointment | null;
    history: ConsultationRecord[];
    calendarDownloadUrl?: string;
    calendarFeedUrl?: string;
}>();

const calendarLinkCopied = ref(false);

function downloadCalendar() {
    if (props.calendarDownloadUrl) {
        window.location.href = props.calendarDownloadUrl;
    }
}

function copyCalendarFeed() {
    if (!props.calendarFeedUrl) {
        return;
    }

    const webcal = props.calendarFeedUrl.replace(/^https?:\/\//, 'webcal://');
    navigator.clipboard?.writeText(webcal).then(() => {
        calendarLinkCopied.value = true;
        window.setTimeout(() => (calendarLinkCopied.value = false), 4000);
    });
}

// Tab Navigation State
const activeTab = ref<'dashboard' | 'medications' | 'records'>('dashboard');

// Medication Adherence State
interface MedHistoryEntry {
    id: number;
    medicine: string;
    log_date: string;
    taken_at: string;
    scheduled_time: string | null;
    appointment_date: string;
}

const todayMeds = ref<TodayMed[]>([]);
const adherenceData = ref<AdherenceMed[]>([]);
const doseLoading = ref<Record<string, boolean>>({});
const doseToggling = ref<Record<string, boolean>>({});
const pendingDosesCount = computed(
    () =>
        todayMeds.value.filter((m) => (m.doses ?? []).some((d) => d.can_take))
            .length,
);

// Today's doses grouped by part of day, so the patient can glance at the
// schedule instead of scanning each prescription row.
const dayParts = computed(() => {
    const buckets = [
        {
            key: 'morning',
            label: 'Morning',
            range: 'Before 12 PM',
            doses: [] as { med: TodayMed; dose: TodayDose }[],
        },
        {
            key: 'afternoon',
            label: 'Afternoon',
            range: '12 PM – 5 PM',
            doses: [] as { med: TodayMed; dose: TodayDose }[],
        },
        {
            key: 'evening',
            label: 'Evening',
            range: '5 PM – 9 PM',
            doses: [] as { med: TodayMed; dose: TodayDose }[],
        },
        {
            key: 'night',
            label: 'Night',
            range: '9 PM – 12 AM',
            doses: [] as { med: TodayMed; dose: TodayDose }[],
        },
    ];

    for (const med of todayMeds.value) {
        for (const dose of med.doses ?? []) {
            const hour = parseInt(
                (dose.scheduled_time ?? '').split(':')[0],
                10,
            );
            const idx = Number.isNaN(hour)
                ? 0
                : hour < 12
                  ? 0
                  : hour < 17
                    ? 1
                    : hour < 21
                      ? 2
                      : 3;
            buckets[idx].doses.push({ med, dose });
        }
    }

    for (const bucket of buckets) {
        bucket.doses.sort((a, b) =>
            a.dose.scheduled_time.localeCompare(b.dose.scheduled_time),
        );
    }

    return buckets.filter((b) => b.doses.length > 0);
});

// Overall 7-day adherence with a simple trend vs the start of the week.
const adherenceTrend = computed(() => {
    const meds = adherenceData.value;

    if (meds.length === 0) {
        return null;
    }

    const average = Math.round(
        meds.reduce((sum, m) => sum + m.average, 0) / meds.length,
    );

    let firstHalf = 0;
    let firstCount = 0;
    let secondHalf = 0;
    let secondCount = 0;

    for (const m of meds) {
        for (const day of m.last7Days.slice(0, 3)) {
            firstHalf += day.pct;
            firstCount++;
        }

        for (const day of m.last7Days.slice(-3)) {
            secondHalf += day.pct;
            secondCount++;
        }
    }

    const delta =
        (secondCount ? secondHalf / secondCount : 0) -
        (firstCount ? firstHalf / firstCount : 0);

    return {
        average,
        delta: Math.round(delta),
        direction: delta >= 8 ? 'up' : delta <= -8 ? 'down' : 'steady',
    };
});
const medHistory = ref<MedHistoryEntry[]>([]);
const medHistoryLoading = ref(false);

async function fetchMedHistory(): Promise<void> {
    medHistoryLoading.value = true;

    try {
        const res = await fetch('/patient/medications/history', {
            credentials: 'same-origin',
        });

        if (!res.ok) {
            medHistory.value = [];

            return;
        }

        medHistory.value = await res.json();
    } catch {
        medHistory.value = [];
    } finally {
        medHistoryLoading.value = false;
    }
}

async function fetchTodayMeds(showReminderToast = true): Promise<void> {
    try {
        const res = await fetch('/patient/medications/today', {
            credentials: 'same-origin',
        });

        if (!res.ok) {
            return;
        }

        todayMeds.value = await res.json();
        const pending = todayMeds.value.filter((m) =>
            (m.doses ?? []).some((d) => d.can_take),
        );

        if (showReminderToast && pending.length > 0) {
            toastType.value = 'reminder';
            toastTitle.value = 'Medication Reminder';
            toastMessage.value = `You have ${pending.length} medication${pending.length > 1 ? 's' : ''} to take today.`;
            showToast.value = true;
            setTimeout(() => {
                showToast.value = false;
            }, 10000);
        }
    } catch {
        /* silently fail */
    }
}

async function fetchAdherenceData(): Promise<void> {
    try {
        if (!props.history || props.history.length === 0) {
            return;
        }

        const firstWithMeds = props.history.find(
            (r) => r.prescriptions?.length,
        );

        if (!firstWithMeds) {
            return;
        }

        const res = await fetch(`/medications/${firstWithMeds.id}/adherence`, {
            credentials: 'same-origin',
        });

        if (!res.ok) {
            return;
        }

        const data = await res.json();

        if (!data.daily) {
            adherenceData.value = [];

            return;
        }

        const appointmentId = data.appointment_id;
        const last7 = data.daily.slice(-7);
        const medMap: Record<string, { taken: number; expected: number }[]> =
            {};

        for (const day of last7) {
            for (const med of day.medications) {
                if (!med.medicine) {
                    continue;
                }

                if (!medMap[med.medicine]) {
                    medMap[med.medicine] = [];
                }

                medMap[med.medicine].push({
                    taken: med.taken,
                    expected: med.expected,
                });
            }
        }

        adherenceData.value = Object.entries(medMap).map(([medicine, days]) => {
            const totalExpected = days.reduce((s, d) => s + d.expected, 0);
            const totalTaken = days.reduce((s, d) => s + d.taken, 0);
            const average =
                totalExpected > 0
                    ? Math.round((totalTaken / totalExpected) * 100)
                    : 0;
            const last7Days = last7.map((day: any) => {
                const m = day.medications.find(
                    (mm: any) => mm.medicine === medicine,
                );
                const expected = m?.expected ?? 0;
                const taken = m?.taken ?? 0;
                const date = new Date(day.date + 'T00:00:00');

                return {
                    date: day.date,
                    taken,
                    expected,
                    prescriptionIndex: m?.index ?? 0,
                    appointmentId,
                    label: date
                        .toLocaleDateString('en-US', { weekday: 'short' })
                        .slice(0, 2),
                    pct:
                        expected > 0 ? Math.round((taken / expected) * 100) : 0,
                };
            });

            return { medicine, dosage: '', frequency: '', average, last7Days };
        });
    } catch {
        adherenceData.value = [];
    }
}

function getCsrfToken(): string {
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);

    return match ? decodeURIComponent(match[1]) : '';
}

async function logDose(
    appointmentId: number,
    prescriptionIndex: number,
    medicine: string,
    scheduledTime: string,
    doseZone: string,
): Promise<void> {
    if (doseZone === 'AMBER') {
        const ok = window.confirm(
            'You are recording this dose late — the on-time window has closed. ' +
                'Please make sure you can still space it safely from your next scheduled intake ' +
                'before confirming.',
        );

        if (!ok) {
            return;
        }
    }

    const key = `${appointmentId}-${prescriptionIndex}`;
    doseLoading.value[key] = true;

    try {
        const csrfToken = getCsrfToken();
        const res = await fetch(`/medications/${appointmentId}/log-dose`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-XSRF-TOKEN': csrfToken,
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                prescription_index: prescriptionIndex,
                medicine,
                scheduled_time: scheduledTime,
            }),
        });

        const data = await res.json().catch(() => ({}));

        if (!res.ok) {
            if (res.status === 422 && data.error_code) {
                showError(data.error || 'Dose could not be recorded.');

                if (data.message) {
                    window.alert(data.message);
                }
            } else {
                showError(data.error || 'Failed to log dose.');
            }

            await fetchTodayMeds();

            return;
        }

        showSuccess(data.message || 'Dose logged! Great job staying on track.');
        await fetchTodayMeds();
    } catch {
        showError('Failed to log dose.');
    } finally {
        delete doseLoading.value[key];
    }
}

async function toggleDayDose(day: AdherenceDay): Promise<void> {
    const key = `${day.appointmentId}-${day.prescriptionIndex}-${day.date}`;
    doseToggling.value[key] = true;

    try {
        const csrfToken = getCsrfToken();
        const res = await fetch(
            `/medications/${day.appointmentId}/toggle-dose`,
            {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-XSRF-TOKEN': csrfToken,
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    prescription_index: day.prescriptionIndex,
                    medicine: '',
                    date: day.date,
                }),
            },
        );

        if (!res.ok) {
            const err = await res.json().catch(() => ({}));
            showError(err.error || 'Failed to toggle dose.');

            return;
        }

        await fetchAdherenceData();
        await fetchTodayMeds();
    } catch {
        showError('Failed to toggle dose.');
    } finally {
        delete doseToggling.value[key];
    }
}

// Date logic
const minDate = computed((): string => {
    const d = new Date();
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');

    return `${y}-${m}-${day}`;
});

// ----------------------------------------------------
// REALTIME SLOT AVAILABILITY (server-driven)
// ----------------------------------------------------
const availableTimeSlots = ref<TimeSlot[]>([]);
const rescheduleTimeSlots = ref<TimeSlot[]>([]);
const slotsLoading = ref(false);
const dateError = ref<string>('');
const rescheduleDateError = ref<string>('');

async function fetchSlots(date: string): Promise<TimeSlot[]> {
    if (!date) {
        return [];
    }

    try {
        const res = await fetch(
            `/patient/appointments/availability?date=${encodeURIComponent(date)}`,
            { credentials: 'same-origin' },
        );

        if (!res.ok) {
            return [];
        }

        const data = await res.json();

        return Array.isArray(data.slots) ? data.slots : [];
    } catch {
        return [];
    }
}

async function refreshBookingSlots(): Promise<void> {
    if (!bookingForm.date) {
        availableTimeSlots.value = [];
        dateError.value = '';

        return;
    }

    slotsLoading.value = true;

    try {
        const slots = await fetchSlots(bookingForm.date);
        availableTimeSlots.value = slots;

        if (slots.length === 0) {
            dateError.value =
                'No doctors are available on this day. Please select another date.';
            bookingForm.time = '';
        } else {
            dateError.value = '';

            if (!slots.some((s) => s.value === bookingForm.time)) {
                bookingForm.time = '';
            }
        }
    } finally {
        slotsLoading.value = false;
    }
}

async function refreshRescheduleSlots(): Promise<void> {
    if (!rescheduleForm.date) {
        rescheduleTimeSlots.value = [];
        rescheduleDateError.value = '';

        return;
    }

    slotsLoading.value = true;

    try {
        const slots = await fetchSlots(rescheduleForm.date);
        rescheduleTimeSlots.value = slots;

        if (slots.length === 0) {
            rescheduleDateError.value =
                'No doctors are available on this day. Please select another date.';
            rescheduleForm.time = '';
        } else {
            rescheduleDateError.value = '';

            if (!slots.some((s) => s.value === rescheduleForm.time)) {
                rescheduleForm.time = '';
            }
        }
    } finally {
        slotsLoading.value = false;
    }
}

// ----------------------------------------------------
// FORMS (MISSING DEFINITIONS FIXED)
// ----------------------------------------------------
const bookingForm = useForm({
    date: '',
    time: '',
    service: 'Consultation',
});

const isRescheduleOpen = ref<boolean>(false);

const hasActiveAppointment = computed(
    () =>
        props.nextAppointment &&
        !['Completed', 'Cancelled', 'No-Show'].includes(
            props.nextAppointment.status,
        ),
);

const rescheduleForm = useForm({
    date: '',
    time: '',
});

const cancelForm = useForm({});

// Watcher to show error if no doctors working on selected date
watch(
    () => bookingForm.date,
    (): void => {
        refreshBookingSlots();
    },
);

// Reschedule Watcher
watch(
    () => rescheduleForm.date,
    (): void => {
        refreshRescheduleSlots();
    },
);

// ── Realtime: refresh availability every 60s as the clock moves and as other
// patients book slots, so today's passed/full slots drop without a reload. ──
let slotsRefreshTimer: number | null = null;

const stopSlotsRefresh = (): void => {
    if (slotsRefreshTimer !== null) {
        window.clearInterval(slotsRefreshTimer);
        slotsRefreshTimer = null;
    }
};

const startSlotsRefresh = (): void => {
    stopSlotsRefresh();
    slotsRefreshTimer = window.setInterval(() => {
        if (bookingForm.date) {
            refreshBookingSlots();
        }

        if (rescheduleForm.date) {
            refreshRescheduleSlots();
        }
    }, 60_000);
};

onMounted(startSlotsRefresh);
onUnmounted(stopSlotsRefresh);

// ----------------------------------------------------
// MEDICATIONS & NOTIFICATIONS LOGIC
// ----------------------------------------------------

const activePrescriptions = computed((): Prescription[] => {
    if (!props.history || props.history.length === 0) {
        return [];
    }

    const recordWithMeds = props.history.find(
        (r: ConsultationRecord): boolean =>
            r.prescriptions &&
            Array.isArray(r.prescriptions) &&
            r.prescriptions.length > 0,
    );

    if (!recordWithMeds?.prescriptions) {
        return [];
    }

    // Validate and filter prescriptions
    return recordWithMeds.prescriptions.filter((p: Prescription): boolean => {
        if (!p) {
            return false;
        }

        // Validate all required fields exist and have correct types
        const validationErrors = validatePrescription(p);

        if (validationErrors.length > 0) {
            console.warn(
                `Invalid prescription skipped: ${validationErrors.map((e) => e.message).join(', ')}`,
            );

            return false;
        }

        return true;
    });
});

/**
 * Detects medication interaction warnings
 */
const medicationWarnings = computed((): string[] => {
    return checkMedicationInteractions(activePrescriptions.value);
});

const notifications = ref<Notification[]>([]);
const showNotifications = ref<boolean>(false);
const showToast = ref<boolean>(false);
const toastType = ref<'prescription' | 'reminder'>('prescription');
const toastTitle = ref('New Prescription Added');
const toastMessage = ref('Your doctor has updated your medications.');

const unreadCount = computed(
    (): number =>
        notifications.value.filter((n: Notification): boolean => !n.read)
            .length,
);

function buildNotifications(): Notification[] {
    const list: Notification[] = [];

    // --- Appointment-based notifications ---
    if (props.nextAppointment) {
        const todayStr = new Date().toISOString().split('T')[0];
        const tomorrowStr = new Date(Date.now() + 86400000)
            .toISOString()
            .split('T')[0];
        const apptDate =
            props.nextAppointment.date?.split('T')[0] ??
            props.nextAppointment.date;
        const docName =
            props.nextAppointment.doctor?.name?.replace('Dr. ', '') ||
            'your doctor';
        const apptTime = formatTime(props.nextAppointment.time);

        if (apptDate === todayStr) {
            list.push({
                id: 'appt-today',
                title: 'Appointment Today',
                message: `You have an appointment today at ${apptTime} with Dr. ${docName}.`,
                time: 'Today',
                read: false,
            });
        } else if (apptDate === tomorrowStr) {
            list.push({
                id: 'appt-tomorrow',
                title: 'Appointment Tomorrow',
                message: `Reminder: appointment tomorrow at ${apptTime} with Dr. ${docName}.`,
                time: 'Tomorrow',
                read: false,
            });
        }

        if (props.nextAppointment.status === 'Reschedule Requested') {
            list.push({
                id: 'reschedule-pending',
                title: 'Reschedule Pending',
                message:
                    'Your reschedule request is awaiting approval from the receptionist.',
                time: 'Pending',
                read: false,
            });
        }
    }

    // --- Prescription notifications ---
    const latestRecord = props.history?.[0];

    if (latestRecord?.prescriptions?.length) {
        list.push({
            id: latestRecord.id,
            title: 'New Prescription Added',
            message: `Dr. ${latestRecord.doctor?.name?.replace('Dr. ', '') || 'Your doctor'} prescribed ${latestRecord.prescriptions.length} new medication(s).`,
            time: formatDateOnly(latestRecord.date),
            read: false,
        });
    }

    return list;
}

function syncNotifications(announceNewPrescription = false): void {
    const previouslyRead = new Map<string, boolean>();
    const previousIds = new Set<string>();

    notifications.value.forEach((n: Notification): void => {
        previouslyRead.set(String(n.id), n.read);
        previousIds.add(String(n.id));
    });

    notifications.value = buildNotifications().map(
        (n: Notification): Notification => ({
            ...n,
            read: previouslyRead.get(String(n.id)) ?? false,
        }),
    );

    const latestRecord = props.history?.[0];

    if (
        announceNewPrescription &&
        latestRecord?.prescriptions?.length &&
        !previousIds.has(String(latestRecord.id)) &&
        !showToast.value
    ) {
        toastType.value = 'prescription';
        toastTitle.value = 'New Prescription Added';
        toastMessage.value = `Dr. ${latestRecord.doctor?.name?.replace('Dr. ', '') || 'Your doctor'} prescribed ${latestRecord.prescriptions.length} new medication(s).`;
        showToast.value = true;
        setTimeout(() => {
            showToast.value = false;
        }, 6000);
    }
}

onMounted(() => {
    syncNotifications(true);

    // --- Medication Adherence ---
    fetchTodayMeds();
    fetchAdherenceData();
    fetchMedHistory();
});

// Refresh today's meds when tab switches to medications
watch(activeTab, (tab) => {
    if (tab === 'medications') {
        fetchTodayMeds();
        fetchAdherenceData();
        fetchMedHistory();
    }
});

// ── Real-time consultation completion notifications ────────────────
let echoChannel: any = null;

onMounted(() => {
    const echo = getEcho();

    if (echo && props.user?.id) {
        echoChannel = echo.channel(`patient.${props.user.id}`);
        echoChannel.listen('ConsultationCompleted', () => {
            router.reload({
                only: ['nextAppointment', 'history'],
                onSuccess: () => {
                    syncNotifications(true);
                    fetchTodayMeds(false);
                    fetchAdherenceData();
                    fetchMedHistory();
                },
            });
        });

        echoChannel.listen('AppointmentRescheduled', (e: any) => {
            router.reload({ only: ['nextAppointment', 'history'] });
            showSuccess(
                `Your appointment has been rescheduled to ${e.newDate} at ${e.newTime}.`,
            );
        });
    }
});

onUnmounted(() => {
    if (echoChannel) {
        const echo = getEcho();

        if (echo) {
            echo.leave(`patient.${props.user.id}`);
        }

        echoChannel = null;
    }
});
// ────────────────────────────────────────────────────────────────────

/**
 * Audit logging: Log medication access for HIPAA compliance
 */
watch(activePrescriptions, (newPrescriptions: Prescription[]): void => {
    if (newPrescriptions && newPrescriptions.length > 0) {
        // Log medication viewing access
        router.post(
            '/audit/medication-viewed',
            {
                patientId: props.user?.id,
                medicationCount: newPrescriptions.length,
                medications: newPrescriptions.map((m: Prescription) => ({
                    id: m.id,
                    name: sanitizeText(m.medicine),
                })),
                timestamp: new Date().toISOString(),
                userAgent: navigator.userAgent,
            },
            {
                preserveScroll: true,
                onError: (errors: any): void => {
                    console.warn('Failed to log medication access:', errors);
                },
            },
        );
    }
});

const toggleNotifications = (): void => {
    showNotifications.value = !showNotifications.value;

    if (showNotifications.value) {
        // Bulletproof array reset to trigger Vue reactivity
        notifications.value = notifications.value.map(
            (n: Notification): Notification => ({
                ...n,
                read: true,
            }),
        );
    }
};

const viewMedications = (): void => {
    activeTab.value = 'medications';
    showToast.value = false;
    showNotifications.value = false;
    notifications.value = notifications.value.map(
        (n: Notification): Notification => ({
            ...n,
            read: true,
        }),
    );

    // Show interaction warnings if any detected
    if (medicationWarnings.value.length > 0) {
        medicationWarnings.value.forEach((warning: string) => {
            showError(warning);
        });
    }
};

/**
 * Safely display medication name with sanitization
 */
const displayMedicineName = (medicine: string | undefined): string => {
    if (!medicine || typeof medicine !== 'string') {
        return 'Unknown medication';
    }

    return sanitizeText(medicine);
};

/**
 * Safely display dosage with sanitization
 */
const displayDosage = (dosage: string | undefined): string => {
    if (!dosage || typeof dosage !== 'string') {
        return 'Not specified';
    }

    return sanitizeText(dosage);
};

// Formatting
const formatMonth = (dateString: string): string => {
    if (!dateString) {
        return '---';
    }

    return new Date(dateString).toLocaleString('default', { month: 'short' });
};
const formatDay = (dateString: string): string => {
    if (!dateString) {
        return '--';
    }

    return new Date(dateString).getDate().toString().padStart(2, '0');
};
const formatDateOnly = (dateString: string): string => {
    if (!dateString) {
        return '';
    }

    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const formatDate = (dateString: string): string => formatDateOnly(dateString);

const formatTime = (timeString: string): string => {
    if (!timeString) {
        return '--:--';
    }

    const [hourStr, minStr] = timeString.split(':');
    const date = new Date();
    date.setHours(parseInt(hourStr, 10), parseInt(minStr, 10));

    return date.toLocaleTimeString('en-US', {
        hour: 'numeric',
        minute: '2-digit',
        hour12: true,
    });
};

const formatDateTime = (iso: string | null | undefined): string => {
    if (!iso) {
        return '—';
    }

    return new Date(iso).toLocaleTimeString('en-US', {
        hour: 'numeric',
        minute: '2-digit',
        hour12: true,
    });
};

const doseChipClass = (dose: TodayDose): string => {
    if (dose.status === 'TAKEN') {
        return dose.adherence_flag === 'LATE'
            ? 'border-amber-300 bg-amber-50 text-amber-800'
            : 'border-emerald-300 bg-emerald-50 text-emerald-800';
    }

    if (dose.status === 'MISSED' || dose.zone === 'RED') {
        return 'border-red-200 bg-red-50 text-red-700';
    }

    if (dose.can_take) {
        return dose.zone === 'AMBER'
            ? 'border-amber-300 bg-amber-50 text-amber-800'
            : 'border-emerald-300 bg-emerald-50 text-emerald-800';
    }

    return 'border-gray-200 bg-gray-50 text-gray-500';
};

const doseBlockedLabel = (dose: TodayDose, med: TodayMed): string => {
    if (dose.blocked_by === 'COOLDOWN_ACTIVE') {
        return ` · Cooldown til ${formatDateTime(med.cooldown?.next_available_at)}`;
    }

    if (dose.blocked_by === 'WINDOW_NOT_OPEN') {
        return ' · Not due yet';
    }

    return ' · Locked';
};

// ----------------------------------------------------
// NEW APPOINTMENT BOOKING
// ----------------------------------------------------
const submitBooking = (): void => {
    bookingForm.post('/patient/appointments', {
        preserveScroll: true,
        onError: (errors: Record<string, string>): void => {
            console.error('Booking Validation Errors:', errors);
            const errorMessages = Object.values(errors).join('\n');
            showError(`Booking Failed:\n${errorMessages}`);
        },
        onSuccess: (): void => {
            showSuccess('Appointment securely booked!');
            bookingForm.reset();
        },
        onFinish: (): void => {
            bookingForm.processing = false;
        },
    });
};

// ----------------------------------------------------
// CANCEL LOGIC
// ----------------------------------------------------
const cancelAppointment = (): void => {
    if (
        !confirm(
            'Are you sure you want to cancel this appointment? This cannot be undone.',
        )
    ) {
        return;
    }

    cancelForm.post(
        `/patient/appointments/${props.nextAppointment?.id}/cancel`,
        {
            preserveScroll: true,
            onSuccess: (): void => {
                showSuccess('Your appointment has been cancelled.');
            },
            onError: (errors: Record<string, string>): void => {
                console.error('Cancel Errors:', errors);
                const errorMsg =
                    errors.cancel || 'Failed to cancel appointment';
                showError(errorMsg);
            },
        },
    );
};

// ----------------------------------------------------
// RESCHEDULE LOGIC
// ----------------------------------------------------
const submitReschedule = (): void => {
    rescheduleForm.post(
        `/patient/appointments/${props.nextAppointment?.id}/request-reschedule`,
        {
            preserveScroll: true,
            onError: (errors: Record<string, string>): void => {
                console.error('Reschedule Validation Errors:', errors);
                const errorMessages = Object.values(errors).join('\n');
                showError(`Reschedule Failed:\n${errorMessages}`);
            },
            onSuccess: (): void => {
                showSuccess(
                    'Reschedule request submitted! The receptionist will review and confirm availability.',
                );
                isRescheduleOpen.value = false;
                rescheduleForm.reset();
            },
            onFinish: (): void => {
                rescheduleForm.processing = false;
            },
        },
    );
};
</script>

<style scoped>
input,
select,
textarea,
option {
    color: #111827 !important; /* text-gray-900 */
}

.animate-fade-in-up {
    animation: fadeInUp 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(15px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
