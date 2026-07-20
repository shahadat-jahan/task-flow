import { ref } from 'vue';

export interface TaskFormTask {
    id: number;
    title: string;
    description: string | null;
    status: string;
    priority: string;
    due_date: string | null;
    assignee_id: number | null;
    project_id: number | null;
    tags: { id: number; name: string; color: string }[];
}

// Module-scoped so both the AppLayout top-bar button and the page-level modal
// can drive the same dialog without an event bus.
const isOpen = ref(false);
const editingTask = ref<TaskFormTask | null>(null);

export function useTaskModal() {
    return {
        isOpen,
        editingTask,
        openCreate: () => {
            editingTask.value = null;
            isOpen.value = true;
        },
        openEdit: (task: TaskFormTask) => {
            editingTask.value = task;
            isOpen.value = true;
        },
        close: () => {
            isOpen.value = false;
            editingTask.value = null;
        },
    };
}
