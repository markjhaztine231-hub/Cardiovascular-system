<template>
    <div
        class="flex h-screen bg-[#f4f6f9] font-sans text-[#2c3e50]"
        style="font-family: 'Segoe UI', sans-serif"
    >
        <!-- Fixed Left Sidebar -->
        <aside
            class="flex w-64 flex-col bg-[#0056b3] text-white transition-all duration-300 md:flex"
        >
            <div class="flex items-center gap-3 border-b border-blue-800 p-6">
                <div
                    class="flex h-8 w-8 items-center justify-center rounded-full bg-white font-bold text-[#0056b3]"
                >
                    ♥
                </div>
                <h2 class="text-lg leading-tight font-bold">
                    Cardiovascular<br />Care Center
                </h2>
            </div>

            <nav class="flex-1 space-y-2 p-4">
                <div
                    class="flex cursor-pointer items-center gap-3 rounded-lg bg-blue-800 px-4 py-2"
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
                            d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"
                        ></path>
                    </svg>
                    Dashboard
                </div>
                <!-- Additional dynamic links would go here based on role -->
            </nav>

            <div class="border-t border-blue-800 p-4">
                <LogoutButton>
                    <button
                        type="button"
                        class="flex items-center gap-2 font-semibold text-[#dc3545] transition-colors hover:text-red-400"
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
                                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
                            ></path>
                        </svg>
                        Secure Logout
                    </button>
                </LogoutButton>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="flex flex-1 flex-col overflow-hidden">
            <!-- Top Header -->
            <header
                class="flex h-16 shrink-0 items-center justify-between border-b bg-white px-6 shadow-sm"
            >
                <div class="font-bold text-[#0056b3] md:hidden">CCC</div>
                <div class="flex-1"></div>
                <!-- Profile Widget -->
                <div class="flex items-center gap-3">
                    <div class="text-right">
                        <p class="text-sm font-bold text-[#2c3e50]">
                            {{ userName }}
                        </p>
                        <p class="text-xs text-[#17a2b8] capitalize">
                            {{ role }}
                        </p>
                    </div>
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-full border border-[#17a2b8] bg-[#f4f6f9] font-bold text-[#0056b3]"
                    >
                        {{ userName.charAt(0) }}
                    </div>
                </div>
            </header>

            <!-- Scrollable Content -->
            <div class="flex-1 overflow-auto p-6">
                <slot />
            </div>
        </main>
    </div>
</template>

<script setup lang="ts">
import LogoutButton from '@/components/LogoutButton.vue';

defineProps({
    role: { type: String, default: 'User' },
    userName: { type: String, default: 'Jane Doe' },
});
</script>
