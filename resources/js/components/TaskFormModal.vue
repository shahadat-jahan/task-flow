<script setup lang="ts">
import { useForm, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useTaskModal } from '@/composables/useTaskModal';
import type { TaskFormTask } from '@/composables/useTaskModal';
import { destroy, store, update } from '@/routes/my-tasks';

// Initialize composable to get dynamic modal title
const { modalTitle, modalSubTitle } = useTaskModal();

const props = defineProps<{
    open: boolean;
    task?: TaskFormTask | null;
    users: { id: number; name: string }[];
    projects: { id: number; name: string; color: string }[];
    tags: { id: number; name: string; color: string }[];
}>();

const emit = defineEmits<{
    close: [];
}>();

const statusOptions: { value: string; label: string }[] = [
    { value: 'todo', label: 'Todo' },
    { value: 'in_progress', label: 'In Progress' },
    { value: 'in_review', label: 'In Review' },
    { value: 'done', label: 'Done' },
    { value: 'cancelled', label: 'Cancelled' },
];

const priorityOptions: { value: string; label: string }[] = [
    { value: 'low', label: 'Low' },
    { value: 'medium', label: 'Medium' },
    { value: 'high', label: 'High' },
    { value: 'urgent', label: 'Urgent'},
];

const form = useForm({
    title: '',
    description: '',
    status: '',
    priority: '',
    due_date: '',
    assignee_id: 'none',
    project_id: 'none',
    tags: [] as string[],
});

const confirmingDelete = ref(false);
const tagsInput = ref('');
const availableTags = ref<{ id: number; name: string; color: string }[]>(
    props.tags,
);

function resetForm(): void {
    form.reset();
    form.clearErrors();
    confirmingDelete.value = false;
    tagsInput.value = '';
}

watch(
    () => [props.open, props.task],
    () => {
        if (!props.open) {
            resetForm();

            return;
        }

        if (props.task) {
            form.title = props.task.title;
            form.description = props.task.description ?? '';
            form.status = props.task.status || 'todo';
            form.priority = props.task.priority || 'medium';

            form.due_date = props.task.due_date
                ? props.task.due_date.slice(0, 10)
                : '';
            form.assignee_id = props.task.assignee_id
                ? String(props.task.assignee_id)
                : 'none';
            form.project_id = props.task.project_id
                ? String(props.task.project_id)
                : 'none';
            form.tags = props.task.tags.map((tag) => tag.name);
            tagsInput.value = props.task.tags.map((tag) => tag.name).join(', ');
        } else {
            resetForm();
        }
    },
    { immediate: true },
);

function submit(): void {
    // 'none' is the Select sentinel for "unassigned"; the backend expects null/empty.
    if (form.assignee_id === 'none') {
        form.assignee_id = '';
    }

    if (form.project_id === 'none') {
        form.project_id = '';
    }

    // Convert comma-separated tag names to array of tag IDs
    const tagNames = tagsInput.value
        .split(',')
        .map(tag => tag.trim())
        .filter(tag => tag.length > 0);

    form.tags = tagNames
        .map((name) => {
            const found = availableTags.value.find(
                (t) => t.name.toLowerCase() === name.toLowerCase(),
            );

            return found ? String(found.id) : null;
        })
        .filter((id): id is string => id !== null);

    if (props.task) {
        form.put(update.url(props.task.id), {
            onSuccess: () => emit('close'),
        });

        return;
    }

    form.post(store.url(), {
        onSuccess: () => emit('close'),
    });
}

function deleteTask(): void {
    if (!props.task) {
        return;
    }

    router.delete(destroy.url(props.task.id), {
        onSuccess: () => emit('close'),
    });
}
</script>

<template>
    <Dialog
        :open="open"
        @update:open="(value: boolean) => !value && emit('close')"
    >
        <DialogContent :show-close-button="false" class="max-h-[90dvh] overflow-y-auto sm:max-w-2xl rounded-[16px] shadow-[0px_25px_50px_-12px_rgba(0,0,0,0.25)]">
            <button
                type="button"
                class="absolute end-4 top-4 flex size-[32px] items-center justify-center rounded-[12px] bg-[#F1F5F9] opacity-70 transition-opacity hover:opacity-100 focus:outline-none"
                @click="emit('close')"
            >
                <X class="size-4 text-[#45556C]" />
                <span class="sr-only">Close</span>
            </button>

            <DialogHeader class="flex h-[75px] flex-col justify-center border-b border-[#F1F5F9]">
                <DialogTitle class="text-base font-semibold text-[#0F172B]">
                    {{ modalTitle }}
                </DialogTitle>
                <DialogDescription class="text-xs font-normal text-[#62748E]">
                    {{ modalSubTitle }}
                </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="submit">
            <div class="grid gap-5 pb-5">
                <!-- Title -->
                <div class="grid gap-2">
                    <Label for="title" class="text-sm font-medium text-[#314158]">Task Title</Label>
                    <Input id="title" v-model="form.title" class="h-[42px] w-full rounded-[16px] border border-[#E2E8F0] px-[14px] text-sm text-[#90A1B9] placeholder:text-[#90A1B9]" placeholder="e.g. Redesign the onboarding flow" />
                    <InputError :message="form.errors.title" />
                </div>

                <!-- Description -->
                <div class="grid gap-2">
                    <Label for="description" class="text-sm font-medium text-[#314158]">Description</Label>
                    <textarea
                        id="description"
                        v-model="form.description"
                        rows="3"
                        class="h-[82px] w-full rounded-[16px] border border-[#E2E8F0] bg-white px-[14px] py-[10px] text-sm text-[#90A1B9] placeholder:text-[#90A1B9] focus:border-[#E2E8F0] focus:outline-none resize-none"
                        placeholder="Add a detailed description of this task..."
                    />
                    <InputError :message="form.errors.description" />
                </div>

                <!-- Status + Priority -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="grid gap-2">
                        <Label for="status" class="text-sm font-medium text-[#314158]">Status</Label>
                        <Select v-model="form.status">
                            <SelectTrigger id="status" class="h-[42px] w-full rounded-[16px] border border-[#E2E8F0] bg-white px-[14px]">
                                <SelectValue placeholder="Todo" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="option in statusOptions"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.status" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="priority" class="text-sm font-medium text-[#314158]">Priority</Label>
                        <Select v-model="form.priority">
                            <SelectTrigger id="priority" class="h-[42px] w-full rounded-[16px] border border-[#E2E8F0] bg-white px-[14px]">
                                <SelectValue placeholder="Medium" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="option in priorityOptions"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.priority" />
                    </div>
                </div>

                <!-- Assignee + Due Date -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="grid gap-2">
                        <Label for="assignee_id" class="text-sm font-medium text-[#314158]">Assignee</Label>
                        <Select v-model="form.assignee_id">
                            <SelectTrigger id="assignee_id" class="h-[42px] w-full rounded-[16px] border border-[#E2E8F0] bg-white px-[14px]">
                                <SelectValue placeholder="Unassigned" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none">Unassigned</SelectItem>
                                <SelectItem
                                    v-for="user in users"
                                    :key="user.id"
                                    :value="String(user.id)"
                                >
                                    {{ user.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.assignee_id" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="due_date" class="text-sm font-medium text-[#314158]">Due Date</Label>
                        <input
                            id="due_date"
                            v-model="form.due_date"
                            type="date"
                            class="h-[42px] w-full rounded-[16px] border border-[#E2E8F0] bg-white px-[14px] text-sm text-[#90A1B9]"
                        />
                        <InputError :message="form.errors.due_date" />
                    </div>
                </div>

                <!-- Project + Tags -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="grid gap-2">
                        <Label for="project_id" class="text-sm font-medium text-[#314158]">Project</Label>
                        <Select v-model="form.project_id">
                            <SelectTrigger id="project_id" class="h-[42px] w-full rounded-[16px] border border-[#E2E8F0] bg-white px-[14px]">
                                <SelectValue placeholder="No project" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none">No project</SelectItem>
                                <SelectItem
                                    v-for="project in projects"
                                    :key="project.id"
                                    :value="String(project.id)"
                                >
                                    {{ project.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.project_id" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="tags" class="text-sm font-medium text-[#314158]">Tags</Label>
                        <Input
                            id="tags"
                            v-model="tagsInput"
                            placeholder="Design, Frontend, Bug..."
                            class="h-[42px] rounded-[16px] border border-[#E2E8F0] px-[14px] text-sm text-[#90A1B9] placeholder:text-[#90A1B9]"
                        />
                        <InputError :message="form.errors.tags" />
                    </div>
                </div>
            </div>

            <DialogFooter class="gap-2 sm:justify-between sm:gap-0">
                <Button type="button" variant="outline" @click="emit('close')">
                    Cancel
                </Button>

                <div class="flex items-center gap-2">
                    <template v-if="task">
                        <template v-if="!confirmingDelete">
                            <Button
                                type="button"
                                variant="ghost"
                                class="text-sm font-medium text-red-600 hover:text-red-600"
                                @click="confirmingDelete = true"
                            >
                                Delete
                            </Button>
                        </template>
                        <template v-else>
                            <span class="text-sm text-muted-foreground"
                                >Really delete?</span
                            >
                            <Button
                                type="button"
                                variant="ghost"
                                class="text-sm font-medium text-[#45556C] hover:bg-gray-50"
                                @click="confirmingDelete = false"
                            >
                                Cancel
                            </Button>
                            <Button
                                type="button"
                                variant="destructive"
                                class="h-[36px] rounded-[16px] bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700"
                                @click="deleteTask"
                            >
                                Delete
                            </Button>
                        </template>
                    </template>

                    <Button type="submit" :disabled="form.processing" class="h-[36px] rounded-[16px] bg-[#4F39F6] px-5 py-2 text-sm font-medium text-white shadow-[0px_1px_3px_#C6D2FF,0px_1px_2px_-1px_#C6D2FF] hover:bg-[#4338CA]">
                        {{ task ? 'Save Changes' : 'Create Task' }}
                    </Button>
                </div>
            </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
