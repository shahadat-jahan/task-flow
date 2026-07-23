<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import TaskIndexView from '@/pages/Tasks/Index.vue';

// Props passed from the DashboardController via Inertia
const props = defineProps<{
    pageTitle:string;
    subtitle: string;
    tasks: any;
    filters: any;
    projects: any;
    tags: any;
    users: any;
    summary: any;
    readOnly?: boolean;
    breadcrumbs?: any[];
}>();

defineOptions({
    layout: AppLayout,
});
</script>

<template>
    <Head :title="`${props.pageTitle}`"/>

    <TaskIndexView
        :tasks="props.tasks"
        :filters="props.filters"
        :projects="props.projects"
        :tags="props.tags"
        :users="props.users"
        :summary="props.summary"
        :read-only="true"
    />
</template>
