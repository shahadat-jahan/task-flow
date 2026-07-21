<script setup lang="ts">
import TaskIndexView from '@/Pages/Tasks/Index.vue';
import { dashboard } from '@/routes';
import { Head } from '@inertiajs/vue3';

// Props passed from the DashboardController via Inertia
const props = defineProps<{
  tasks: any;
  filters: any;
  projects: any;
  tags: any;
  users: any;
  summary: any;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Dashboard" />

        <TaskIndexView
            :tasks="props.tasks"
            :filters="props.filters"
            :projects="props.projects"
            :tags="props.tags"
            :users="props.users"
            :summary="props.summary"
        />

</template>
