<template>
    <header
        class="flex h-20 shrink-0 items-center gap-3 border-b border-slate-200 bg-white px-4 sm:px-6 lg:px-8"
    >
        <button
            type="button"
            class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-slate-50 p-2.5 text-slate-500 transition hover:bg-indigo-50 hover:text-indigo-600"
            title="Toggle sidebar"
            aria-label="Toggle sidebar"
            @click="toggle"
        >
            <PanelLeft class="h-5 w-5" />
        </button>

        <div class="min-w-0">
            <p
                class="hidden text-[9px] font-black tracking-[0.22em] text-indigo-500 uppercase sm:block"
            >
                CardioCare admin
            </p>
            <h2
                class="truncate text-lg leading-tight font-black tracking-tight text-slate-800"
            >
                {{ title }}
            </h2>
        </div>

        <div class="ml-auto flex items-center gap-2">
            <form
                class="hidden md:block"
                role="search"
                @submit.prevent="searchUsers"
            >
                <div class="relative">
                    <Search
                        class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-slate-400"
                    />
                    <input
                        v-model="query"
                        type="search"
                        placeholder="Search users by name or email..."
                        class="w-56 rounded-xl border border-slate-200 bg-slate-50 py-2.5 pr-3 pl-9 text-sm font-medium text-slate-700 transition outline-none placeholder:text-slate-400 focus:border-indigo-400 focus:bg-white focus:ring-2 focus:ring-indigo-500/15 xl:w-72"
                        aria-label="Search users"
                    />
                </div>
            </form>

            <Link
                href="/profile"
                class="flex items-center gap-2 rounded-full border border-slate-200 bg-white p-1 pr-2.5 shadow-sm transition hover:border-indigo-200 hover:bg-indigo-50/50 sm:pr-3"
                :title="user?.name ?? 'Administrator'"
            >
                <span
                    class="flex h-8 w-8 items-center justify-center rounded-full bg-linear-to-br from-indigo-500 to-violet-600 text-xs font-black text-white"
                >
                    {{ initial }}
                </span>
                <span class="hidden min-w-0 leading-tight sm:block">
                    <span
                        class="block max-w-32 truncate text-xs font-bold text-slate-700"
                        >{{ user?.name ?? 'Administrator' }}</span
                    >
                    <span
                        class="block text-[9px] font-black tracking-wider text-slate-400 uppercase"
                        >Super admin</span
                    >
                </span>
                <Settings class="hidden h-4 w-4 text-slate-400 sm:block" />
            </Link>
        </div>
    </header>
</template>

<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { PanelLeft, Search, Settings } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { useSidebar } from '@/composables/useSidebar';

const props = defineProps<{
    title: string;
    user?: { name?: string };
}>();

const { toggle } = useSidebar('admin');

const query = ref('');

const initial = computed(() =>
    (props.user?.name ?? 'A').charAt(0).toUpperCase(),
);

const searchUsers = () => {
    const search = query.value.trim();

    router.get('/admin/users-directory', search ? { search } : {}, {
        preserveState: false,
    });
};
</script>
