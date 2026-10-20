<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { LogOut } from 'lucide-vue-next';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';

const open = ref(false);
const submitting = ref(false);

const confirmLogout = () => {
    submitting.value = true;
    router.post('/logout');
};
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child>
            <slot />
        </DialogTrigger>
        <DialogContent class="sm:max-w-md">
            <DialogHeader class="space-y-3">
                <DialogTitle>Sign out of your account?</DialogTitle>
                <DialogDescription>
                    You will be signed out and returned to the sign-in page.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter class="gap-2">
                <DialogClose as-child>
                    <Button variant="secondary">Cancel</Button>
                </DialogClose>
                <Button
                    variant="destructive"
                    :disabled="submitting"
                    @click="confirmLogout"
                >
                    <LogOut v-if="!submitting" class="mr-2 h-4 w-4" />
                    {{ submitting ? 'Signing out…' : 'Sign out' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
