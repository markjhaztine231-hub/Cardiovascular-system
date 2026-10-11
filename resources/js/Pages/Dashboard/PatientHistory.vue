<template>
    <div
        class="flex h-screen bg-[#f4f7fa] font-sans selection:bg-[#15818c] selection:text-white"
    >
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
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                />
                            </svg>
                            Patient Records (EMR)
                        </Link>
                    </li>
                    <li>
                        <Link
                            href="/doctor/reports/monthly"
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
                                    d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                />
                            </svg>
                            Monthly Narrative Report
                        </Link>
                    </li>
                </ul>
            </nav>

            <div class="border-t border-[#1b7f9e] p-4">
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

        <!-- MAIN CONTENT -->
        <main class="flex h-screen flex-1 flex-col overflow-hidden">
            <header
                class="z-10 flex h-20 shrink-0 items-center justify-between border-b border-gray-200 bg-white px-8 shadow-sm"
            >
                <div class="flex items-center gap-4">
                    <Link
                        href="/doctor/emr"
                        class="rounded-lg border border-gray-200 bg-gray-50 p-2 text-gray-400 transition-colors hover:text-[#146b8a]"
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
                                d="M10 19l-7-7m0 0l7-7m-7 7h18"
                            />
                        </svg>
                    </Link>
                    <div>
                        <h2
                            class="text-xl font-bold tracking-tight text-gray-800"
                        >
                            Comprehensive Patient History
                        </h2>
                        <p class="text-xs text-gray-500">
                            {{ history.length }} consultation record{{
                                history.length !== 1 ? 's' : ''
                            }}
                        </p>
                    </div>
                </div>
            </header>

            <div class="relative flex-1 overflow-y-auto p-8">
                <div class="mx-auto flex max-w-7xl flex-col gap-8 lg:flex-row">
                    <!-- LEFT: PATIENT PROFILE -->
                    <div class="w-full space-y-6 lg:w-1/3">
                        <div
                            class="sticky top-8 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
                        >
                            <div
                                class="relative bg-linear-to-r from-[#146b8a] to-[#1e8996] p-6 text-center text-white"
                            >
                                <div
                                    class="mx-auto mb-3 flex h-20 w-20 items-center justify-center rounded-full border-4 border-teal-500/30 bg-white text-3xl font-black text-[#146b8a] shadow-lg"
                                >
                                    {{ patient.name.charAt(0) }}
                                </div>
                                <h3 class="text-xl font-bold tracking-wide">
                                    {{ patient.name }}
                                </h3>
                                <p
                                    class="mt-1 text-sm font-medium tracking-widest text-teal-100 uppercase"
                                >
                                    ID: {{ formatPatientId() }}
                                </p>
                            </div>

                            <div class="space-y-6 p-6">
                                <!-- AI Abstract Summary Generator -->
                                <div>
                                    <div
                                        class="mb-3 flex items-center justify-between"
                                    >
                                        <h4
                                            class="flex items-center text-xs font-extrabold tracking-widest text-[#8b5cf6] uppercase"
                                        >
                                            <svg
                                                class="mr-1.5 h-4 w-4"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M13 10V3L4 14h7v7l9-11h-7z"
                                                />
                                            </svg>
                                            Cloud RAG Abstract
                                        </h4>
                                        <span
                                            class="rounded-full border border-purple-200 bg-purple-100 px-2 py-0.5 text-[10px] font-bold text-purple-700 uppercase"
                                            >Deepseek v4 flash</span
                                        >
                                    </div>

                                    <div
                                        class="relative rounded-xl border border-purple-100 bg-purple-50 p-4 shadow-sm"
                                    >
                                        <div v-if="latestAiSummary">
                                            <p
                                                class="text-sm leading-relaxed font-medium text-gray-800 italic"
                                            >
                                                "{{ latestAiSummary }}"
                                            </p>
                                            <div
                                                class="mt-4 flex items-center justify-end gap-2 border-t border-purple-200/50 pt-3"
                                            >
                                                <span
                                                    class="mr-2 text-xs font-bold text-purple-600"
                                                    >Was this summary
                                                    accurate?</span
                                                >
                                                <button
                                                    @click="
                                                        sendFeedback('like')
                                                    "
                                                    class="rounded-md border border-emerald-200 bg-white p-1.5 text-emerald-600 transition-colors hover:bg-emerald-50"
                                                    title="Like & Train AI"
                                                >
                                                    👍
                                                </button>
                                                <button
                                                    @click="
                                                        sendFeedback('dislike')
                                                    "
                                                    class="rounded-md border border-red-200 bg-white p-1.5 text-red-600 transition-colors hover:bg-red-50"
                                                    title="Dislike"
                                                >
                                                    👎
                                                </button>
                                            </div>
                                        </div>
                                        <p
                                            v-else
                                            class="mb-3 text-sm text-gray-500"
                                        >
                                            No abstract generated yet. Click
                                            below to analyze this patient's
                                            history using Deepseek, PubMed, and
                                            PhilHeart Guidelines.
                                        </p>

                                        <div
                                            v-if="
                                                flashSuccess &&
                                                !abstractForm.processing
                                            "
                                            class="mb-3 rounded border border-emerald-100 bg-emerald-50 p-2 text-xs font-bold text-emerald-600"
                                        >
                                            {{ flashSuccess }}
                                        </div>
                                        <p
                                            v-if="
                                                (abstractForm.errors as Record<string, string>).ai ||
                                                (abstractForm.errors as Record<string, string>).ai_error
                                            "
                                            class="mb-3 rounded border border-red-100 bg-red-50 p-2 text-xs font-bold text-red-500"
                                        >
                                            {{
                                                (abstractForm.errors as Record<string, string>).ai ||
                                                (abstractForm.errors as Record<string, string>).ai_error
                                            }}
                                        </p>

                                        <button
                                            @click="generateAiAbstract"
                                            :disabled="abstractForm.processing"
                                            class="mt-3 flex w-full items-center justify-center rounded-lg bg-purple-600 py-2.5 text-xs font-bold text-white shadow-sm transition-colors hover:bg-purple-700 disabled:cursor-wait disabled:opacity-60"
                                        >
                                            <svg
                                                v-if="abstractForm.processing"
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
                                            <span
                                                v-if="abstractForm.processing"
                                                class="text-center"
                                                >Processing Guidelines &
                                                Generating...<br /><span
                                                    class="text-[9px] opacity-80"
                                                    >(First run takes
                                                    ~60s)</span
                                                ></span
                                            >
                                            <span v-else
                                                >Generate Grounded
                                                Abstract</span
                                            >
                                        </button>
                                    </div>
                                </div>

                                <!-- Active Prescriptions -->
                                <div class="border-t border-gray-100 pt-6">
                                    <h4
                                        class="mb-3 flex items-center text-xs font-extrabold tracking-widest text-[#146b8a] uppercase"
                                    >
                                        <svg
                                            class="mr-1.5 h-4 w-4"
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
                                        Active Prescriptions
                                    </h4>
                                    <div
                                        v-if="
                                            latestPrescriptions &&
                                            latestPrescriptions.length > 0
                                        "
                                        class="space-y-2"
                                    >
                                        <div
                                            v-for="(
                                                med, idx
                                            ) in latestPrescriptions"
                                            :key="idx"
                                            class="rounded-lg border border-gray-200 bg-gray-50 p-3"
                                        >
                                            <p
                                                class="text-sm font-bold text-[#146b8a]"
                                            >
                                                {{ med.medicine }}
                                                <span
                                                    class="text-xs font-normal text-gray-500"
                                                    >({{ med.dosage }})</span
                                                >
                                            </p>
                                            <p
                                                class="mt-1 text-xs font-medium text-gray-500"
                                            >
                                                {{ med.frequency }} for
                                                {{ med.duration }} days
                                            </p>
                                        </div>
                                    </div>
                                    <p
                                        v-else
                                        class="rounded-lg border border-dashed border-gray-200 bg-gray-50 p-4 text-center text-sm font-medium text-gray-400 italic"
                                    >
                                        No active medications.
                                    </p>
                                </div>

                                <!-- Adherence Overview -->
                                <div class="border-t border-gray-100 pt-6">
                                    <h4
                                        class="mb-3 flex items-center text-xs font-extrabold tracking-widest text-[#146b8a] uppercase"
                                    >
                                        <svg class="mr-1.5 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                        </svg>
                                        Medication Adherence
                                    </h4>
                                    <div v-if="adherenceLoading" class="py-4 text-center text-xs text-gray-400">
                                        Loading...
                                    </div>
                                    <div
                                        v-else-if="adherenceMeds.length > 0"
                                        class="space-y-3"
                                    >
                                        <div
                                            v-for="(med, idx) in adherenceMeds"
                                            :key="idx"
                                            class="rounded-lg border p-3"
                                            :class="med.adherence_rate >= 80 ? 'border-emerald-200 bg-emerald-50' : med.adherence_rate >= 50 ? 'border-amber-200 bg-amber-50' : 'border-red-200 bg-red-50'"
                                        >
                                            <div class="flex items-center justify-between mb-1">
                                                <p class="text-sm font-bold text-gray-800 capitalize truncate max-w-[140px]">
                                                    {{ med.medicine }}
                                                </p>
                                                <span
                                                    class="text-xs font-extrabold"
                                                    :class="med.adherence_rate >= 80 ? 'text-emerald-700' : med.adherence_rate >= 50 ? 'text-amber-700' : 'text-red-700'"
                                                >
                                                    {{ med.adherence_rate }}%
                                                </span>
                                            </div>
                                            <div class="w-full h-1.5 rounded-full bg-gray-200 overflow-hidden">
                                                <div
                                                    class="h-full rounded-full transition-all"
                                                    :class="med.adherence_rate >= 80 ? 'bg-emerald-500' : med.adherence_rate >= 50 ? 'bg-amber-400' : 'bg-red-400'"
                                                    :style="{ width: med.adherence_rate + '%' }"
                                                ></div>
                                            </div>
                                            <p class="mt-1 text-[10px] text-gray-400">
                                                {{ med.total_taken }}/{{ med.total_expected }} doses
                                            </p>
                                            <div v-if="med.daily && med.daily.length > 0" class="mt-2">
                                                <div class="flex items-start gap-2">
                                                    <div class="flex flex-col gap-px pt-4 text-[8px] font-medium text-gray-400 leading-3">
                                                        <span>Mon</span>
                                                        <span>Tue</span>
                                                        <span>Wed</span>
                                                        <span>Thu</span>
                                                        <span>Fri</span>
                                                        <span>Sat</span>
                                                        <span>Sun</span>
                                                    </div>
                                                    <div class="flex-1 overflow-x-auto">
                                                        <div class="flex gap-px">
                                                            <div
                                                                v-for="weekIdx in [0,1,2,3]"
                                                                :key="weekIdx"
                                                                class="flex flex-col gap-px"
                                                            >
                                                                <div
                                                                    v-for="dayIdx in [1,2,3,4,5,6,0]"
                                                                    :key="dayIdx"
                                                                    class="relative group"
                                                                >
                                                                    <div
                                                                        v-if="hasDay(med.daily, weekIdx, dayIdx)"
                                                                        class="w-3 h-3 rounded-sm cursor-default transition-transform hover:scale-125"
                                                                        :class="adherenceColor(getDay(med.daily, weekIdx, dayIdx).pct)"
                                                                    >
                                                                    </div>
                                                                    <div
                                                                        v-else
                                                                        class="w-3 h-3 rounded-sm"
                                                                        :class="med.daily.length > 0 ? 'bg-gray-100' : ''"
                                                                    ></div>
                                                                    <div
                                                                        class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 hidden group-hover:block z-10"
                                                                    >
                                                                        <div
                                                                            v-if="hasDay(med.daily, weekIdx, dayIdx)"
                                                                            class="whitespace-nowrap rounded-md bg-gray-800 px-2 py-1 text-[10px] font-medium text-white shadow-lg"
                                                                        >
                                                                            {{ getDay(med.daily, weekIdx, dayIdx).date }}:
                                                                            {{ getDay(med.daily, weekIdx, dayIdx).taken }}/{{
                                                                                getDay(med.daily, weekIdx, dayIdx).expected
                                                                            }}
                                                                            ({{ getDay(med.daily, weekIdx, dayIdx).pct }}%)
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="mt-1 flex items-center gap-2 text-[9px] text-gray-400">
                                                    <span class="font-medium">Less</span>
                                                    <span class="inline-block w-2.5 h-2.5 rounded-sm bg-red-300"></span>
                                                    <span class="inline-block w-2.5 h-2.5 rounded-sm bg-amber-300"></span>
                                                    <span class="inline-block w-2.5 h-2.5 rounded-sm bg-emerald-400"></span>
                                                    <span class="font-medium">More</span>
                                                    <span class="ml-auto">{{ med.daily.filter(d => d.pct < 80).length }}/{{ med.daily.length }} days below target</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <p
                                        v-else
                                        class="rounded-lg border border-dashed border-gray-200 bg-gray-50 p-4 text-center text-sm font-medium text-gray-400 italic"
                                    >
                                        No adherence data yet.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT: CHRONOLOGICAL CLINICAL TIMELINE -->
                    <div class="w-full lg:w-2/3">
                        <div
                            class="rounded-2xl border border-gray-200 bg-white p-8 shadow-sm"
                        >
                            <h3
                                class="mb-8 flex items-center border-b border-gray-100 pb-4 text-lg font-black tracking-tight text-gray-800"
                            >
                                <svg
                                    class="mr-2 h-5 w-5 text-[#146b8a]"
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
                                Consultation History
                            </h3>

                            <div class="mb-6">
                                <AnalyticsPanel :patient-id="Number(patient.id)" />
                            </div>

                            <div
                                v-if="history.length === 0"
                                class="py-12 text-center"
                            >
                                <p class="text-base font-bold text-gray-600">
                                    No Consultation Records
                                </p>
                                <p class="mt-1 text-sm text-gray-400">
                                    This patient has no completed consultations
                                    yet.
                                </p>
                            </div>

                            <div
                                v-else
                                class="relative ml-4 space-y-10 border-l-2 border-[#146b8a]/20"
                            >
                                <div
                                    v-for="record in history"
                                    :key="record.id"
                                    class="relative pl-8"
                                >
                                    <div
                                        class="absolute top-1.5 -left-2.25 h-4 w-4 rounded-full bg-[#146b8a] shadow-sm ring-4 ring-white"
                                    ></div>

                                    <!-- ═══ HISTORY CARD (Clickable) ═══ -->
                                    <div
                                        @click="openDetailView(record)"
                                        class="group cursor-pointer rounded-xl border border-gray-200 bg-white p-6 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-[#146b8a]/50 hover:shadow-md"
                                    >
                                        <div
                                            class="mb-4 flex flex-col gap-3 border-b border-gray-100 pb-4 sm:flex-row sm:items-start sm:justify-between"
                                        >
                                            <div>
                                                <div
                                                    class="flex items-center gap-3"
                                                >
                                                    <h4
                                                        class="text-base font-black text-[#146b8a]"
                                                    >
                                                        {{ record.date }}
                                                    </h4>
                                                    <span
                                                        class="rounded bg-gray-100 px-2 py-0.5 text-[10px] font-bold text-gray-600 uppercase"
                                                    >
                                                        {{
                                                            Array.isArray(
                                                                record.consultation_type,
                                                            )
                                                                ? record.consultation_type.join(
                                                                      ' + ',
                                                                  )
                                                                : record.consultation_type ||
                                                                  'Standard'
                                                        }}
                                                    </span>
                                                    <!-- Type badges -->
                                                    <span
                                                        v-if="record.echo_data"
                                                        class="rounded-full bg-blue-100 px-2 py-0.5 text-[9px] font-bold text-blue-600"
                                                        >ECHO</span
                                                    >
                                                    <span
                                                        v-if="record.tmt_data"
                                                        class="rounded-full bg-purple-100 px-2 py-0.5 text-[9px] font-bold text-purple-600"
                                                        >TMT</span
                                                    >
                                                    <span
                                                        v-if="
                                                            record.doppler_data
                                                        "
                                                        class="rounded-full bg-teal-100 px-2 py-0.5 text-[9px] font-bold text-teal-600"
                                                        >DOPPLER</span
                                                    >
                                                    <span
                                                        v-if="
                                                            record.manual_draft_data
                                                        "
                                                        class="rounded-full bg-amber-100 px-2 py-0.5 text-[9px] font-bold text-amber-700"
                                                        >MANUAL DRAFT</span
                                                    >
                                                </div>
                                                <p
                                                    class="mt-0.5 text-xs font-bold text-gray-500"
                                                >
                                                    {{ record.time }} •
                                                    {{ record.service }}
                                                </p>
                                                <p
                                                    class="mt-0.5 text-xs text-gray-400"
                                                >
                                                    Dr. {{ record.doctor }}
                                                </p>
                                            </div>

                                            <!-- "Click to view" hint + Export Panel -->
                                            <div
                                                class="flex flex-col items-end gap-2"
                                            >
                                                <span
                                                    class="flex items-center gap-1 text-[10px] font-semibold text-[#146b8a] opacity-0 transition-opacity group-hover:opacity-100"
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
                                                    View Full Record
                                                </span>

                                                <!-- Export buttons (stop propagation so clicking them doesn't open the modal) -->
                                                <div
                                                    v-if="
                                                        record.echo_data ||
                                                        record.doppler_data ||
                                                        record.tmt_data ||
                                                        record.manual_draft_data ||
                                                        parsedPrescriptions(record).length > 0
                                                    "
                                                    class="flex flex-wrap justify-end gap-1.5"
                                                    @click.stop
                                                >
                                                    <!-- Echo export group -->
                                                    <template
                                                        v-if="record.echo_data"
                                                    >
                                                        <span
                                                            class="self-center text-[10px] font-extrabold tracking-widest text-gray-400 uppercase"
                                                            >Echo:</span
                                                        >
                                                        <button
                                                            @click.stop="
                                                                doExport(
                                                                    'echo',
                                                                    'docx',
                                                                    record,
                                                                )
                                                            "
                                                            class="inline-flex items-center rounded-lg border border-blue-200 bg-blue-50 px-2.5 py-1 text-[10px] font-bold text-blue-700 transition-colors hover:bg-blue-600 hover:text-white"
                                                        >
                                                            DOCX
                                                        </button>
                                                        <button
                                                            @click.stop="
                                                                doExport(
                                                                    'echo',
                                                                    'pdf',
                                                                    record,
                                                                )
                                                            "
                                                            class="inline-flex items-center rounded-lg border border-blue-200 bg-blue-50 px-2.5 py-1 text-[10px] font-bold text-blue-700 transition-colors hover:bg-blue-600 hover:text-white"
                                                        >
                                                            PDF
                                                        </button>
                                                    </template>

                                                    <!-- Doppler export group -->
                                                    <template
                                                        v-if="
                                                            record.doppler_data
                                                        "
                                                    >
                                                        <span
                                                            class="self-center text-[10px] font-extrabold tracking-widest text-gray-400 uppercase"
                                                            >Doppler:</span
                                                        >
                                                        <button
                                                            @click.stop="
                                                                doExport(
                                                                    'doppler',
                                                                    'docx',
                                                                    record,
                                                                )
                                                            "
                                                            class="inline-flex items-center rounded-lg border border-teal-200 bg-teal-50 px-2.5 py-1 text-[10px] font-bold text-teal-700 transition-colors hover:bg-teal-600 hover:text-white"
                                                        >
                                                            DOCX
                                                        </button>
                                                        <button
                                                            @click.stop="
                                                                doExport(
                                                                    'doppler',
                                                                    'pdf',
                                                                    record,
                                                                )
                                                            "
                                                            class="inline-flex items-center rounded-lg border border-teal-200 bg-teal-50 px-2.5 py-1 text-[10px] font-bold text-teal-700 transition-colors hover:bg-teal-600 hover:text-white"
                                                        >
                                                            PDF
                                                        </button>
                                                    </template>

                                                    <!-- TMT export group -->
                                                    <template
                                                        v-if="record.tmt_data"
                                                    >
                                                        <span
                                                            class="self-center text-[10px] font-extrabold tracking-widest text-gray-400 uppercase"
                                                            >TMT:</span
                                                        >
                                                        <button
                                                            @click.stop="
                                                                doExport(
                                                                    'tmt',
                                                                    'docx',
                                                                    record,
                                                                )
                                                            "
                                                            class="inline-flex items-center rounded-lg border border-purple-200 bg-purple-50 px-2.5 py-1 text-[10px] font-bold text-purple-700 transition-colors hover:bg-purple-600 hover:text-white"
                                                        >
                                                            DOCX
                                                        </button>
                                                        <button
                                                            @click.stop="
                                                                doExport(
                                                                    'tmt',
                                                                    'pdf',
                                                                    record,
                                                                )
                                                            "
                                                            class="inline-flex items-center rounded-lg border border-purple-200 bg-purple-50 px-2.5 py-1 text-[10px] font-bold text-purple-700 transition-colors hover:bg-purple-600 hover:text-white"
                                                        >
                                                            PDF
                                                        </button>
                                                    </template>

                                                    <!-- Manual Draft export group -->
                                                    <template
                                                        v-if="
                                                            record.manual_draft_data
                                                        "
                                                    >
                                                        <span
                                                            class="self-center text-[10px] font-extrabold tracking-widest text-gray-400 uppercase"
                                                            >Draft:</span
                                                        >
                                                        <button
                                                            @click.stop="
                                                                doExport(
                                                                    'manual_draft',
                                                                    'docx',
                                                                    record,
                                                                )
                                                            "
                                                            class="inline-flex items-center rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-1 text-[10px] font-bold text-amber-700 transition-colors hover:bg-amber-600 hover:text-white"
                                                        >
                                                            DOCX
                                                        </button>
                                                        <button
                                                            @click.stop="
                                                                doExport(
                                                                    'manual_draft',
                                                                    'pdf',
                                                                    record,
                                                                )
                                                            "
                                                            class="inline-flex items-center rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-1 text-[10px] font-bold text-amber-700 transition-colors hover:bg-amber-600 hover:text-white"
                                                        >
                                                            PDF
                                                        </button>
                                                    </template>

                                                    <!-- Prescription export group -->
                                                    <template
                                                        v-if="
                                                            parsedPrescriptions(record)
                                                                .length > 0
                                                        "
                                                    >
                                                        <span
                                                            class="self-center text-[10px] font-extrabold tracking-widest text-gray-400 uppercase"
                                                            >Rx:</span
                                                        >
                                                        <button
                                                            @click.stop="
                                                                downloadServerPdf(
                                                                    'prescription',
                                                                    record,
                                                                )
                                                            "
                                                            class="inline-flex items-center rounded-lg border border-green-200 bg-green-50 px-2.5 py-1 text-[10px] font-bold text-green-700 transition-colors hover:bg-green-600 hover:text-white"
                                                        >
                                                            PDF
                                                        </button>
                                                    </template>
                                                </div>
                                                <p
                                                    v-else
                                                    class="text-xs font-bold text-gray-500"
                                                >
                                                    Attended by:
                                                    {{ record.doctor }}
                                                </p>
                                            </div>
                                        </div>

                                        <!-- Consultation Type -->
                                        <div
                                            v-if="record.service"
                                            class="mb-3 rounded-lg border border-blue-100 bg-blue-50/50 p-3"
                                        >
                                            <p
                                                class="mb-0.5 text-[10px] font-extrabold tracking-widest text-blue-600 uppercase"
                                            >
                                                Consultation Type
                                            </p>
                                            <p
                                                class="text-sm font-bold text-gray-900"
                                            >
                                                {{ record.service }}
                                            </p>
                                        </div>

                                        <!-- Symptoms/Vitals preview -->
                                        <div
                                            class="grid grid-cols-1 gap-4 md:grid-cols-2"
                                        >
                                            <div>
                                                <p
                                                    class="mb-1 text-[10px] font-extrabold tracking-widest text-gray-400 uppercase"
                                                >
                                                    Symptoms / Subjective
                                                </p>
                                                <p
                                                    class="line-clamp-2 text-sm font-medium text-gray-700"
                                                >
                                                    {{
                                                        record.symptoms ||
                                                        'No symptoms recorded.'
                                                    }}
                                                </p>
                                            </div>
                                            <div>
                                                <p
                                                    class="mb-1 text-[10px] font-extrabold tracking-widest text-gray-400 uppercase"
                                                >
                                                    Vitals / Objective
                                                </p>
                                                <p
                                                    class="line-clamp-2 text-sm font-medium text-gray-700"
                                                >
                                                    {{
                                                        record.vitals ||
                                                        'No vitals recorded.'
                                                    }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- ═══════════════════════════════════════════════════════════════ -->
        <!-- FULL-SCREEN RECORD DETAIL OVERLAY -->
        <!-- ═══════════════════════════════════════════════════════════════ -->
        <Teleport to="body">
            <div
                v-if="selectedRecord"
                class="fixed inset-0 z-50 flex flex-col bg-white"
                style="animation: fadeSlideIn 0.25s ease-out both"
            >
                <!-- Detail Header -->
                <div
                    class="flex shrink-0 items-center justify-between bg-[#1e293b] px-6 py-4 text-white shadow-md"
                >
                    <div class="flex items-center gap-4">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-lg bg-teal-500 text-lg font-bold"
                        >
                            {{ patient.name.charAt(0) }}
                        </div>
                        <div>
                            <h2 class="text-xl font-bold">
                                {{ patient.name }}
                            </h2>
                            <p
                                class="text-xs font-medium tracking-widest text-gray-300 uppercase"
                            >
                                {{ selectedRecord.date }} •
                                {{ selectedRecord.time }} •
                                {{ selectedRecord.service }}
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <!-- PDF quick-download buttons in header -->
                        <button
                            v-if="selectedRecord.echo_data"
                            @click="downloadServerPdf('echo', selectedRecord)"
                            class="flex items-center gap-1.5 rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-bold text-white transition-colors hover:bg-blue-700"
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
                            Echo PDF
                        </button>
                        <button
                            v-if="selectedRecord.tmt_data"
                            @click="downloadServerPdf('tmt', selectedRecord)"
                            class="flex items-center gap-1.5 rounded-lg bg-purple-600 px-3 py-1.5 text-xs font-bold text-white transition-colors hover:bg-purple-700"
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
                            TMT PDF
                        </button>
                        <button
                            v-if="selectedRecord.doppler_data"
                            @click="
                                downloadServerPdf('doppler', selectedRecord)
                            "
                            class="flex items-center gap-1.5 rounded-lg bg-teal-600 px-3 py-1.5 text-xs font-bold text-white transition-colors hover:bg-teal-700"
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
                            Doppler PDF
                        </button>
                        <button
                            v-if="selectedRecord.manual_draft_data"
                            @click="
                                downloadServerPdf('manual_draft', selectedRecord)
                            "
                            class="flex items-center gap-1.5 rounded-lg bg-amber-600 px-3 py-1.5 text-xs font-bold text-white transition-colors hover:bg-amber-700"
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
                            Draft PDF
                        </button>
                        <button
                            v-if="
                                parsedPrescriptions(selectedRecord).length > 0
                            "
                            @click="
                                downloadServerPdf(
                                    'prescription',
                                    selectedRecord,
                                )
                            "
                            class="flex items-center gap-1.5 rounded-lg bg-green-600 px-3 py-1.5 text-xs font-bold text-white transition-colors hover:bg-green-700"
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
                            Prescription PDF
                        </button>
                        <button
                            @click="closeDetailView"
                            class="ml-2 rounded-lg border border-white/20 p-2 text-gray-300 transition-colors hover:bg-white/10 hover:text-white"
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
                        </button>
                    </div>
                </div>

                <!-- Detail Body (scrollable) -->
                <div class="flex-1 overflow-y-auto bg-[#f4f7fa] p-8">
                    <div class="mx-auto max-w-6xl space-y-8">
                        <!-- Type badges summary -->
                        <div class="flex flex-wrap items-center gap-2">
                            <span
                                class="text-xs font-bold tracking-widest text-gray-500 uppercase"
                                >Consultation Types:</span
                            >
                            <span
                                v-if="selectedRecord.echo_data"
                                class="rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-700"
                                >Type 1 — Echocardiographic</span
                            >
                            <span
                                v-if="selectedRecord.tmt_data"
                                class="rounded-full bg-purple-100 px-3 py-1 text-xs font-bold text-purple-700"
                                >Type 2 — Treadmill (TMT)</span
                            >
                            <span
                                v-if="selectedRecord.doppler_data"
                                class="rounded-full bg-teal-100 px-3 py-1 text-xs font-bold text-teal-700"
                                >Type 3 — Color Doppler</span
                            >
                            <span
                                v-if="selectedRecord.manual_draft_data"
                                class="rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-700"
                                >Type 4 — Manual Draft</span
                            >
                            <span
                                v-if="
                                    !selectedRecord.echo_data &&
                                    !selectedRecord.tmt_data &&
                                    !selectedRecord.doppler_data &&
                                    !selectedRecord.manual_draft_data
                                "
                                class="rounded-full bg-gray-100 px-3 py-1 text-xs font-bold text-gray-600"
                                >Standard Consultation</span
                            >
                        </div>

                        <!-- SOAP NOTES -->
                        <div
                            class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm"
                        >
                            <h3
                                class="mb-5 flex items-center text-sm font-extrabold tracking-widest text-[#146b8a] uppercase"
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
                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                                    />
                                </svg>
                                SOAP Notes
                            </h3>

                            <div
                                v-if="selectedRecord.service"
                                class="mb-5 rounded-xl border border-blue-100 bg-blue-50 p-4"
                            >
                                <p
                                    class="mb-1 text-[10px] font-extrabold tracking-widest text-blue-600 uppercase"
                                >
                                    Consultation Type
                                </p>
                                <p class="text-lg font-bold text-gray-900">
                                    {{ selectedRecord.service }}
                                </p>
                            </div>

                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div>
                                    <p
                                        class="mb-2 text-[10px] font-extrabold tracking-widest text-gray-400 uppercase"
                                    >
                                        Subjective (Symptoms)
                                    </p>
                                    <div
                                        class="min-h-20 rounded-xl border border-gray-200 bg-gray-50 p-4"
                                    >
                                        <p
                                            class="text-sm font-medium whitespace-pre-wrap text-gray-700"
                                        >
                                            {{
                                                selectedRecord.symptoms ||
                                                'No symptoms recorded.'
                                            }}
                                        </p>
                                    </div>
                                </div>
                                <div>
                                    <p
                                        class="mb-2 text-[10px] font-extrabold tracking-widest text-gray-400 uppercase"
                                    >
                                        Objective (Vitals)
                                    </p>
                                    <div
                                        class="min-h-20 rounded-xl border border-gray-200 bg-gray-50 p-4"
                                    >
                                        <p
                                            class="text-sm font-medium whitespace-pre-wrap text-gray-700"
                                        >
                                            {{
                                                selectedRecord.vitals ||
                                                'No vitals recorded.'
                                            }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- PRESCRIPTIONS -->
                        <div
                            v-if="
                                parsedPrescriptions(selectedRecord).length > 0
                            "
                            class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm"
                        >
                            <h3
                                class="mb-5 flex items-center text-sm font-extrabold tracking-widest text-[#146b8a] uppercase"
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
                                        d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"
                                    />
                                </svg>
                                Prescriptions
                            </h3>
                            <div
                                class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3"
                            >
                                <div
                                    v-for="(med, idx) in parsedPrescriptions(
                                        selectedRecord,
                                    )"
                                    :key="idx"
                                    class="rounded-xl border border-[#146b8a]/20 bg-teal-50/50 p-4"
                                >
                                    <p class="font-bold text-[#146b8a]">
                                        {{ med.medicine }}
                                    </p>
                                    <p class="mt-1 text-sm text-gray-600">
                                        {{ med.dosage }}
                                    </p>
                                    <p class="mt-1 text-xs text-gray-500">
                                        {{ med.frequency }} —
                                        {{ med.duration }} days
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- ECHO DATA (Type 1) -->
                        <div
                            v-if="selectedRecord.echo_data"
                            class="rounded-2xl border border-blue-100 bg-white p-6 shadow-sm"
                        >
                            <div class="mb-5 flex items-center justify-between">
                                <h3
                                    class="flex items-center text-sm font-extrabold tracking-widest text-blue-700 uppercase"
                                >
                                    <span
                                        class="mr-2 rounded bg-blue-100 px-2 py-0.5 text-blue-700"
                                        >Type 1</span
                                    >
                                    Echocardiographic Report
                                </h3>
                                <button
                                    @click="
                                        downloadServerPdf(
                                            'echo',
                                            selectedRecord,
                                        )
                                    "
                                    class="flex items-center gap-1.5 rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-blue-700"
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
                                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                                        />
                                    </svg>
                                    Download PDF
                                </button>
                            </div>
                            <div class="overflow-x-auto">
                                <table
                                    class="min-w-full border-collapse text-left text-sm"
                                >
                                    <thead>
                                        <tr class="bg-blue-50">
                                            <th
                                                class="border border-blue-200 px-3 py-2 text-xs font-bold text-blue-700"
                                                colspan="2"
                                            >
                                                Patient Info
                                            </th>
                                            <th
                                                class="border border-blue-200 px-3 py-2 text-xs font-bold text-blue-700"
                                                colspan="2"
                                            >
                                                Dimensions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            v-for="row in echoTableRows(
                                                selectedRecord.echo_data,
                                            )"
                                            :key="row.label"
                                            class="hover:bg-gray-50"
                                        >
                                            <td
                                                class="border border-gray-200 px-3 py-2 text-xs font-medium text-gray-600"
                                            >
                                                {{ row.label }}
                                            </td>
                                            <td
                                                class="border border-gray-200 px-3 py-2 font-bold text-gray-900"
                                            >
                                                {{ row.value || '—' }}
                                            </td>
                                            <td
                                                class="border border-gray-200 px-3 py-2 text-xs font-medium text-gray-600"
                                            >
                                                {{ row.label2 }}
                                            </td>
                                            <td
                                                class="border border-gray-200 px-3 py-2 font-bold text-gray-900"
                                            >
                                                {{ row.value2 || '—' }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div
                                v-if="selectedRecord.echo_data.findings"
                                class="mt-4 rounded-xl border border-gray-200 bg-gray-50 p-4"
                            >
                                <p
                                    class="mb-1 text-[10px] font-extrabold tracking-widest text-gray-400 uppercase"
                                >
                                    Echocardiographic Findings
                                </p>
                                <p
                                    class="text-sm whitespace-pre-wrap text-gray-700"
                                >
                                    {{ selectedRecord.echo_data.findings }}
                                </p>
                            </div>
                        </div>

                        <!-- TMT DATA (Type 2) -->
                        <div
                            v-if="selectedRecord.tmt_data"
                            class="rounded-2xl border border-purple-100 bg-white p-6 shadow-sm"
                        >
                            <div class="mb-5 flex items-center justify-between">
                                <h3
                                    class="flex items-center text-sm font-extrabold tracking-widest text-purple-700 uppercase"
                                >
                                    <span
                                        class="mr-2 rounded bg-purple-100 px-2 py-0.5 text-purple-700"
                                        >Type 2</span
                                    >
                                    Treadmill Exercise Test
                                </h3>
                                <button
                                    @click="
                                        downloadServerPdf('tmt', selectedRecord)
                                    "
                                    class="flex items-center gap-1.5 rounded-lg bg-purple-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-purple-700"
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
                                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                                        />
                                    </svg>
                                    Download PDF
                                </button>
                            </div>
                            <!-- TMT Patient Info -->
                            <div
                                class="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-4"
                            >
                                <div class="rounded-lg bg-purple-50 p-3">
                                    <p
                                        class="text-[10px] font-bold text-purple-600 uppercase"
                                    >
                                        Age
                                    </p>
                                    <p class="text-sm font-bold text-gray-900">
                                        {{ selectedRecord.tmt_data.age || '—' }}
                                    </p>
                                </div>
                                <div class="rounded-lg bg-purple-50 p-3">
                                    <p
                                        class="text-[10px] font-bold text-purple-600 uppercase"
                                    >
                                        PMHR
                                    </p>
                                    <p class="text-sm font-bold text-gray-900">
                                        {{
                                            selectedRecord.tmt_data.pmhr || '—'
                                        }}
                                    </p>
                                </div>
                                <div class="rounded-lg bg-purple-50 p-3">
                                    <p
                                        class="text-[10px] font-bold text-purple-600 uppercase"
                                    >
                                        Resting BP
                                    </p>
                                    <p class="text-sm font-bold text-gray-900">
                                        {{ selectedRecord.tmt_data.bp || '—' }}
                                    </p>
                                </div>
                                <div class="rounded-lg bg-purple-50 p-3">
                                    <p
                                        class="text-[10px] font-bold text-purple-600 uppercase"
                                    >
                                        Protocol
                                    </p>
                                    <p class="text-sm font-bold text-gray-900">
                                        {{
                                            selectedRecord.tmt_data.protocol ||
                                            'BRUCE'
                                        }}
                                    </p>
                                </div>
                            </div>
                            <!-- TMT Stages Table -->
                            <div
                                v-if="selectedRecord.tmt_data.stages?.length"
                                class="overflow-x-auto"
                            >
                                <table
                                    class="min-w-full border-collapse text-sm"
                                >
                                    <thead>
                                        <tr class="bg-purple-50">
                                            <th
                                                class="border border-purple-200 px-3 py-2 text-xs font-bold text-purple-700"
                                            >
                                                Event
                                            </th>
                                            <th
                                                class="border border-purple-200 px-3 py-2 text-xs font-bold text-purple-700"
                                            >
                                                Speed (mph)
                                            </th>
                                            <th
                                                class="border border-purple-200 px-3 py-2 text-xs font-bold text-purple-700"
                                            >
                                                Grade (%)
                                            </th>
                                            <th
                                                class="border border-purple-200 px-3 py-2 text-xs font-bold text-purple-700"
                                            >
                                                METs
                                            </th>
                                            <th
                                                class="border border-purple-200 px-3 py-2 text-xs font-bold text-purple-700"
                                            >
                                                BP
                                            </th>
                                            <th
                                                class="border border-purple-200 px-3 py-2 text-xs font-bold text-purple-700"
                                            >
                                                HR
                                            </th>
                                            <th
                                                class="border border-purple-200 px-3 py-2 text-xs font-bold text-purple-700"
                                            >
                                                Remarks
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            v-for="(stage, si) in selectedRecord
                                                .tmt_data.stages"
                                            :key="si"
                                            :class="
                                                stage.name?.includes(
                                                    'Recovery',
                                                ) || stage.name?.includes('min')
                                                    ? 'bg-white'
                                                    : 'bg-purple-50/30'
                                            "
                                        >
                                            <td
                                                class="border border-gray-200 px-3 py-2 font-medium text-gray-700"
                                            >
                                                {{ stage.name }}
                                            </td>
                                            <td
                                                class="border border-gray-200 px-3 py-2 text-center text-gray-900"
                                            >
                                                {{ stage.speed }}
                                            </td>
                                            <td
                                                class="border border-gray-200 px-3 py-2 text-center text-gray-900"
                                            >
                                                {{ stage.grade }}
                                            </td>
                                            <td
                                                class="border border-gray-200 px-3 py-2 text-center text-gray-900"
                                            >
                                                {{ stage.mets }}
                                            </td>
                                            <td
                                                class="border border-gray-200 px-3 py-2 font-bold text-gray-900"
                                            >
                                                {{ stage.bp || '—' }}
                                            </td>
                                            <td
                                                class="border border-gray-200 px-3 py-2 font-bold text-gray-900"
                                            >
                                                {{ stage.hr || '—' }}
                                            </td>
                                            <td
                                                class="border border-gray-200 px-3 py-2 text-gray-600"
                                            >
                                                {{ stage.remarks || '' }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <!-- TMT Interpretation -->
                            <div
                                class="mt-4 space-y-2 rounded-xl border border-gray-200 bg-gray-50 p-4"
                            >
                                <p
                                    class="text-[10px] font-extrabold tracking-widest text-gray-400 uppercase"
                                >
                                    Interpretation
                                </p>
                                <div
                                    v-if="selectedRecord.tmt_data.resting_ecg"
                                    class="flex gap-2"
                                >
                                    <span
                                        class="text-xs font-bold text-gray-500"
                                        >A. Resting ECG:</span
                                    ><span class="text-xs text-gray-700">{{
                                        selectedRecord.tmt_data.resting_ecg
                                    }}</span>
                                </div>
                                <div
                                    v-if="
                                        selectedRecord.tmt_data
                                            .response_to_exercise
                                    "
                                    class="flex gap-2"
                                >
                                    <span
                                        class="text-xs font-bold text-gray-500"
                                        >B. Response:</span
                                    ><span class="text-xs text-gray-700">{{
                                        selectedRecord.tmt_data
                                            .response_to_exercise
                                    }}</span>
                                </div>
                                <div
                                    v-if="
                                        selectedRecord.tmt_data
                                            .exercise_capacity
                                    "
                                    class="flex gap-2"
                                >
                                    <span
                                        class="text-xs font-bold text-gray-500"
                                        >C. Exercise Capacity:</span
                                    ><span class="text-xs text-gray-700">{{
                                        selectedRecord.tmt_data
                                            .exercise_capacity
                                    }}</span>
                                </div>
                                <div
                                    v-if="selectedRecord.tmt_data.conclusion"
                                    class="mt-2 rounded-lg border border-gray-200 bg-white p-3"
                                >
                                    <p
                                        class="mb-1 text-[10px] font-extrabold tracking-widest text-gray-400 uppercase"
                                    >
                                        Conclusion
                                    </p>
                                    <p class="text-sm font-bold text-gray-800">
                                        {{ selectedRecord.tmt_data.conclusion }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- DOPPLER DATA (Type 3) -->
                        <div
                            v-if="selectedRecord.doppler_data"
                            class="rounded-2xl border border-teal-100 bg-white p-6 shadow-sm"
                        >
                            <div class="mb-5 flex items-center justify-between">
                                <h3
                                    class="flex items-center text-sm font-extrabold tracking-widest text-teal-700 uppercase"
                                >
                                    <span
                                        class="mr-2 rounded bg-teal-100 px-2 py-0.5 text-teal-700"
                                        >Type 3</span
                                    >
                                    Color Flow & Doppler Study
                                </h3>
                                <button
                                    @click="
                                        downloadServerPdf(
                                            'doppler',
                                            selectedRecord,
                                        )
                                    "
                                    class="flex items-center gap-1.5 rounded-lg bg-teal-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-teal-700"
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
                                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                                        />
                                    </svg>
                                    Download PDF
                                </button>
                            </div>
                            <div class="overflow-x-auto">
                                <table
                                    class="min-w-full border-collapse text-sm"
                                >
                                    <thead>
                                        <tr class="bg-teal-50">
                                            <th
                                                class="border border-teal-200 px-3 py-2 text-xs font-bold text-teal-700"
                                            >
                                                Parameter
                                            </th>
                                            <th
                                                class="border border-teal-200 px-3 py-2 text-xs font-bold text-teal-700"
                                            >
                                                Value
                                            </th>
                                            <th
                                                class="border border-teal-200 px-3 py-2 text-xs font-bold text-teal-700"
                                            >
                                                Parameter
                                            </th>
                                            <th
                                                class="border border-teal-200 px-3 py-2 text-xs font-bold text-teal-700"
                                            >
                                                Value
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            v-for="row in dopplerTableRows(
                                                selectedRecord.doppler_data,
                                            )"
                                            :key="row.label"
                                            class="hover:bg-gray-50"
                                        >
                                            <td
                                                class="border border-gray-200 px-3 py-2 text-xs font-medium text-gray-600"
                                            >
                                                {{ row.label }}
                                            </td>
                                            <td
                                                class="border border-gray-200 px-3 py-2 font-bold text-gray-900"
                                            >
                                                {{ row.value || '—' }}
                                            </td>
                                            <td
                                                class="border border-gray-200 px-3 py-2 text-xs font-medium text-gray-600"
                                            >
                                                {{ row.label2 }}
                                            </td>
                                            <td
                                                class="border border-gray-200 px-3 py-2 font-bold text-gray-900"
                                            >
                                                {{ row.value2 || '—' }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div
                                v-if="selectedRecord.doppler_data.conclusion"
                                class="mt-4 rounded-xl border border-gray-200 bg-gray-50 p-4"
                            >
                                <p
                                    class="mb-1 text-[10px] font-extrabold tracking-widest text-gray-400 uppercase"
                                >
                                    Conclusion
                                </p>
                                <p class="text-sm font-bold text-gray-800">
                                    {{ selectedRecord.doppler_data.conclusion }}
                                </p>
                            </div>
                        </div>

                        <!-- MANUAL DRAFT DATA (Type 4) -->
                        <div
                            v-if="selectedRecord.manual_draft_data"
                            class="rounded-2xl border border-amber-100 bg-white p-6 shadow-sm"
                        >
                            <div class="mb-5 flex items-center justify-between">
                                <h3
                                    class="flex items-center text-sm font-extrabold tracking-widest text-amber-700 uppercase"
                                >
                                    <span
                                        class="mr-2 rounded bg-amber-100 px-2 py-0.5 text-amber-700"
                                        >Type 4</span
                                    >
                                    Manual Draft
                                </h3>
                                <button
                                    @click="
                                        downloadServerPdf(
                                            'manual_draft',
                                            selectedRecord,
                                        )
                                    "
                                    class="flex items-center gap-1.5 rounded-lg bg-amber-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-amber-700"
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
                                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                                        />
                                    </svg>
                                    Download PDF
                                </button>
                            </div>

                            <div
                                class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2"
                            >
                                <div
                                    class="rounded-xl border border-gray-200 bg-gray-50 p-4"
                                >
                                    <p
                                        class="mb-1 text-[10px] font-extrabold tracking-widest text-gray-400 uppercase"
                                    >
                                        Requesting Physician
                                    </p>
                                    <p class="text-sm font-bold text-gray-800">
                                        {{
                                            selectedRecord.manual_draft_data
                                                .requesting_physician || '—'
                                        }}
                                    </p>
                                </div>
                                <div
                                    class="rounded-xl border border-gray-200 bg-gray-50 p-4"
                                >
                                    <p
                                        class="mb-1 text-[10px] font-extrabold tracking-widest text-gray-400 uppercase"
                                    >
                                        Title
                                    </p>
                                    <p class="text-sm font-bold text-gray-800">
                                        {{
                                            selectedRecord.manual_draft_data
                                                .title || '—'
                                        }}
                                    </p>
                                </div>
                            </div>

                            <div
                                v-if="
                                    selectedRecord.manual_draft_data.images
                                        ?.length
                                "
                                class="mb-4"
                            >
                                <p
                                    class="mb-2 text-[10px] font-extrabold tracking-widest text-gray-400 uppercase"
                                >
                                    Attached Scans
                                </p>
                                <div
                                    class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4"
                                >
                                    <a
                                        v-for="(
                                            img, idx
                                        ) in selectedRecord.manual_draft_data
                                            .images"
                                        :key="idx"
                                        :href="img.url"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="overflow-hidden rounded-lg border border-gray-200 bg-white"
                                    >
                                        <img
                                            :src="img.url"
                                            :alt="img.name"
                                            class="h-28 w-full bg-gray-50 object-contain"
                                            loading="lazy"
                                        />
                                    </a>
                                </div>
                            </div>

                            <div
                                v-if="selectedRecord.manual_draft_data.draft"
                                class="mb-4 rounded-xl border border-gray-200 bg-gray-50 p-4"
                            >
                                <p
                                    class="mb-1 text-[10px] font-extrabold tracking-widest text-gray-400 uppercase"
                                >
                                    Draft
                                </p>
                                <p
                                    class="text-sm font-medium whitespace-pre-wrap text-gray-700"
                                >
                                    {{ selectedRecord.manual_draft_data.draft }}
                                </p>
                            </div>

                            <div
                                v-if="
                                    selectedRecord.manual_draft_data.conclusion
                                "
                                class="rounded-xl border border-gray-200 bg-gray-50 p-4"
                            >
                                <p
                                    class="mb-1 text-[10px] font-extrabold tracking-widest text-gray-400 uppercase"
                                >
                                    Conclusion
                                </p>
                                <p class="text-sm font-bold text-gray-800">
                                    {{
                                        selectedRecord.manual_draft_data
                                            .conclusion
                                    }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script setup lang="ts">
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted } from 'vue';
import AnalyticsPanel from '@/components/AnalyticsPanel.vue';
import Toast from '@/components/Toast.vue';
import { useToast } from '@/composables/useToast';
import type {
    Appointment,
    EchoData,
    DopplerData,
    TMTData,
    ManualDraftData,
} from '@/types/appointment';
import {
    buildEchoHTML,
    buildDopplerDocument,
    buildManualDraftDocument,
    buildTmtDocument,
    exportAsDocx,
} from '@/utils/docExport';

// ─────────────────────────────────────────────────────────────────────
// Types
// ─────────────────────────────────────────────────────────────────────

interface Patient {
    id: string | number;
    name: string;
    patient_id: string | number;
    email?: string;
}

interface ConsultationRecord extends Appointment {
    id: string | number;
    /**
     * The PDF export route binds an Appointment, not an Encounter, so this is
     * what must be sent - `id` is the encounter id and does not resolve.
     */
    appointment_id?: string | number | null;
    date: string;
    time: string;
    service: string;
    doctor?: string;
    symptoms?: string;
    vitals?: string;
    consultation_type?: string | string[];
    prescriptions?:
        | Array<{
              medicine: string;
              dosage: string;
              frequency: string;
              duration: string;
          }>
        | string;
    ai_summary?: string;
    echo_data?: Record<string, any>;
    doppler_data?: Record<string, any>;
    tmt_data?: Record<string, any>;
    manual_draft_data?: Record<string, any>;
}

type ExportType = 'echo' | 'doppler' | 'tmt' | 'manual_draft' | 'prescription';
type ExportFormat = 'docx' | 'pdf';

// ─────────────────────────────────────────────────────────────────────
// Props & composables
// ─────────────────────────────────────────────────────────────────────

const props = defineProps<{
    user: Patient;
    patient: Patient;
    history: ConsultationRecord[];
}>();

const { success: showSuccess, error: showError } = useToast();

const flashSuccess = computed(() => {
    const flash = usePage().props.flash as { success?: string } | undefined;

    return flash?.success ?? '';
});

// ─────────────────────────────────────────────────────────────────────
// State
// ─────────────────────────────────────────────────────────────────────

const isLoadingFeedback = ref(false);
const isLoadingAbstract = ref(false);
const selectedRecord = ref<ConsultationRecord | null>(null);

// Medication Adherence State
interface AdherenceDay {
    date: string;
    taken: number;
    expected: number;
    label: string;
    pct: number;
}
interface AdherenceMed {
    medicine: string;
    dosage: string;
    frequency: string;
    adherence_rate: number;
    total_expected: number;
    total_taken: number;
    daily: AdherenceDay[];
}
const adherenceMeds = ref<AdherenceMed[]>([]);
const adherenceLoading = ref(false);

function getDay(daily: AdherenceDay[], week: number, dayIdx: number): AdherenceDay {
    const index = week * 7 + dayIdx;

    return daily[index] || { date: '', taken: 0, expected: 0, label: '', pct: 0 };
}

function adherenceColor(pct: number): string {
    if (pct >= 80) {
return 'bg-emerald-400';
}

    if (pct >= 50) {
return 'bg-amber-300';
}

    return 'bg-red-300';
}

function hasDay(daily: AdherenceDay[], week: number, dayIdx: number): boolean {
    const index = week * 7 + dayIdx;

    return index < daily.length;
}

async function fetchAdherence(): Promise<void> {
    if (!props.patient?.id) {
return;
}

    adherenceLoading.value = true;

    try {
        const res = await fetch(`/doctor/patients/${props.patient.id}/medication-adherence`, { credentials: 'same-origin' });

        if (!res.ok) {
 adherenceMeds.value = [];

 return; 
}

        const data = await res.json();
        adherenceMeds.value = (data.medications ?? []).map((m: AdherenceMed) => ({
            ...m,
            daily: m.daily.slice(-28),
        }));
    } catch {
 adherenceMeds.value = []; 
} finally {
 adherenceLoading.value = false; 
}
}

onMounted(() => {
 fetchAdherence(); 
});

// ─────────────────────────────────────────────────────────────────────
// Detail view
// ─────────────────────────────────────────────────────────────────────

function openDetailView(record: ConsultationRecord): void {
    selectedRecord.value = record;
    document.body.style.overflow = 'hidden';
}

function closeDetailView(): void {
    selectedRecord.value = null;
    document.body.style.overflow = '';
}

// ─────────────────────────────────────────────────────────────────────
// Computed helpers
// ─────────────────────────────────────────────────────────────────────

const latestPrescriptions = computed(() => {
    if (props.history.length > 0 && props.history[0].prescriptions) {
        try {
            return typeof props.history[0].prescriptions === 'string'
                ? JSON.parse(props.history[0].prescriptions)
                : Array.isArray(props.history[0].prescriptions)
                  ? props.history[0].prescriptions
                  : [];
        } catch {
            showError('Error loading prescriptions');

            return [];
        }
    }

    return [];
});

const latestAiSummary = computed(() =>
    props.history.length > 0 && props.history[0].ai_summary
        ? props.history[0].ai_summary
        : null,
);

function parsedPrescriptions(record: ConsultationRecord): Array<{
    medicine: string;
    dosage: string;
    frequency: string;
    duration: string | number;
}> {
    if (!record.prescriptions) {
        return [];
    }

    try {
        const parsed =
            typeof record.prescriptions === 'string'
                ? JSON.parse(record.prescriptions)
                : record.prescriptions;

        return Array.isArray(parsed)
            ? parsed.filter((p: any) => p.medicine)
            : [];
    } catch {
        return [];
    }
}

/**
 * Builds 2-column pairs of Echo data for the detail table.
 */
function echoTableRows(
    echo: Record<string, any>,
): Array<{ label: string; value: string; label2: string; value2: string }> {
    return [
        {
            label: 'Requesting Physician',
            value: echo.requesting_physician,
            label2: 'File No',
            value2: echo.file_no,
        },
        { label: 'Age', value: echo.age, label2: 'Sex', value2: echo.sex },
        {
            label: 'Height',
            value: echo.height ? echo.height + ' cm' : '',
            label2: 'Weight',
            value2: echo.weight ? echo.weight + ' kg' : '',
        },
        {
            label: 'BSA',
            value: echo.bsa,
            label2: 'Heart Rate',
            value2: echo.hr ? echo.hr + ' bpm' : '',
        },
        {
            label: '─ Mitral EF',
            value: echo.mitral_ef,
            label2: '─ IVS Diastolic',
            value2: echo.ivs_dia,
        },
        {
            label: '─ Mitral DE',
            value: echo.mitral_de,
            label2: '─ IVS Systolic',
            value2: echo.ivs_sys,
        },
        {
            label: '─ Mitral EPSS',
            value: echo.mitral_epss,
            label2: '─ IVS Excursion',
            value2: echo.ivs_exc,
        },
        {
            label: '─ Aortic Root',
            value: echo.aortic_root,
            label2: '─ LVPW Diastolic',
            value2: echo.lvpw_dia,
        },
        {
            label: '─ Aortic Opening',
            value: echo.aortic_valve,
            label2: '─ LVPW Systolic',
            value2: echo.lvpw_sys,
        },
        {
            label: '─ LV Diastolic',
            value: echo.lv_dia,
            label2: '─ LV Systolic',
            value2: echo.lv_sys,
        },
        {
            label: '─ LV LVEDV',
            value: echo.lv_lvedv,
            label2: '─ LV LVESV',
            value2: echo.lv_lvesv,
        },
        {
            label: '─ Ejection Fraction',
            value: echo.lv_ef ? echo.lv_ef + '%' : '',
            label2: '─ Fractional Shortening',
            value2: echo.lv_fs ? echo.lv_fs + '%' : '',
        },
        {
            label: '─ LV Mass',
            value: echo.lv_mass,
            label2: '─ Simpsons',
            value2: echo.simpsons,
        },
        {
            label: '─ LA AP',
            value: echo.la_ap,
            label2: '─ RA RL',
            value2: echo.ra_rl,
        },
        {
            label: '─ RV AP',
            value: echo.rv_ap,
            label2: '─ RV RL',
            value2: echo.rv_rl,
        },
        {
            label: '─ TAPSE',
            value: echo.tapse,
            label2: '─ Tricuspid Valve',
            value2: echo.tricuspid_valve,
        },
        {
            label: '─ Pericardium Ant.',
            value: echo.peri_ant,
            label2: '─ Pericardium Post.',
            value2: echo.peri_post,
        },
        {
            label: '─ RVOT',
            value: echo.rvot,
            label2: '─ LVOT',
            value2: echo.lvot,
        },
    ].filter((row) => row.value || row.value2);
}

/**
 * Builds 2-column pairs of Doppler data for the detail table.
 */
function dopplerTableRows(
    dop: Record<string, any>,
): Array<{ label: string; value: string; label2: string; value2: string }> {
    return [
        {
            label: 'MV Peak Vel E',
            value: dop.mv_peak_vel_e,
            label2: 'MV Peak Vel A',
            value2: dop.mv_peak_vel_a,
        },
        {
            label: 'MV Peak Grad E',
            value: dop.mv_peak_grad_e,
            label2: 'MV Peak Grad A',
            value2: dop.mv_peak_grad_a,
        },
        {
            label: 'MV IVRT',
            value: dop.mv_ivrt,
            label2: 'MV E/A Ratio',
            value2: dop.mv_ea_ratio,
        },
        {
            label: 'MV DecTime',
            value: dop.mv_dectime,
            label2: 'MV Area PHT',
            value2: dop.mv_area_pht,
        },
        {
            label: 'MV Mean Pressure',
            value: dop.mv_mean_pressure,
            label2: 'MV Regurg Frac',
            value2: dop.mv_regurg_frac,
        },
        {
            label: "TD Septal e'",
            value: dop.td_septal_e,
            label2: "TD Septal E/e'",
            value2: dop.td_septal_ee,
        },
        {
            label: "TD Lateral e'",
            value: dop.td_lateral_e,
            label2: "TD Lateral E/e'",
            value2: dop.td_lateral_ee,
        },
        {
            label: 'TV E Wave',
            value: dop.tv_e_wave,
            label2: 'TV A Wave',
            value2: dop.tv_a_wave,
        },
        {
            label: 'TV Peak TR',
            value: dop.tv_peak_tr,
            label2: 'TV Systolic PAP',
            value2: dop.tv_sys_pap,
        },
        {
            label: 'AV Peak Vel (pre)',
            value: dop.av_peak_vel_before,
            label2: 'AV Peak Vel (post)',
            value2: dop.av_peak_vel_after,
        },
        {
            label: 'AV Press Grad (pre)',
            value: dop.av_press_grad_before,
            label2: 'AV Press Grad (post)',
            value2: dop.av_press_grad_after,
        },
        {
            label: 'AV Area',
            value: dop.av_area,
            label2: 'AV Mean Gradient',
            value2: dop.av_mean_grad,
        },
        {
            label: 'AV AR PHT',
            value: dop.av_ar_pht,
            label2: 'AV AR Jet',
            value2: dop.av_ar_jet,
        },
        {
            label: 'PV Accel Time',
            value: dop.pv_accel_time,
            label2: 'PV Press Grad',
            value2: dop.pv_press_grad,
        },
        {
            label: 'PV Systolic PAP',
            value: dop.pv_sys_pap,
            label2: 'PV RVOT VTI',
            value2: dop.pv_rvot_vti,
        },
        {
            label: 'Qp/Qs',
            value: dop.qp_qs,
            label2: 'PR Jet',
            value2: dop.pv_pr_jet,
        },
    ].filter((row) => row.value || row.value2);
}

// ─────────────────────────────────────────────────────────────────────
// Patient ID formatter
// ─────────────────────────────────────────────────────────────────────

function formatPatientId(): string {
    return `PT-${props.patient.id}`;
}

// ─────────────────────────────────────────────────────────────────────
// AI Abstract
// ─────────────────────────────────────────────────────────────────────

const abstractForm = useForm({});
const feedbackForm = useForm({ text: '', feedback: '' });

function generateAiAbstract(): void {
    if (isLoadingAbstract.value) {
        return;
    }

    isLoadingAbstract.value = true;
    abstractForm.post(`/api/ai/abstract/${props.patient.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            showSuccess('AI abstract generated successfully!');
            isLoadingAbstract.value = false;
        },
        onError: (errors: Record<string, string>) => {
            showError(
                errors.ai || errors.ai_error || 'Failed to generate abstract',
            );
            isLoadingAbstract.value = false;
        },
    });
}

function sendFeedback(type: 'like' | 'dislike'): void {
    if (isLoadingFeedback.value || !latestAiSummary.value) {
        return;
    }

    isLoadingFeedback.value = true;
    feedbackForm.text = latestAiSummary.value;
    feedbackForm.feedback = type;
    feedbackForm.post(`/api/ai/feedback/${props.patient.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            showSuccess(`Feedback submitted!`);
            isLoadingFeedback.value = false;
        },
        onError: (errors: Record<string, string>) => {
            showError(errors.feedback || 'Failed to submit feedback');
            isLoadingFeedback.value = false;
        },
    });
}

// ─────────────────────────────────────────────────────────────────────
// Server-side PDF download (spatie/laravel-pdf)
// ─────────────────────────────────────────────────────────────────────

function downloadServerPdf(type: ExportType, record: ConsultationRecord): void {
    // The export route is bound to an Appointment. Sending the encounter id
    // (record.id) resolved to a non-existent appointment and 404'd.
    const appointmentId = record.appointment_id;

    if (!appointmentId) {
        showError(
            'This consultation is not linked to an appointment, so the document cannot be downloaded.',
        );

        return;
    }

    window.open(`/doctor/pdf/${appointmentId}/${type}`, '_blank');
}

// ─────────────────────────────────────────────────────────────────────
// Client-side DOCX export
// PDF uses server route above.
// ─────────────────────────────────────────────────────────────────────

function doExport(
    type: ExportType,
    format: ExportFormat,
    record: ConsultationRecord,
): void {
    const name = props.patient.name;
    const date = record.date;
    const doctor = record.doctor ?? '';
    const slug = name.replace(/\s+/g, '_');

    // PDF → always use server-side PDF (laravel-pdf)
    if (format === 'pdf') {
        downloadServerPdf(type, record);

        return;
    }

    let bodyHtml = '';
    let filename = '';

    try {
        switch (type) {
            case 'echo':
                if (!record.echo_data) {
                    showError('No Echocardiogram data for this record.');

                    return;
                }

                bodyHtml = buildEchoHTML(record.echo_data as EchoData, name, date, doctor);
                filename = `Echo_${slug}_${date}`;
                break;
            case 'doppler':
                if (!record.doppler_data) {
                    showError('No Doppler data for this record.');

                    return;
                }

                bodyHtml = buildDopplerDocument(
                    record.doppler_data as DopplerData,
                    record.echo_data as EchoData,
                    name,
                    date,
                );
                filename = `Doppler_${slug}_${date}`;
                break;
            case 'tmt':
                if (!record.tmt_data) {
                    showError('No TMT data for this record.');

                    return;
                }

                bodyHtml = buildTmtDocument(
                    record.tmt_data as TMTData,
                    name,
                    (record.echo_data as EchoData)?.sex ?? '',
                    date,
                );
                filename = `TMT_${slug}_${date}`;
                break;
            case 'manual_draft':
                if (!record.manual_draft_data) {
                    showError('No Manual Draft data for this record.');

                    return;
                }

                bodyHtml = buildManualDraftDocument(
                    record.manual_draft_data as unknown as ManualDraftData,
                    name,
                    date,
                    doctor,
                );
                filename = `Manual_Draft_${slug}_${date}`;
                break;
            default:
                showError('Invalid export type');

                return;
        }

        if (!bodyHtml) {
            showError('Failed to generate document content');

            return;
        }

        switch (format) {
            case 'docx':
                exportAsDocx(bodyHtml, filename);
                showSuccess(`${type.toUpperCase()} exported as DOCX`);
                break;
            default:
                showError('Invalid export format');

                return;
        }
    } catch (error) {
        console.error(`Export error (${type}/${format}):`, error);
        showError(`Failed to export ${type.toUpperCase()} — please try again`);
    }
}
</script>

<style scoped>
@keyframes fadeSlideIn {
    from {
        opacity: 0;
        transform: translateY(12px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
