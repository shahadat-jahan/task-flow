<script setup lang="ts">
import { AlertTriangle } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

defineProps<{
    open: boolean;
    taskTitle: string;
}>();

const emit = defineEmits<{
    confirm: [];
    cancel: [];
}>();
</script>

<template>
    <Dialog
        :open="open"
        @update:open="(value: boolean) => !value && emit('cancel')"
    >
        <DialogContent>
            <DialogHeader class="space-y-3">
                <div
                    class="flex size-10 items-center justify-center rounded-full bg-red-50 text-red-600"
                >
                    <AlertTriangle class="size-5" />
                </div>
                <DialogTitle>Delete task</DialogTitle>
                <DialogDescription>
                    Are you sure you want to delete
                    <span class="font-medium text-foreground">{{
                        taskTitle
                    }}</span
                    >? This action cannot be undone.
                </DialogDescription>
            </DialogHeader>

            <DialogFooter class="gap-2 sm:gap-0">
                <Button variant="outline" @click="emit('cancel')">
                    Cancel
                </Button>
                <Button
                    variant="destructive"
                    data-test="confirm-delete-task-button"
                    @click="emit('confirm')"
                >
                    Delete task
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
