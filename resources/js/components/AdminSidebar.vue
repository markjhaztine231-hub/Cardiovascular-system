<template>
    <aside class="z-20 flex w-64 shrink-0 flex-col bg-[#0f172a] text-slate-300 shadow-2xl">
        <div class="flex h-20 items-center border-b border-slate-800 bg-[#020617] px-6">
            <div class="mr-3 rounded-lg bg-indigo-500 p-2 text-xl font-bold text-white shadow-md shadow-indigo-500/20">⚙</div>
            <div>
                <h1 class="text-lg leading-tight font-bold tracking-wide text-white">CardioCare</h1>
                <p class="text-[10px] tracking-widest text-indigo-400 uppercase">Admin Console</p>
            </div>
        </div>

        <nav class="flex-1 overflow-y-auto py-6">
            <div class="mb-2 px-6 text-xs font-semibold tracking-wider text-slate-500 uppercase">System Management</div>
            <ul class="space-y-1">
                <li>
                    <Link href="/admin/dashboard"
                        class="flex items-center px-6 py-3 font-medium transition-colors"
                        :class="isActive('/admin/dashboard') ? 'border-l-4 border-indigo-500 bg-indigo-600/10 text-indigo-100' : 'border-l-4 border-transparent text-slate-400 hover:bg-slate-800 hover:text-white'">
                        <svg class="mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" /></svg>
                        Dashboard
                    </Link>
                </li>
                <li>
                    <Link href="/admin/users-directory"
                        class="flex items-center px-6 py-3 font-medium transition-colors"
                        :class="isActive('/admin/users-directory') ? 'border-l-4 border-indigo-500 bg-indigo-600/10 text-indigo-100' : 'border-l-4 border-transparent text-slate-400 hover:bg-slate-800 hover:text-white'">
                        <svg class="mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                        Users Directory
                    </Link>
                </li>
                <li>
                    <Link href="/admin/create-account"
                        class="flex items-center px-6 py-3 font-medium transition-colors"
                        :class="isActive('/admin/create-account') ? 'border-l-4 border-indigo-500 bg-indigo-600/10 text-indigo-100' : 'border-l-4 border-transparent text-slate-400 hover:bg-slate-800 hover:text-white'">
                        <svg class="mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" /></svg>
                        Create Account
                    </Link>
                </li>
                <li>
                    <Link href="/admin/audit-log"
                        class="flex items-center px-6 py-3 font-medium transition-colors"
                        :class="isActive('/admin/audit-log') ? 'border-l-4 border-indigo-500 bg-indigo-600/10 text-indigo-100' : 'border-l-4 border-transparent text-slate-400 hover:bg-slate-800 hover:text-white'">
                        <svg class="mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                        Audit Log
                    </Link>
                </li>
                <li>
                    <Link href="/admin/reports/practice"
                        class="flex items-center px-6 py-3 font-medium transition-colors"
                        :class="isActive('/admin/reports/practice') ? 'border-l-4 border-indigo-500 bg-indigo-600/10 text-indigo-100' : 'border-l-4 border-transparent text-slate-400 hover:bg-slate-800 hover:text-white'">
                        <svg class="mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6m4 6V5m4 14v-9M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                        Practice Report
                    </Link>
                </li>
            </ul>
        </nav>

        <div class="space-y-1 border-t border-slate-800 p-4">
            <Link href="/profile" class="flex w-full items-center rounded-lg px-4 py-2 text-sm font-medium text-slate-400 transition-colors hover:bg-slate-800 hover:text-white">
                <svg class="mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                My Profile
            </Link>
            <Link href="/logout" method="post" as="button" class="flex w-full items-center rounded-lg px-4 py-2 text-sm font-medium text-slate-400 transition-colors hover:bg-slate-800 hover:text-white">
                <svg class="mr-3 h-5 w-5 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                Secure Sign Out
            </Link>
        </div>
    </aside>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

const isActive = (path: string): boolean => {
    return window.location.pathname === path;
};
</script>
