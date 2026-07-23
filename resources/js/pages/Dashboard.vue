<script setup lang="ts">
import { taskPriorityConfig, taskProjectConfig, taskStatusConfig } from '@/composables/useTaskBadges';
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
}>();

defineOptions({
    layout: AppLayout,
});
</script>

<template>

    <TaskIndexView
        :page-title="props.pageTitle"
        :tasks="props.tasks"
        :filters="props.filters"
        :projects="props.projects"
        :tags="props.tags"
        :users="props.users"
        :summary="props.summary"
        :read-only="true"
    />
</template>
