<script setup lang="ts">
import { useForm, router } from '@inertiajs/vue3';
import { Plus, X } from '@lucide/vue';
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
import type { TaskFormTask } from '@/composables/useTaskModal';
import { store as tagsStore } from '@/routes/tags';
import { destroy, store, update } from '@/routes/tasks';

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
];

// Native textarea / date inputs reuse the shadcn Input styling.
const controlClass =
    'border-input bg-transparent file:text-foreground placeholder:text-muted-foreground selection:bg-primary selection:text-primary-foreground dark:bg-input/30 flex w-full min-w-0 rounded-md border px-3 py-2 text-base shadow-xs transition-[color,box-shadow] outline-none disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:border-destructive aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40';

const form = useForm({
    title: '',
    description: '',
    status: 'todo',
    priority: 'medium',
    due_date: '',
    assignee_id: '',
    project_id: '',
    tags: [] as string[],
});

const confirmingDelete = ref(false);
const availableTags = ref<{ id: number; name: string; color: string }[]>(
    props.tags,
);
const newTag = ref('');
const addingTag = ref(false);
const tagError = ref('');

function resetForm(): void {
    form.reset();
    form.clearErrors();
    confirmingDelete.value = false;
    tagError.value = '';
    newTag.value = '';
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
            form.status = props.task.status;
            form.priority = props.task.priority;
            form.due_date = props.task.due_date
                ? props.task.due_date.slice(0, 10)
                : '';
            form.assignee_id = props.task.assignee_id
                ? String(props.task.assignee_id)
                : 'none';
            form.project_id = props.task.project_id
                ? String(props.task.project_id)
                : 'none';
            form.tags = props.task.tags.map((tag) => String(tag.id));
        } else {
            resetForm();
        }
    },
    { immediate: true },
);

async function addTag(): Promise<void> {
    const name = newTag.value.trim();

    if (!name) {
        return;
    }

    addingTag.value = true;
    tagError.value = '';

    try {
        const token = decodeURIComponent(
            document.cookie
                .split('; ')
                .find((cookie) => cookie.startsWith('XSRF-TOKEN='))
                ?.split('=')[1] ?? '',
        );

        const response = await fetch(tagsStore.url(), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-XSRF-TOKEN': token,
            },
            body: JSON.stringify({ name, color: '#6b7280' }),
        });

        if (!response.ok) {
            const data = (await response.json().catch(() => null)) as {
                message?: string;
            } | null;
            tagError.value = data?.message ?? 'Could not create tag.';
            addingTag.value = false;

            return;
        }

        const data = (await response.json()) as {
            tag: { id: number; name: string; color: string };
        };
        availableTags.value.push(data.tag);
        form.tags.push(String(data.tag.id));
        newTag.value = '';
    } finally {
        addingTag.value = false;
    }
}

function submit(): void {
    // 'none' is the Select sentinel for "unassigned"; the backend expects null/empty.
    if (form.assignee_id === 'none') {
        form.assignee_id = '';
    }

    if (form.project_id === 'none') {
        form.project_id = '';
    }

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
        <DialogContent class="sm:max-w-2xl">
            <button
                type="button"
                class="absolute end-4 top-4 rounded-sm opacity-70 transition-opacity hover:opacity-100 focus:outline-none"
                @click="emit('close')"
            >
                <X class="size-4" />
                <span class="sr-only">Close</span>
            </button>

            <DialogHeader class="space-y-1">
                <DialogTitle>
                    {{ task ? 'Edit Task' : 'Create New Task' }}
                </DialogTitle>
                <DialogDescription>
                    {{
                        task
                            ? 'Update the details of this task.'
                            : 'Add a new task to your workflow.'
                    }}
                </DialogDescription>
            </DialogHeader>

            <div class="grid gap-4 py-2">
                <!-- Title -->
                <div class="grid gap-2">
                    <Label for="title">Task Title</Label>
                    <Input id="title" v-model="form.title" />
                    <InputError :message="form.errors.title" />
                </div>

                <!-- Description -->
                <div class="grid gap-2">
                    <Label for="description">Description</Label>
                    <textarea
                        id="description"
                        v-model="form.description"
                        rows="3"
                        :class="controlClass"
                    />
                    <InputError :message="form.errors.description" />
                </div>

                <!-- Status + Priority -->
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="status">Status</Label>
                        <Select v-model="form.status">
                            <SelectTrigger id="status">
                                <SelectValue placeholder="Select status" />
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
                        <Label for="priority">Priority</Label>
                        <Select v-model="form.priority">
                            <SelectTrigger id="priority">
                                <SelectValue placeholder="Select priority" />
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
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="assignee_id">Assignee</Label>
                        <Select v-model="form.assignee_id">
                            <SelectTrigger id="assignee_id">
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
                        <Label for="due_date">Due Date</Label>
                        <input
                            id="due_date"
                            v-model="form.due_date"
                            type="date"
                            :class="controlClass"
                        />
                        <InputError :message="form.errors.due_date" />
                    </div>
                </div>

                <!-- Project + Tags -->
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="project_id">Project</Label>
                        <Select v-model="form.project_id">
                            <SelectTrigger id="project_id">
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
                        <Label for="tags">Tags</Label>
                        <Select id="tags" multiple v-model="form.tags">
                            <SelectTrigger>
                                <SelectValue placeholder="Select tags" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="tag in availableTags"
                                    :key="tag.id"
                                    :value="String(tag.id)"
                                >
                                    {{ tag.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <div class="flex items-center gap-2">
                            <Input
                                v-model="newTag"
                                placeholder="New tag name"
                                class="flex-1"
                                @keydown.enter.prevent="addTag"
                            />
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                :disabled="addingTag"
                                @click="addTag"
                            >
                                <Plus class="size-4" />
                                Add
                            </Button>
                        </div>
                        <p v-if="tagError" class="text-sm text-red-600">
                            {{ tagError }}
                        </p>
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
                                class="text-red-600 hover:text-red-600"
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
                                @click="confirmingDelete = false"
                            >
                                Cancel
                            </Button>
                            <Button
                                type="button"
                                variant="destructive"
                                @click="deleteTask"
                            >
                                Delete
                            </Button>
                        </template>
                    </template>

                    <Button :disabled="form.processing" @click="submit">
                        {{ task ? 'Save Changes' : 'Create Task' }}
                    </Button>
                </div>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
