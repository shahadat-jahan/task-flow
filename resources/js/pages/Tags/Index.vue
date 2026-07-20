<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Tags',
                href: '/tags',
            },
        ],
    },
});
</script>

<template>
    <Head title="Tags" />

    <div class="p-4">
        <h1 class="text-xl font-semibold">Tags</h1>
        <p class="text-sm text-muted-foreground">
            Tag management UI will be built in a later prompt.
        </p>
    </div>
</template>
