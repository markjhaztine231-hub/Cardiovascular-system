<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { LogOut, Settings } from 'lucide-vue-next';
import LogoutButton from '@/components/LogoutButton.vue';
import {
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import UserInfo from '@/components/UserInfo.vue';
import { edit as showProfile } from '@/routes/profile';
import type { User } from '@/types';

type Props = {
    user: User;
};

defineProps<Props>();
</script>

<template>
    <DropdownMenuLabel class="p-0 font-normal">
        <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
            <UserInfo :user="user" :show-email="true" />
        </div>
    </DropdownMenuLabel>
    <DropdownMenuSeparator />
    <DropdownMenuGroup>
        <DropdownMenuItem :as-child="true">
            <Link
                class="block w-full cursor-pointer"
                :href="showProfile()"
                prefetch
            >
                <Settings class="mr-2 h-4 w-4" />
                Settings
            </Link>
        </DropdownMenuItem>
    </DropdownMenuGroup>
    <DropdownMenuSeparator />
    <LogoutButton>
        <button
            type="button"
            class="flex w-full cursor-pointer items-center rounded-sm px-2 py-1.5 text-sm transition-colors outline-none hover:bg-accent hover:text-accent-foreground"
            data-test="logout-button"
        >
            <LogOut class="mr-2 h-4 w-4" />
            Log out
        </button>
    </LogoutButton>
</template>
