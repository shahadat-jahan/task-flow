<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

defineOptions({
    layout: {
        title: 'Task Details',
        subtitle: 'View and manage this task',
    },
});
</script>

<template>
    <Head title="Task details" />

    <div class="p-4">
        <h1 class="text-xl font-semibold">Task details</h1>
        <p class="text-sm text-muted-foreground">
            Task details (comments, attachments, status) will be built in a
            later prompt.
        </p>
    </div>
</template>
