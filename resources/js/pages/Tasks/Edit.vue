<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Tasks',
                href: '/tasks',
            },
            {
                title: 'Edit task',
                href: '/tasks',
            },
        ],
    },
});
</script>

<template>
    <Head title="Edit task" />

    <div class="p-4">
        <h1 class="text-xl font-semibold">Edit task</h1>
        <p class="text-sm text-muted-foreground">
            The task edit form will be built in a later prompt.
        </p>
    </div>
</template>
