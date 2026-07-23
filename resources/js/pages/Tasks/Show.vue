<script setup lang="ts">
import { useForm, router, usePage, Head } from '@inertiajs/vue3';
import { MessageSquare, Paperclip, Trash2, PencilLine, Calendar } from '@lucide/vue';
import { computed, ref, watch } from 'vue';

import InputError from '@/components/InputError.vue';
import TaskFormModal from '@/components/TaskFormModal.vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { useInitials } from '@/composables/useInitials';
import { useTaskModal } from '@/composables/useTaskModal';
import { priorityBadgeClass, statusBadgeClass } from '@/lib/taskBadges';
import { destroy as destroyAttachment } from '@/routes/attachments';
import { destroy as destroyComment } from '@/routes/comments';
import { store as storeAttachment } from '@/routes/my-tasks/attachments';
import { store as storeComment } from '@/routes/my-tasks/comments';
import { update as updateStatus } from '@/routes/my-tasks/status';

interface UserSummary {
    id: number;
    name: string;
    email: string;
}

interface ProjectSummary {
    id: number;
    name: string;
    color: string;
}

interface TagSummary {
    id: number;
    name: string;
    color: string;
}

interface CommentSummary {
    id: number;
    body: string;
    created_at: string;
    user: UserSummary;
}

interface AttachmentSummary {
    id: number;
    original_filename: string;
    stored_path: string;
    mime_type: string;
    size_bytes: number;
    created_at: string;
    uploader: UserSummary;
}

interface TaskDetail {
    id: number;
    title: string;
    description: string | null;
    status: string;
    priority: string;
    due_date: string | null;
    assignee: UserSummary | null;
    creator: UserSummary | null;
    project: ProjectSummary | null;
    tags: TagSummary[];
    comments: CommentSummary[] | null;
    attachments: AttachmentSummary[] | null;
}


const props = defineProps<{
    pageTitle: string;
    task: TaskDetail;
    canEdit: boolean;
    users: { id: number; name: string }[];
    projects: { id: number; name: string; color: string }[];
    tags: { id: number; name: string; color: string }[];
}>();

const { getInitials } = useInitials();
const { isOpen, editingTask, openEdit, close } = useTaskModal();
const page = usePage();
const currentUser = computed(() => page.props.auth.user as UserSummary | null);

function navigateToEdit(): void {
    openEdit({
        id: props.task.id,
        title: props.task.title,
        description: props.task.description,
        status: props.task.status,
        priority: props.task.priority,
        due_date: props.task.due_date,
        assignee_id: props.task.assignee?.id ?? null,
        project_id: props.task.project?.id ?? null,
        tags: props.task.tags,
    });
}

const controlClass =
    'border-input bg-transparent file:text-foreground placeholder:text-muted-foreground selection:bg-primary selection:text-primary-foreground dark:bg-input/30 flex w-full min-w-0 rounded-md border px-3 py-2 text-base shadow-xs transition-[color,box-shadow] outline-none disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:border-destructive aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40';

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

const statusLabel = computed(
    () =>
        statusOptions.find((option) => option.value === props.task.status)
            ?.label ?? props.task.status,
);
const priorityLabel = computed(
    () =>
        priorityOptions.find((option) => option.value === props.task.priority)
            ?.label ?? props.task.priority,
);

const comments = computed(() => props.task.comments ?? []);
const attachments = computed(() => props.task.attachments ?? []);

const selectedStatus = ref(props.task.status);
watch(
    () => props.task.status,
    (value) => {
        selectedStatus.value = value;
    },
);
watch(selectedStatus, (value) => {
    if (value && value !== props.task.status) {
        router.patch(
            updateStatus.url(props.task.id),
            { status: value },
            { preserveScroll: true },
        );
    }
});

function isOverdue(dueDate: string | null, status: string): boolean {
    if (!dueDate) {
        return false;
    }

    const today = new Date().toISOString().slice(0, 10);

    return dueDate < today && status !== 'done' && status !== 'cancelled';
}

function formatDate(date: string): string {
    return date.slice(0, 10);
}

function formatSize(bytes: number): string {
    if (bytes < 1024) {
        return `${bytes} B`;
    }

    const kb = bytes / 1024;

    if (kb < 1024) {
        return `${kb.toFixed(0)} KB`;
    }

    return `${(kb / 1024).toFixed(1)} MB`;
}

function timeAgo(date: string): string {
    const diff = Date.now() - new Date(date).getTime();
    const seconds = Math.floor(diff / 1000);

    if (seconds < 60) {
        return 'just now';
    }

    const minutes = Math.floor(seconds / 60);

    if (minutes < 60) {
        return `${minutes}m ago`;
    }

    const hours = Math.floor(minutes / 60);

    if (hours < 24) {
        return `${hours}h ago`;
    }

    const days = Math.floor(hours / 24);

    if (days < 30) {
        return `${days}d ago`;
    }

    return formatDate(date);
}

const commentForm = useForm({
    body: '',
});

function submitComment(): void {
    commentForm.post(storeComment.url(props.task.id), {
        preserveScroll: true,
        onSuccess: () => commentForm.reset(),
    });
}

function deleteComment(id: number): void {
    router.delete(destroyComment.url(id), { preserveScroll: true });
}

const attachmentForm = useForm<{ file: File | null }>({
    file: null,
});

function onFileChange(event: Event): void {
    const target = event.target as HTMLInputElement;
    attachmentForm.file = target.files?.[0] ?? null;
}

function submitAttachment(): void {
    attachmentForm.post(storeAttachment.url(props.task.id), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => attachmentForm.reset(),
    });
}

function deleteAttachment(id: number): void {
    router.delete(destroyAttachment.url(id), { preserveScroll: true });
}

</script>

<template>
    <Head :title="`${props.pageTitle}-${task.title}`"/>

    <div class="mx-auto max-w-4xl space-y-6 p-4 sm:p-6">
        <!-- Header card -->
        <div class="rounded-lg border border-border bg-white p-4 sm:p-6">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <h1 class="text-xl font-semibold tracking-tight sm:text-2xl">
                        {{ task.title }}
                    </h1>
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        <span
                            class="rounded-full px-2 py-0.5 text-xs font-medium"
                            :class="statusBadgeClass[task.status]"
                        >
                            {{ statusLabel }}
                        </span>
                        <span
                            class="rounded-full px-2 py-0.5 text-xs font-medium"
                            :class="priorityBadgeClass[task.priority]"
                        >
                            {{ priorityLabel }}
                        </span>
                    </div>
                </div>

                <div v-if="canEdit" class="flex items-center gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        type="button"
                        class="rounded-full"
                        @click="navigateToEdit"
                    >
                        <PencilLine class="size-4 mr-2" />
                        Edit
                    </Button>
                </div>
            </div>

            <div
                class="mt-4 grid grid-cols-2 gap-4 border-t border-border pt-4 sm:grid-cols-4"
            >
                <div>
                    <p class="text-xs text-muted-foreground">Due date</p>
                    <p
                        class="mt-1 text-sm font-medium"
                        :class="
                            isOverdue(task.due_date, task.status)
                                ? 'text-red-600'
                                : ''
                        "
                    >
                        <Calendar class="size-3.5 mr-2" />{{ task.due_date ? formatDate(task.due_date) : '—' }}
                    </p>
                </div>
                <div>
                    <p class="text-xs text-muted-foreground">Assignee</p>
                    <p class="mt-1 text-sm font-medium">
                        {{ task.assignee?.name ?? 'Unassigned' }}
                    </p>
                </div>
                <div>
                    <p class="text-xs text-muted-foreground">Project</p>
                    <p
                        class="mt-1 flex items-center gap-1.5 text-sm font-medium"
                    >
                        <span
                            v-if="task.project"
                            class="size-2 shrink-0 rounded-full"
                            :style="{ backgroundColor: task.project.color }"
                        />
                        {{ task.project?.name ?? 'No project' }}
                    </p>
                </div>
                <div>
                    <p class="text-xs text-muted-foreground">Created by</p>
                    <p class="mt-1 text-sm font-medium">
                        {{ task.creator?.name ?? '—' }}
                    </p>
                </div>
            </div>

            <div v-if="task.tags.length" class="mt-4 flex flex-wrap gap-2">
                <span
                    v-for="tag in task.tags"
                    :key="tag.id"
                    class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium"
                    :style="{
                        backgroundColor: tag.color + '20',
                        color: tag.color,
                    }"
                >
                    <span
                        class="size-2 rounded-full"
                        :style="{ backgroundColor: tag.color }"
                    />
                    {{ tag.name }}
                </span>
            </div>

            <div
                v-if="task.description"
                class="mt-4 border-t border-border pt-4"
            >
                <p class="text-xs text-muted-foreground">Description</p>
                <p class="mt-1 text-sm whitespace-pre-wrap">
                    {{ task.description }}
                </p>
            </div>
        </div>

        <!-- Comments -->
        <section class="rounded-lg border border-border bg-white p-4 sm:p-6">
            <h2 class="font-semibold">Comments</h2>

            <div v-if="comments.length" class="mt-4 space-y-4">
                <div
                    v-for="comment in comments"
                    :key="comment.id"
                    class="flex gap-3"
                >
                    <Avatar class="size-8 shrink-0">
                        <AvatarFallback>{{
                            getInitials(comment.user.name)
                        }}</AvatarFallback>
                    </Avatar>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-sm font-medium">
                                {{ comment.user.name }}
                            </p>
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-muted-foreground">
                                    {{ timeAgo(comment.created_at) }}
                                </span>
                                <button
                                    v-if="comment.user.id === currentUser?.id"
                                    type="button"
                                    class="text-muted-foreground transition-colors hover:text-red-600"
                                    aria-label="Delete comment"
                                    @click="deleteComment(comment.id)"
                                >
                                    <Trash2 class="size-4" />
                                </button>
                            </div>
                        </div>
                        <p class="mt-1 text-sm whitespace-pre-wrap">
                            {{ comment.body }}
                        </p>
                    </div>
                </div>
            </div>
            <p
                v-else
                class="mt-4 flex items-center gap-2 text-sm text-muted-foreground"
            >
                <MessageSquare class="size-4" />
                No comments yet
            </p>

            <form class="mt-4 space-y-2" @submit.prevent="submitComment">
                <textarea
                    v-model="commentForm.body"
                    rows="3"
                    placeholder="Add a comment…"
                    :class="controlClass"
                />
                <InputError :message="commentForm.errors.body" />
                <div class="flex justify-end">
                    <Button
                        type="submit"
                        :disabled="
                            commentForm.processing || !commentForm.body.trim()
                        "
                    >
                        Post comment
                    </Button>
                </div>
            </form>
        </section>

        <!-- Attachments -->
        <section class="rounded-lg border border-border bg-white p-4 sm:p-6">
            <h2 class="font-semibold">Attachments</h2>

            <div v-if="attachments.length" class="mt-4 space-y-2">
                <div
                    v-for="attachment in attachments"
                    :key="attachment.id"
                    class="flex items-center gap-3 rounded-md border border-border p-3"
                >
                    <Paperclip class="size-4 shrink-0 text-muted-foreground" />
                    <div class="min-w-0 flex-1">
                        <a
                            :href="attachment.stored_path"
                            target="_blank"
                            rel="noopener"
                            class="block truncate text-sm font-medium hover:underline"
                        >
                            {{ attachment.original_filename }}
                        </a>
                        <p class="text-xs text-muted-foreground">
                            {{ formatSize(attachment.size_bytes) }} ·
                            {{ attachment.uploader.name }} ·
                            {{ timeAgo(attachment.created_at) }}
                        </p>
                    </div>
                    <button
                        v-if="attachment.uploader.id === currentUser?.id"
                        type="button"
                        class="text-muted-foreground transition-colors hover:text-red-600"
                        aria-label="Delete attachment"
                        @click="deleteAttachment(attachment.id)"
                    >
                        <Trash2 class="size-4" />
                    </button>
                </div>
            </div>
            <p
                v-else
                class="mt-4 flex items-center gap-2 text-sm text-muted-foreground"
            >
                <Paperclip class="size-4" />
                No attachments yet
            </p>

            <form
                class="mt-4 flex flex-wrap items-center gap-3"
                @submit.prevent="submitAttachment"
            >
                <input
                    type="file"
                    class="block w-full text-sm text-muted-foreground file:mr-3 file:rounded-md file:border-0 file:bg-muted file:px-3 file:py-1.5 file:text-sm file:font-medium hover:file:bg-muted/70"
                    @change="onFileChange"
                />
                <InputError :message="attachmentForm.errors.file" />
                <Button
                    type="submit"
                    :disabled="
                        attachmentForm.processing || !attachmentForm.file
                    "
                >
                    Upload
                </Button>
            </form>
        </section>

        <TaskFormModal
            :open="isOpen"
            :task="editingTask"
            :users="users"
            :projects="projects"
            :tags="tags"
            @close="close"
        />
    </div>
</template>
