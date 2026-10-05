<template>
    <div
        class="flex h-screen bg-[#f4f7fa] font-sans selection:bg-[#15818c] selection:text-white"
    >
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
                <h1 class="text-lg leading-tight font-bold">CardioCare</h1>
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
                <Link
                    href="/logout"
                    method="post"
                    as="button"
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
                </Link>
            </div>
        </aside>

        <!-- MAIN -->
        <main class="flex h-screen flex-1 flex-col overflow-hidden">
            <header
                class="z-10 flex h-20 shrink-0 items-center justify-between border-b border-gray-200 bg-white px-8 shadow-sm"
            >
                <h2 class="text-xl font-bold text-gray-800">
                    Monthly Narrative Report
                </h2>
                <div class="flex items-center gap-3">
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
            </header>

            <div class="flex-1 overflow-y-auto p-8">
                <!-- ─── CONTROLS ─────────────────────────────────────────────── -->
                <div
                    class="mx-auto mb-8 max-w-7xl rounded-2xl border border-gray-200 bg-white p-6 shadow-sm"
                >
                    <div
                        class="flex flex-col items-start justify-between gap-6 md:flex-row md:items-center"
                    >
                        <div>
                            <h3
                                class="flex items-center gap-2 text-lg font-black text-gray-800"
                            >
                                <svg
                                    class="h-5 w-5 text-[#146b8a]"
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
                                Generate Statistics Report
                            </h3>
                            <p class="mt-1 text-sm font-medium text-gray-500">
                                Aggregates real appointment and patient data
                                from the database. No AI — facts only.
                            </p>
                        </div>
                        <form
                            @submit.prevent="generateReport"
                            class="flex w-full items-center gap-4 md:w-auto"
                        >
                            <input
                                type="month"
                                v-model="generateForm.month"
                                class="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm font-bold text-gray-700 outline-none focus:ring-2 focus:ring-[#146b8a] md:w-48"
                                required
                            />
                            <button
                                type="submit"
                                :disabled="generateForm.processing || isGenerating"
                                class="flex w-full items-center justify-center rounded-xl bg-[#146b8a] px-6 py-2.5 text-sm font-bold text-white shadow-md transition-all hover:bg-[#0e5c78] disabled:opacity-50 md:w-auto"
                            >
                                <svg
                                    v-if="generateForm.processing || isGenerating"
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
                                {{
                                    generateForm.processing || isGenerating
                                        ? 'Calculating…'
                                        : 'Generate Report'
                                }}
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Error -->
                <div
                    v-if="generateForm.errors?.month"
                    class="mx-auto mb-6 max-w-7xl rounded-xl border border-red-200 bg-red-50 px-6 py-4 text-sm font-bold text-red-700 shadow-sm"
                >
                    {{ generateForm.errors.month }}
                </div>

                <!-- ─── REPORT ─────────────────────────────────────────────── -->
                <div
                    v-if="stats"
                    id="printable-report"
                    class="animate-fade-in-up mx-auto max-w-7xl space-y-6"
                >
                    <!-- Report header banner -->
                    <div
                        class="flex items-center justify-between rounded-2xl bg-[#146b8a] p-8 text-white shadow-md"
                    >
                        <div>
                            <p
                                class="text-xs font-bold tracking-widest text-teal-200 uppercase"
                            >
                                Cardiovascular Care Center
                            </p>
                            <h2 class="mt-1 text-2xl font-black">
                                Monthly Performance Report
                            </h2>
                            <p class="mt-1 font-semibold text-teal-200">
                                {{ formattedMonth }}
                            </p>
                        </div>
                        <button
                            @click="() => downloadPdf()"
                            :disabled="!stats"
                            class="flex items-center gap-2 rounded-xl bg-white/10 px-5 py-2.5 text-sm font-bold text-white transition-colors hover:bg-white/20 disabled:cursor-not-allowed disabled:opacity-40"
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
                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                                />
                            </svg>
                            Download PDF
                        </button>
                    </div>

                    <!-- ─── SECTION 1: OPERATIONAL METRICS ─────────────────── -->
                    <div
                        class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm"
                    >
                        <h3
                            class="mb-5 flex items-center gap-2 text-sm font-extrabold tracking-widest text-[#146b8a] uppercase"
                        >
                            <span
                                class="flex h-6 w-6 items-center justify-center rounded-full bg-[#146b8a] text-[10px] font-black text-white"
                                >1</span
                            >
                            Operational Metrics
                        </h3>

                        <!-- Stat cards row -->
                        <div class="mb-6 grid grid-cols-2 gap-4 md:grid-cols-5">
                            <div
                                class="rounded-xl border-l-4 border-l-blue-500 bg-blue-50 p-4"
                            >
                                <p
                                    class="text-[10px] font-extrabold tracking-widest text-blue-500 uppercase"
                                >
                                    Total Appointments
                                </p>
                                <p
                                    class="mt-1 text-3xl font-black text-gray-800"
                                >
                                    {{ stats.total }}
                                </p>
                            </div>
                            <div
                                class="rounded-xl border-l-4 border-l-emerald-500 bg-emerald-50 p-4"
                            >
                                <p
                                    class="text-[10px] font-extrabold tracking-widest text-emerald-600 uppercase"
                                >
                                    Completed
                                </p>
                                <p
                                    class="mt-1 text-3xl font-black text-gray-800"
                                >
                                    {{ stats.completed }}
                                </p>
                            </div>
                            <div
                                class="rounded-xl border-l-4 border-l-amber-500 bg-amber-50 p-4"
                            >
                                <p
                                    class="text-[10px] font-extrabold tracking-widest text-amber-600 uppercase"
                                >
                                    Pending / In Progress
                                </p>
                                <p
                                    class="mt-1 text-3xl font-black text-gray-800"
                                >
                                    {{ stats.pending }}
                                </p>
                            </div>
                            <div
                                class="rounded-xl border-l-4 border-l-red-400 bg-red-50 p-4"
                            >
                                <p
                                    class="text-[10px] font-extrabold tracking-widest text-red-500 uppercase"
                                >
                                    No-Shows
                                </p>
                                <p
                                    class="mt-1 text-3xl font-black text-gray-800"
                                >
                                    {{ stats.no_shows }}
                                </p>
                            </div>
                            <div
                                class="rounded-xl border-l-4 border-l-gray-400 bg-gray-50 p-4"
                            >
                                <p
                                    class="text-[10px] font-extrabold tracking-widest text-gray-500 uppercase"
                                >
                                    Cancelled
                                </p>
                                <p
                                    class="mt-1 text-3xl font-black text-gray-800"
                                >
                                    {{ stats.cancelled }}
                                </p>
                            </div>
                        </div>

                        <!-- Summary counts -->
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div class="rounded-xl bg-emerald-50 p-4">
                                <p
                                    class="text-[10px] font-extrabold tracking-widest text-emerald-600 uppercase"
                                >
                                    Completed
                                </p>
                                <p class="mt-1 text-2xl font-black text-gray-800">
                                    {{ stats.completed }} of {{ stats.total }} appointments
                                </p>
                            </div>
                            <div class="rounded-xl bg-red-50 p-4">
                                <p
                                    class="text-[10px] font-extrabold tracking-widest text-red-500 uppercase"
                                >
                                    No-Shows
                                </p>
                                <p class="mt-1 text-2xl font-black text-gray-800">
                                    {{ stats.no_shows }} patients
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- ─── SECTION 2: DEMOGRAPHIC DISTRIBUTIONS ───────────── -->
                    <div
                        class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm"
                    >
                        <h3
                            class="mb-5 flex items-center gap-2 text-sm font-extrabold tracking-widest text-[#146b8a] uppercase"
                        >
                            <span
                                class="flex h-6 w-6 items-center justify-center rounded-full bg-[#146b8a] text-[10px] font-black text-white"
                                >2</span
                            >
                            Demographic Distribution
                        </h3>

                        <div class="mb-6 grid grid-cols-2 gap-4 md:grid-cols-4">
                            <div
                                class="rounded-xl border border-gray-200 bg-gray-50 p-4 text-center"
                            >
                                <p
                                    class="text-[10px] font-extrabold tracking-widest text-gray-500 uppercase"
                                >
                                    Unique Patients
                                </p>
                                <p
                                    class="mt-1 text-3xl font-black text-gray-800"
                                >
                                    {{
                                        stats.demographics.total_unique_patients
                                    }}
                                </p>
                            </div>
                            <div
                                class="rounded-xl border border-gray-200 bg-purple-50 p-4 text-center"
                            >
                                <p
                                    class="text-[10px] font-extrabold tracking-widest text-purple-500 uppercase"
                                >
                                    Average Age
                                </p>
                                <p
                                    class="mt-1 text-3xl font-black text-gray-800"
                                >
                                    {{ stats.demographics.avg_age
                                    }}<span
                                        v-if="stats.demographics.avg_age !== 'N/A'"
                                        class="ml-1 text-sm font-bold text-gray-400"
                                        >yrs</span
                                    >
                                </p>
                            </div>
                            <div
                                class="rounded-xl border border-blue-100 bg-blue-50 p-4 text-center"
                            >
                                <p
                                    class="text-[10px] font-extrabold tracking-widest text-blue-500 uppercase"
                                >
                                    Male
                                </p>
                                <p
                                    class="mt-1 text-3xl font-black text-gray-800"
                                >
                                    {{ stats.demographics.male_count }}
                                </p>
                                <p class="text-xs font-bold text-blue-400">
                                    patients
                                </p>
                            </div>
                            <div
                                class="rounded-xl border border-pink-100 bg-pink-50 p-4 text-center"
                            >
                                <p
                                    class="text-[10px] font-extrabold tracking-widest text-pink-500 uppercase"
                                >
                                    Female
                                </p>
                                <p
                                    class="mt-1 text-3xl font-black text-gray-800"
                                >
                                    {{ stats.demographics.female_count }}
                                </p>
                                <p class="text-xs font-bold text-pink-400">
                                    patients
                                </p>
                            </div>
                        </div>

                        <!-- Gender split -->
                        <div class="rounded-xl bg-gray-50 p-4">
                            <p
                                class="mb-2 text-[10px] font-extrabold tracking-widest text-gray-500 uppercase"
                            >
                                Gender Split
                            </p>
                            <div class="flex gap-6 text-sm font-bold">
                                <span class="text-blue-600"
                                    >&#9632; Male &nbsp;{{
                                        stats.demographics.male_count
                                    }} patients</span
                                >
                                <span class="text-pink-500"
                                    >&#9632; Female
                                    {{ stats.demographics.female_count }} patients</span
                                >
                                <span
                                    v-if="stats.demographics.other_count > 0"
                                    class="text-gray-400"
                                    >&#9632; Other/Unset
                                    {{ stats.demographics.other_count }}</span
                                >
                            </div>
                        </div>
                    </div>

                    <!-- ─── SECTION 3: CLINICAL PROCEDURES ────────────────── -->
                    <div
                        class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm"
                    >
                        <h3
                            class="mb-5 flex items-center gap-2 text-sm font-extrabold tracking-widest text-[#146b8a] uppercase"
                        >
                            <span
                                class="flex h-6 w-6 items-center justify-center rounded-full bg-[#146b8a] text-[10px] font-black text-white"
                                >3</span
                            >
                            Clinical Procedures Performed
                        </h3>

                        <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                            <div
                                class="rounded-xl border-2 border-[#146b8a] bg-[#146b8a]/5 p-5 text-center"
                            >
                                <p
                                    class="text-[10px] font-extrabold tracking-widest text-[#146b8a] uppercase"
                                >
                                    Echocardiogram
                                </p>
                                <p
                                    class="text-[10px] font-medium text-[#146b8a]/70"
                                >
                                    Type 1
                                </p>
                                <p
                                    class="mt-2 text-4xl font-black text-[#146b8a]"
                                >
                                    {{ stats.procedures.echocardiogram }}
                                </p>
                            </div>
                            <div
                                class="rounded-xl border-2 border-amber-500 bg-amber-50 p-5 text-center"
                            >
                                <p
                                    class="text-[10px] font-extrabold tracking-widest text-amber-600 uppercase"
                                >
                                    Treadmill (TMT)
                                </p>
                                <p
                                    class="text-[10px] font-medium text-amber-500"
                                >
                                    Type 2
                                </p>
                                <p
                                    class="mt-2 text-4xl font-black text-amber-600"
                                >
                                    {{ stats.procedures.treadmill_tmt }}
                                </p>
                            </div>
                            <div
                                class="rounded-xl border-2 border-indigo-500 bg-indigo-50 p-5 text-center"
                            >
                                <p
                                    class="text-[10px] font-extrabold tracking-widest text-indigo-600 uppercase"
                                >
                                    Color Doppler
                                </p>
                                <p
                                    class="text-[10px] font-medium text-indigo-500"
                                >
                                    Type 3
                                </p>
                                <p
                                    class="mt-2 text-4xl font-black text-indigo-600"
                                >
                                    {{ stats.procedures.color_doppler }}
                                </p>
                            </div>
                            <div
                                class="rounded-xl border-2 border-gray-300 bg-gray-50 p-5 text-center"
                            >
                                <p
                                    class="text-[10px] font-extrabold tracking-widest text-gray-500 uppercase"
                                >
                                    Total Procedures
                                </p>
                                <p
                                    class="text-[10px] font-medium text-gray-400"
                                >
                                    All Types
                                </p>
                                <p
                                    class="mt-2 text-4xl font-black text-gray-700"
                                >
                                    {{ stats.procedures.total }}
                                </p>
                            </div>
                        </div>

                        <!-- Procedure breakdown -->
                        <div
                            v-if="stats.procedures.total > 0"
                            class="mt-5 rounded-xl bg-gray-50 p-4"
                        >
                            <p
                                class="mb-2 text-[10px] font-extrabold tracking-widest text-gray-500 uppercase"
                            >
                                Procedure Breakdown
                            </p>
                            <div class="flex flex-wrap gap-6 text-sm font-bold">
                                <span class="text-[#146b8a]"
                                    >&#9632; Echo &nbsp;{{
                                        stats.procedures.echocardiogram
                                    }} procedures</span
                                >
                                <span class="text-amber-600"
                                    >&#9632; TMT &nbsp;{{
                                        stats.procedures.treadmill_tmt
                                    }} procedures</span
                                >
                                <span class="text-indigo-600"
                                    >&#9632; Doppler &nbsp;{{
                                        stats.procedures.color_doppler
                                    }} procedures</span
                                >
                            </div>
                        </div>
                    </div>

                    <!-- ─── NO-SHOW PATTERNS ──────────────────────────────── -->
                    <div
                        v-if="Object.keys(stats.no_show_by_day || {}).length > 0 || Object.keys(stats.no_show_by_time || {}).length > 0"
                        class="rounded-2xl border border-orange-200 bg-white p-6 shadow-sm"
                    >
                        <h3
                            class="mb-4 flex items-center gap-2 text-sm font-extrabold tracking-widest text-orange-700 uppercase"
                        >
                            <span
                                class="flex h-6 w-6 items-center justify-center rounded-full bg-orange-100 text-[10px] font-black text-orange-700"
                                >3b</span
                            >
                            No-Show Pattern Analysis
                        </h3>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div v-if="Object.keys(stats.no_show_by_day || {}).length > 0">
                                <p class="mb-2 text-xs font-bold tracking-wider text-gray-500 uppercase">
                                    By Day of Week
                                </p>
                                <div class="space-y-1.5">
                                    <div
                                        v-for="(count, day) in stats.no_show_by_day"
                                        :key="day"
                                        class="flex items-center gap-2"
                                    >
                                        <span class="w-24 text-xs font-semibold text-gray-600">{{ day }}</span>
                                        <div class="flex-1 rounded-full bg-orange-100 h-2.5">
                                            <div
                                                class="h-2.5 rounded-full bg-orange-500"
                                                :style="`width:${(count / Math.max(...Object.values(stats.no_show_by_day))) * 100}%`"
                                            ></div>
                                        </div>
                                        <span class="w-8 text-right text-xs font-bold text-orange-700">{{ count }}</span>
                                    </div>
                                </div>
                            </div>
                            <div v-if="Object.keys(stats.no_show_by_time || {}).length > 0">
                                <p class="mb-2 text-xs font-bold tracking-wider text-gray-500 uppercase">
                                    By Time of Day
                                </p>
                                <div class="space-y-1.5">
                                    <div
                                        v-for="(count, period) in stats.no_show_by_time"
                                        :key="period"
                                        class="flex items-center gap-2"
                                    >
                                        <span class="w-24 text-xs font-semibold text-gray-600 capitalize">{{ period }}</span>
                                        <div class="flex-1 rounded-full bg-orange-100 h-2.5">
                                            <div
                                                class="h-2.5 rounded-full bg-orange-500"
                                                :style="`width:${(count / Math.max(...Object.values(stats.no_show_by_time))) * 100}%`"
                                            ></div>
                                        </div>
                                        <span class="w-8 text-right text-xs font-bold text-orange-700">{{ count }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ─── SECTION 4: CLINICAL TRENDS ─────────────────────── -->
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <!-- Top Diagnoses -->
                        <div
                            class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm"
                        >
                            <h3
                                class="mb-4 flex items-center gap-2 text-sm font-extrabold tracking-widest text-[#146b8a] uppercase"
                            >
                                <span
                                    class="flex h-6 w-6 items-center justify-center rounded-full bg-[#146b8a] text-[10px] font-black text-white"
                                    >4a</span
                                >
                                Top Diagnoses
                            </h3>

                            <div
                                v-if="stats.top_diagnoses.length"
                                class="space-y-2"
                            >
                                <div
                                    v-for="(dx, i) in stats.top_diagnoses"
                                    :key="i"
                                    class="flex items-center gap-3 rounded-lg border border-gray-100 bg-gray-50 px-4 py-3"
                                >
                                    <span
                                        class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-[#146b8a]/10 text-[10px] font-black text-[#146b8a]"
                                        >{{ i + 1 }}</span
                                    >
                                    <span
                                        class="flex-1 text-sm font-semibold text-gray-700"
                                        >{{ dx.name }}</span
                                    >
                                    <span
                                        class="rounded-md bg-[#146b8a] px-2.5 py-1 text-xs font-black text-white"
                                        >{{ dx.count }} case{{
                                            dx.count !== 1 ? 's' : ''
                                        }}</span
                                    >
                                </div>
                            </div>
                            <p v-else class="text-sm font-medium text-gray-400">
                                No completed consultations with recorded
                                diagnoses.
                            </p>

                            <!-- Diagnosis Trends (month-over-month) -->
                            <div
                                v-if="stats.diagnosis_trends && stats.diagnosis_trends.length > 0"
                                class="mt-4 border-t border-gray-100 pt-4"
                            >
                                <p class="mb-2 text-xs font-bold tracking-wider text-gray-500 uppercase">
                                    vs Previous Month
                                </p>
                                <div class="space-y-2">
                                    <div
                                        v-for="(trend, i) in stats.diagnosis_trends"
                                        :key="i"
                                        class="flex items-center gap-2 rounded-lg bg-gray-50 px-3 py-2"
                                    >
                                        <span class="flex-1 text-xs font-semibold text-gray-700">{{ trend.name }}</span>
                                        <span class="text-[10px] font-medium text-gray-500">
                                            {{ trend.previous_count }} → {{ trend.current_count }}
                                        </span>
                                        <span
                                            class="inline-flex items-center gap-0.5 rounded-full px-2 py-0.5 text-[10px] font-bold"
                                            :class="
                                                trend.change > 0
                                                    ? 'bg-red-100 text-red-700'
                                                    : trend.change < 0
                                                        ? 'bg-emerald-100 text-emerald-700'
                                                        : 'bg-gray-100 text-gray-500'
                                            "
                                        >
                                            <template v-if="trend.change > 0">↑</template>
                                            <template v-else-if="trend.change < 0">↓</template>
                                            {{ Math.abs(trend.change) }} case{{ Math.abs(trend.change) !== 1 ? 's' : '' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Top Services -->
                        <div
                            class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm"
                        >
                            <h3
                                class="mb-4 flex items-center gap-2 text-sm font-extrabold tracking-widest text-[#146b8a] uppercase"
                            >
                                <span
                                    class="flex h-6 w-6 items-center justify-center rounded-full bg-[#146b8a] text-[10px] font-black text-white"
                                    >4b</span
                                >
                                Top Services Rendered
                            </h3>

                            <div
                                v-if="stats.top_services.length"
                                class="space-y-2"
                            >
                                <div
                                    v-for="(svc, i) in stats.top_services"
                                    :key="i"
                                    class="flex items-center gap-3 rounded-lg border border-gray-100 bg-gray-50 px-4 py-3"
                                >
                                    <span
                                        class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-[10px] font-black text-indigo-700"
                                        >{{ i + 1 }}</span
                                    >
                                    <span
                                        class="flex-1 text-sm font-semibold text-gray-700"
                                        >{{ svc.name }}</span
                                    >
                                    <span
                                        class="rounded-md bg-indigo-600 px-2.5 py-1 text-xs font-black text-white"
                                        >{{ svc.count }}x</span
                                    >
                                </div>
                            </div>
                            <p v-else class="text-sm font-medium text-gray-400">
                                No service data recorded for this period.
                            </p>
                        </div>

                        <!-- Age Distribution & Diagnoses by Age Band -->
                        <div
                            class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm md:col-span-2"
                        >
                            <h3
                                class="mb-4 flex items-center gap-2 text-sm font-extrabold tracking-widest text-[#146b8a] uppercase"
                            >
                                <span
                                    class="flex h-6 w-6 items-center justify-center rounded-full bg-[#146b8a] text-[10px] font-black text-white"
                                    >4c</span
                                >
                                Age Profile & Diagnoses by Age Band
                            </h3>

                            <!-- Histogram -->
                            <div v-if="knownAgePatients > 0">
                                <p class="mb-3 text-xs font-bold tracking-wider text-gray-500 uppercase">
                                    Age distribution ({{ knownAgePatients }} patient{{ knownAgePatients !== 1 ? 's' : '' }} with a recorded date of birth)
                                </p>
                                <div class="space-y-2">
                                    <div
                                        v-for="band in stats.age_distribution ?? []"
                                        :key="band.band"
                                        class="flex items-center gap-3"
                                    >
                                        <span class="w-20 shrink-0 text-xs font-semibold text-gray-600">{{ band.label }}</span>
                                        <div class="h-2.5 flex-1 overflow-hidden rounded-full bg-gray-100">
                                            <div
                                                class="h-full rounded-full"
                                                :class="band.band === 'unknown' ? 'bg-gray-300' : 'bg-[#146b8a]'"
                                                :style="`width:${(band.count / maxAgeBandCount) * 100}%`"
                                            ></div>
                                        </div>
                                        <span class="w-20 shrink-0 text-right text-[11px] font-bold text-gray-500">
                                            {{ band.count }} · {{ band.pct }}%
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <p v-else class="text-sm font-medium text-gray-400">
                                No patients with a recorded date of birth.
                            </p>

                            <!-- Cross-tab: which diagnoses appeared in which band -->
                            <div
                                v-if="ageDxRows.length"
                                class="mt-5 border-t border-gray-100 pt-4"
                            >
                                <p class="mb-2 text-xs font-bold tracking-wider text-gray-500 uppercase">
                                    Diagnoses by age band
                                </p>
                                <div class="space-y-2">
                                    <div
                                        v-for="row in ageDxRows"
                                        :key="row.code ?? row.name"
                                        class="rounded-lg border border-gray-100 bg-gray-50 px-4 py-3"
                                    >
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="flex-1 text-sm font-semibold text-gray-700">{{ row.name }}</span>
                                            <span
                                                v-if="row.code"
                                                class="rounded bg-white px-1.5 py-0.5 text-[10px] font-bold text-gray-500 ring-1 ring-gray-200 ring-inset"
                                                >{{ row.code }}</span
                                            >
                                            <span class="rounded-md bg-[#146b8a] px-2 py-0.5 text-[10px] font-black text-white">{{ row.total }} total</span>
                                        </div>
                                        <div class="mt-2 flex flex-wrap gap-1.5">
                                            <span
                                                v-for="p in row.populated"
                                                :key="p.band"
                                                class="rounded-full px-2 py-0.5 text-[10px] font-bold"
                                                :class="
                                                    YOUNG_BANDS.includes(p.band)
                                                        ? 'bg-amber-100 text-amber-800'
                                                        : 'bg-white text-gray-600 ring-1 ring-gray-200 ring-inset'
                                                "
                                                >{{ p.label }}: {{ p.count }}</span
                                            >
                                        </div>
                                        <p
                                            v-if="row.youngCount > 0"
                                            class="mt-2 text-[11px] font-bold text-amber-700"
                                        >
                                            ⚠ {{ row.youngCount }} case{{ row.youngCount !== 1 ? 's' : '' }} in a patient aged
                                            under 30 — unusual for this diagnosis
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ─── MEDICATION ADHERENCE ───────────────────────────── -->
                    <div
                        v-if="stats.medication_adherence && stats.medication_adherence.total_expected_doses > 0"
                        class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm"
                    >
                        <h3
                            class="mb-4 flex items-center gap-2 text-sm font-extrabold tracking-widest text-[#146b8a] uppercase"
                        >
                            <span
                                class="flex h-6 w-6 items-center justify-center rounded-full bg-[#146b8a] text-[10px] font-black text-white"
                                >4c</span
                            >
                            Medication Adherence
                        </h3>

                        <div class="mb-4 rounded-xl bg-gray-50 p-4">
                            <p class="text-[10px] font-extrabold tracking-widest text-gray-500 uppercase">
                                Total Doses Taken
                            </p>
                            <p class="mt-1 text-3xl font-black text-gray-800">
                                {{ stats.medication_adherence.total_taken_doses }} of {{ stats.medication_adherence.total_expected_doses }}
                            </p>
                            <p class="text-xs font-medium text-gray-400">expected doses</p>
                        </div>

                        <div v-if="stats.medication_adherence.medications.length > 0" class="space-y-2">
                            <p class="text-xs font-bold tracking-wider text-gray-500 uppercase">
                                Per Medication
                            </p>
                            <div
                                v-for="(med, i) in stats.medication_adherence.medications"
                                :key="i"
                                class="flex items-center gap-3 rounded-lg border px-4 py-3"
                                :class="
                                    med.taken_doses < med.expected_doses * 0.8
                                        ? 'border-red-100 bg-red-50'
                                        : 'border-gray-100 bg-gray-50'
                                "
                            >
                                <span class="flex-1 text-sm font-semibold text-gray-700">{{ med.medicine }}</span>
                                <span class="text-xs font-bold text-gray-600">
                                    {{ med.taken_doses }} of {{ med.expected_doses }} doses
                                </span>
                            </div>
                        </div>

                        <div
                            v-if="stats.medication_adherence.declining_medications.length > 0"
                            class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3"
                        >
                            <p class="text-xs font-bold tracking-wider text-amber-800 uppercase">
                                ⚠ Requires Intervention
                            </p>
                            <p class="mt-1 text-xs font-medium text-amber-700">
                                {{ stats.medication_adherence.declining_medications.map(m => `${m.medicine} (${m.taken_doses} of ${m.expected_doses} doses)`).join(', ') }}
                                — low adherence. Consider patient education or regimen review.
                            </p>
                        </div>
                    </div>

                    <!-- ─── SECTION 5: AI NARRATIVE REPORT ─────────────────── -->
                    <!-- Shown when AI successfully generated the narrative -->
                    <div
                        v-if="reportText"
                        class="relative overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-lg"
                    >
                        <!-- Watermark -->
                        <div
                            class="pointer-events-none absolute inset-0 flex items-center justify-center opacity-[0.025]"
                        >
                            <p
                                class="-rotate-12 text-9xl font-black tracking-widest text-gray-800 uppercase"
                            >
                                CardioCare
                            </p>
                        </div>

                        <div class="relative z-10 p-10">
                            <!-- Document header -->
                            <div
                                class="mb-8 border-b border-gray-200 pb-6 text-center"
                            >
                                <p
                                    class="text-[10px] font-extrabold tracking-widest text-[#146b8a] uppercase"
                                >
                                    Cardiovascular Care Center
                                </p>
                                <h2
                                    class="mt-1 font-serif text-3xl font-black text-gray-800"
                                >
                                    EXECUTIVE CLINICAL REPORT
                                </h2>
                                <p
                                    class="mt-1 text-sm font-bold tracking-widest text-gray-500 uppercase"
                                >
                                    Period: {{ formattedMonth }}
                                </p>
                            </div>

                            <!-- AI source badge -->
                            <div
                                class="mb-6 flex items-start gap-3 rounded-lg border border-blue-100 bg-blue-50 px-4 py-3"
                            >
                                <svg
                                    class="mt-0.5 h-4 w-4 shrink-0 text-blue-500"
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
                                <p class="text-xs font-medium text-blue-700">
                                    This narrative was generated by AI using
                                    <strong>only</strong> the verified database
                                    figures shown in the statistics above. It
                                    contains no predictions, estimations, or
                                    invented data.
                                </p>
                            </div>

                            <!-- Narrative meta row + Edit toggle -->
                            <div class="mb-5 flex items-center justify-between">
                                <div
                                    class="flex items-center gap-4 text-xs font-bold text-gray-400"
                                >
                                    <span
                                        >{{
                                            narrativeParagraphs.length
                                        }}
                                        paragraph{{
                                            narrativeParagraphs.length !== 1
                                                ? 's'
                                                : ''
                                        }}</span
                                    >
                                    <span>&bull;</span>
                                    <span>~{{ narrativeWordCount }} words</span>
                                    <span
                                        v-if="narrativeEdited"
                                        class="flex items-center gap-1 text-amber-500"
                                    >
                                        <svg
                                            class="h-3 w-3"
                                            fill="currentColor"
                                            viewBox="0 0 20 20"
                                        >
                                            <path
                                                d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"
                                            />
                                        </svg>
                                        Edited
                                    </span>
                                </div>
                                <button
                                    v-if="!isEditingNarrative"
                                    @click="startEditing"
                                    class="flex items-center gap-1.5 rounded-lg border border-gray-200 bg-gray-50 px-3 py-1.5 text-xs font-bold text-gray-600 transition-colors hover:border-[#146b8a] hover:bg-teal-50 hover:text-[#146b8a]"
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
                                            d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"
                                        />
                                    </svg>
                                    Edit Narrative
                                </button>
                            </div>

                            <!-- READ MODE: rendered paragraphs -->
                            <div
                                v-if="!isEditingNarrative"
                                class="space-y-5 text-base leading-relaxed text-gray-800"
                            >
                                <p
                                    v-for="(
                                        paragraph, i
                                    ) in narrativeParagraphs"
                                    :key="i"
                                    class="text-justify indent-8"
                                >
                                    {{ paragraph }}
                                </p>
                            </div>

                            <!-- EDIT MODE: textarea -->
                            <div v-else class="space-y-3">
                                <div
                                    class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-2.5 text-xs font-medium text-amber-700"
                                >
                                    ✏️ You are editing the narrative. Separate
                                    paragraphs with a blank line. Changes are
                                    saved to the database and will appear in the
                                    next PDF download.
                                </div>
                                <textarea
                                    v-model="editText"
                                    rows="18"
                                    class="w-full resize-y rounded-xl border border-gray-300 bg-white px-4 py-3 font-mono text-sm leading-relaxed text-gray-800 outline-none focus:border-[#146b8a] focus:ring-2 focus:ring-[#146b8a]/20"
                                    placeholder="Write the narrative here..."
                                ></textarea>
                                <div class="flex items-center justify-between">
                                    <p class="text-xs text-gray-400">
                                        ~{{ editWordCount }} words
                                    </p>
                                    <div class="flex items-center gap-2">
                                        <button
                                            @click="cancelEditing"
                                            :disabled="editForm.processing"
                                            class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-bold text-gray-600 transition-colors hover:bg-gray-50 disabled:opacity-50"
                                        >
                                            Cancel
                                        </button>
                                        <button
                                            @click="saveNarrative"
                                            :disabled="
                                                editForm.processing ||
                                                !editText.trim()
                                            "
                                            class="flex items-center gap-2 rounded-lg bg-[#146b8a] px-5 py-2 text-sm font-bold text-white shadow-sm transition-colors hover:bg-[#0e5c78] disabled:cursor-not-allowed disabled:opacity-50"
                                        >
                                            <svg
                                                v-if="editForm.processing"
                                                class="h-4 w-4 animate-spin"
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
                                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                                />
                                            </svg>
                                            <svg
                                                v-else
                                                class="h-4 w-4"
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
                                            {{
                                                editForm.processing
                                                    ? 'Saving...'
                                                    : 'Save Narrative'
                                            }}
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Signature block -->
                            <div
                                class="mt-12 flex items-end justify-between border-t border-gray-200 pt-8"
                            >
                                <div>
                                    <p
                                        class="text-[10px] font-extrabold tracking-widest text-gray-400 uppercase"
                                    >
                                        Electronically Prepared By
                                    </p>
                                    <p
                                        class="mt-1 font-serif text-xl font-black text-gray-900 italic"
                                    >
                                        {{ user.name }}
                                    </p>
                                    <p class="text-xs font-bold text-[#146b8a]">
                                        Attending Cardiologist
                                    </p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <!-- Copy narrative to clipboard -->



                                    <!-- Download full PDF -->
                                    <button
                                        @click="() => downloadPdf()"
                                        class="flex items-center gap-2 rounded-xl bg-[#146b8a] px-5 py-2.5 text-sm font-bold text-white shadow-md transition-colors hover:bg-[#0e5c78]"
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
                                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                                            />
                                        </svg>
                                        Download PDF
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Shown when stats exist but AI narrative failed / skipped -->
                    <div
                        v-else
                        class="rounded-2xl border border-dashed border-gray-300 bg-gray-50 p-8 text-center"
                    >
                        <p class="text-sm font-bold text-gray-500">
                            AI narrative unavailable.
                        </p>
                        <p class="mt-1 text-xs text-gray-400">
                            Check that <code>OPENROUTER_API_KEY</code> is set in
                            <code>.env</code> and try generating again.
                        </p>
                    </div>

                    <!-- ─── FOOTER ──────────────────────────────────────────── -->
                    <div
                        class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm print:hidden"
                    >
                        <div class="flex items-end justify-between">
                            <div>
                                <p
                                    class="text-[10px] font-extrabold tracking-widest text-gray-400 uppercase"
                                >
                                    Report Period
                                </p>
                                <p class="mt-1 text-sm font-bold text-gray-700">
                                    {{ formattedMonth }}
                                </p>
                            </div>
                            <p class="text-[10px] text-gray-400">
                                Generated: {{ generatedAt }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- ─── REPORT HISTORY ─────────────────────────────────────── -->
                <div
                    v-if="reportHistory && reportHistory.length > 0"
                    class="mx-auto max-w-7xl"
                >
                    <div
                        class="rounded-2xl border border-gray-200 bg-white shadow-sm"
                    >
                        <button
                            @click="historyOpen = !historyOpen"
                            class="flex w-full items-center justify-between px-6 py-4 text-left transition-colors hover:bg-gray-50"
                        >
                            <div class="flex items-center gap-3">
                                <svg
                                    class="h-5 w-5 text-[#146b8a]"
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
                                <h3
                                    class="text-sm font-extrabold tracking-widest text-[#146b8a] uppercase"
                                >
                                    Report History
                                </h3>
                                <span
                                    class="rounded-full bg-gray-100 px-2.5 py-0.5 text-[10px] font-bold text-gray-500"
                                >
                                    {{ reportHistory.length }}
                                </span>
                            </div>
                            <svg
                                class="h-4 w-4 text-gray-400 transition-transform"
                                :class="historyOpen ? 'rotate-180' : ''"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 9l-7 7-7-7"
                                />
                            </svg>
                        </button>

                        <div v-show="historyOpen" class="border-t border-gray-100 px-6 pb-4 pt-2">
                            <div
                                v-for="(reports, monthLabel) in historyGroupedByMonth"
                                :key="monthLabel"
                                class="mb-3 last:mb-0"
                            >
                                <p
                                    class="mb-2 mt-3 text-[10px] font-extrabold tracking-widest text-gray-400 uppercase"
                                >
                                    {{ monthLabel }}
                                    <span class="ml-1 font-normal text-gray-300"
                                        >({{ reports.length }})</span
                                    >
                                </p>
                                <div class="space-y-1.5">
                                    <div
                                        v-for="report in reports"
                                        :key="report.id"
                                        class="flex items-center justify-between rounded-lg border px-4 py-2.5 transition-colors"
                                        :class="
                                            report.id === currentReportId
                                                ? 'border-[#146b8a] bg-[#146b8a]/5'
                                                : 'border-gray-100 bg-gray-50 hover:border-gray-200 hover:bg-white'
                                        "
                                    >
                                        <div class="flex items-center gap-3">
                                            <span
                                                class="flex h-6 w-6 items-center justify-center rounded-full text-[10px] font-black"
                                                :class="
                                                    report.id === currentReportId
                                                        ? 'bg-[#146b8a] text-white'
                                                        : 'bg-gray-200 text-gray-500'
                                                "
                                            >
                                                <svg
                                                    v-if="report.id === currentReportId"
                                                    class="h-3 w-3"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="3"
                                                        d="M5 13l4 4L19 7"
                                                    />
                                                </svg>
                                                <template v-else>
                                                    {{ reports.indexOf(report) + 1 }}
                                                </template>
                                            </span>
                                            <div>
                                                <p
                                                    class="text-xs font-bold text-gray-700"
                                                >
                                                    Generated
                                                    {{
                                                        formatDate(
                                                            report.created_at,
                                                        )
                                                    }}
                                                </p>
                                                <div
                                                    class="flex items-center gap-2 text-[10px] font-medium text-gray-400"
                                                >
                                                    <span>{{
                                                        report.report_text
                                                            ? report.report_text
                                                                  .split(
                                                                      /\s+/,
                                                                  )
                                                                  .filter(
                                                                      Boolean,
                                                                  ).length +
                                                              ' words'
                                                            : 'No narrative'
                                                    }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <button
                                                v-if="
                                                    report.id !==
                                                    currentReportId
                                                "
                                                @click="viewReport(report.id)"
                                                class="rounded-md border border-gray-200 bg-white px-3 py-1 text-[10px] font-bold text-gray-600 transition-colors hover:border-[#146b8a] hover:text-[#146b8a]"
                                            >
                                                View
                                            </button>
                                            <button
                                                @click="
                                                    downloadPdf(report.id)
                                                "
                                                class="rounded-md border border-gray-200 bg-white px-3 py-1 text-[10px] font-bold text-gray-600 transition-colors hover:border-[#146b8a] hover:text-[#146b8a]"
                                                title="Download PDF of this version"
                                            >
                                                PDF
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ─── EMPTY STATE ─────────────────────────────────────────── -->
                <div
                    v-if="!stats && !generateForm.processing"
                    class="mx-auto flex max-w-7xl flex-col items-center justify-center py-24 text-gray-400"
                >
                    <svg
                        class="mb-4 h-20 w-20 opacity-40"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.5"
                            d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                        />
                    </svg>
                    <p class="text-xl font-bold text-gray-500">
                        No report generated yet
                    </p>
                    <p class="mt-1 text-sm font-medium">
                        Select a month above and click
                        <strong>Generate Report</strong>.
                    </p>
                </div>
            </div>
        </main>
    </div>
</template>

<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

// Type definitions
interface Stats {
    total: number;
    completed: number;
    pending: number;
    no_shows: number;
    cancelled: number;
    demographics: {
        total_unique_patients: number;
        male_count: number;
        female_count: number;
        other_count: number;
        avg_age: number | string;
    };
    procedures: {
        echocardiogram: number;
        treadmill_tmt: number;
        color_doppler: number;
        total: number;
    };
    top_diagnoses: Array<{
        name: string;
        count: number;
    }>;
    /** Absent on reports generated before age analytics shipped. */
    age_distribution?: Array<{
        band: string;
        label: string;
        count: number;
        pct: number;
    }>;
    /** Absent on reports generated before age analytics shipped. */
    diagnosis_by_age?: Array<{
        name: string;
        code: string | null;
        total: number;
        bands: Record<string, number>;
    }>;
    top_services: Array<{
        name: string;
        count: number;
    }>;
    no_show_by_day: Record<string, number>;
    no_show_by_time: Record<string, number>;
    medication_adherence: {
        overall_adherence_rate: number;
        total_expected_doses: number;
        total_taken_doses: number;
        medications: Array<{
            medicine: string;
            expected_doses: number;
            taken_doses: number;
            adherence_rate: number;
        }>;
        declining_medications: Array<{
            medicine: string;
            expected_doses: number;
            taken_doses: number;
            adherence_rate: number;
        }>;
    };
    diagnosis_trends: Array<{
        name: string;
        current_count: number;
        previous_count: number;
        change: number;
        change_pct: number;
    }>;
}

interface User {
    id: string | number;
    name: string;
    email: string;
}

interface ReportHistoryItem {
    id: number;
    month: string;
    stats: Stats | null;
    report_text: string | null;
    created_at: string;
}

const props = defineProps<{
    user: User;
    stats?: Stats | null;
    reportText?: string | null;
    selectedMonth: string;
    currentReportId?: number | null;
    reportHistory?: ReportHistoryItem[];
}>();

const generateForm = useForm({
    month: props.selectedMonth || new Date().toISOString().slice(0, 7),
});

const formattedMonth = computed((): string => {
    if (!generateForm.month) {
        return '';
    }

    const d = new Date(generateForm.month + '-01');

    return d.toLocaleString('default', { month: 'long', year: 'numeric' });
});

const generatedAt = new Date().toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
});

/**
 * Bands where a cardiovascular diagnosis is clinically unexpected. These drive
 * the "unusual presentation" flag, e.g. hypertension in a 20-year-old.
 */
const YOUNG_BANDS = ['0-17', '18-29'];

/** Resolves a band key ('18-29') to its label using the histogram the API sends. */
const bandLabel = (band: string): string => props.stats?.age_distribution?.find((b) => b.band === band)?.label ?? band;

interface AgeDxRow {
    name: string;
    code: string | null;
    total: number;
    populated: Array<{ band: string; label: string; count: number }>;
    youngCount: number;
}

/** Flattens each diagnosis's band map into renderable chips + a young-band total. */
const ageDxRows = computed((): AgeDxRow[] =>
    (props.stats?.diagnosis_by_age ?? []).map((d) => {
        const populated = Object.entries(d.bands ?? {})
            .filter(([, count]) => count > 0)
            .map(([band, count]) => ({ band, label: bandLabel(band), count }));

        return {
            name: d.name,
            code: d.code,
            total: d.total,
            populated,
            youngCount: populated.filter((p) => YOUNG_BANDS.includes(p.band)).reduce((sum, p) => sum + p.count, 0),
        };
    }),
);

/** Patients with a usable DOB — the denominator for the histogram percentages. */
const knownAgePatients = computed((): number =>
    (props.stats?.age_distribution ?? []).filter((b) => b.band !== 'unknown').reduce((sum, b) => sum + b.count, 0),
);

const maxAgeBandCount = computed((): number => Math.max(1, ...(props.stats?.age_distribution ?? []).map((b) => b.count)));

/**
 * Splits the AI narrative into individual paragraphs for clean rendering.
 * The model writes plain-text paragraphs separated by double newlines.
 */
const narrativeParagraphs = computed((): string[] => {
    if (!props.reportText) {
        return [];
    }

    return props.reportText
        .split(/\n{2,}/)
        .map((p: string): string => p.trim())
        .filter((p: string): boolean => p.length > 0);
});

/** Word count of the full narrative text. */
const narrativeWordCount = computed((): number => {
    if (!props.reportText) {
        return 0;
    }

    return props.reportText.trim().split(/\s+/).filter(Boolean).length;
});

const isGenerating = ref(false);

const generateReport = (): void => {
    if (isGenerating.value || generateForm.processing) {
return;
}

    isGenerating.value = true;
    generateForm.post('/doctor/reports/monthly', {
        preserveScroll: true,
        onFinish: (): void => {
            generateForm.processing = false;
            isGenerating.value = false;
        },
    });
};

let pdfDebounceTimer: ReturnType<typeof setTimeout> | null = null;

/**
 * Opens the server-side PDF in a new tab.
 * Debounced to prevent multiple tabs from spam clicking.
 */
const downloadPdf = (reportId?: number): void => {
    if (!generateForm.month) {
return;
}

    if (pdfDebounceTimer) {
return;
}

    const id = reportId ?? props.currentReportId;
    const params = new URLSearchParams();
    params.set('month', generateForm.month);

    if (id) {
        params.set('report', String(id));
    }

    window.open(
        `/doctor/reports/monthly/pdf?${params.toString()}`,
        '_blank',
    );

    pdfDebounceTimer = setTimeout(() => {
        pdfDebounceTimer = null;
    }, 1000);
};

// ─────────────────────────────────────────────────────────────────
// Narrative edit
// ─────────────────────────────────────────────────────────────────

const isEditingNarrative = ref(false);
const editText = ref('');
const narrativeEdited = ref(false);

/** Tracks whether the current reportText differs from the original AI output. */
const editForm = useForm({ report_id: 0, report_text: '' });

/** Word count of the text currently in the edit box. */
const editWordCount = computed((): number => {
    if (!editText.value.trim()) {
        return 0;
    }

    return editText.value.trim().split(/\s+/).length;
});

function startEditing(): void {
    editText.value = props.reportText ?? '';
    isEditingNarrative.value = true;
}

function cancelEditing(): void {
    isEditingNarrative.value = false;
    editText.value = '';
}

function saveNarrative(): void {
    if (!editText.value.trim() || !props.currentReportId) {
        return;
    }

    editForm.report_id = props.currentReportId;
    editForm.report_text = editText.value;

    editForm.put('/doctor/reports/monthly/narrative', {
        preserveScroll: true,
        onSuccess: () => {
            isEditingNarrative.value = false;
            narrativeEdited.value = true;
        },
    });
}

/**
 * Group history by month label (e.g. "May 2026") for display.
 */
const historyGroupedByMonth = computed(() => {
    const groups: Record<string, ReportHistoryItem[]> = {};

    for (const item of props.reportHistory ?? []) {
        const d = new Date(item.month + '-01');
        const label = d.toLocaleString('default', { month: 'long', year: 'numeric' });

        if (!groups[label]) {
groups[label] = [];
}

        groups[label].push(item);
    }

    return groups;
});

const historyOpen = ref(false);

/** Navigate to a specific historical report. */
function viewReport(reportId: number): void {
    window.location.href = `/doctor/reports/monthly?report=${reportId}`;
}

/** Format a date string for display. */
function formatDate(dateStr: string): string {
    const d = new Date(dateStr);

    return d.toLocaleString('default', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

</script>

<style scoped>
.animate-fade-in-up {
    animation: fadeInUp 0.4s ease-out forwards;
}
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(16px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
