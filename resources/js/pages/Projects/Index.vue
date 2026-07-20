<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Projects',
                href: '/projects',
            },
        ],
    },
});
</script>

<template>
    <Head title="Projects" />

    <div class="p-4">
        <h1 class="text-xl font-semibold">Projects</h1>
        <p class="text-sm text-muted-foreground">
            Project management UI will be built in a later prompt.
        </p>
    </div>
</template>
