<script setup lang="ts">
import { onMounted } from 'vue';
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import OfflineIndicator from '@/components/OfflineIndicator.vue';
import type { BreadcrumbItem } from '@/types';
import { initOfflineListeners } from '@/utils/offlineSync';

onMounted(() => {
    initOfflineListeners();
});

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar />
        <AppContent variant="sidebar" class="overflow-x-hidden">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <slot />
            <OfflineIndicator />
        </AppContent>
    </AppShell>
</template>
