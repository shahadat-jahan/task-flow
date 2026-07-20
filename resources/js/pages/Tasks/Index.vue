<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

defineOptions({
    layout: {
        title: 'Tasks',
        subtitle: 'Manage and track your team’s tasks',
    },
});
</script>

<template>
    <Head title="Tasks" />

    <div class="p-4">
        <h1 class="text-xl font-semibold">Tasks</h1>
        <p class="text-sm text-muted-foreground">
            Task list, filters, and summary cards will be built in a later
            prompt.
        </p>
    </div>
</template>
