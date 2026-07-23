<script setup lang="ts">
import { useForm, router, usePage, Head } from '@inertiajs/vue3';
import { MessageSquare, Paperclip, Trash2, PencilLine, Calendar } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import TaskFormModal from '@/components/TaskFormModal.vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { useInitials } from '@/composables/useInitials';
import { taskPriorityConfig, taskStatusConfig } from '@/composables/useTaskBadges';
import { useTaskModal } from '@/composables/useTaskModal';
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
    created_at: string;
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

// Deterministic avatar color pairs, keyed by user id — mirrors Figma's
// varied blue/green/purple avatar palette instead of one flat color.
const avatarPalette = [
    { bg: '#DBEAFE', fg: '#1447E6' }, // blue
    { bg: '#D0FAE5', fg: '#007A55' }, // green
    { bg: '#EDE9FE', fg: '#7008E7' }, // purple
];

function avatarStyle(userId: number) {
    const palette = avatarPalette[userId % avatarPalette.length];

    return { backgroundColor: palette.bg, color: palette.fg };
}

const activeTab = ref<'overview' | 'activity'>('overview');

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
    'border-input bg-transparent file:text-foreground placeholder:text-muted-foreground selection:bg-primary selection:text-primary-foreground dark:bg-input/30 flex w-full min-w-0 rounded-2xl border px-3.5 py-2.5 text-sm shadow-xs transition-[color,box-shadow] outline-none disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:border-destructive aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40';

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
    { value: 'urgent', label: 'Urgent' },
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
    <Head :title="`${props.pageTitle}-${task.title}`" />

    <div class="mx-auto flex max-w-[912px] flex-col gap-4 p-4 sm:flex-row sm:items-start sm:gap-5 sm:p-6">
        <!-- Main column -->
        <div class="flex min-w-0 flex-1 flex-col gap-5">
            <!-- Header card -->
            <div class="rounded-2xl border border-slate-200 bg-white p-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <span
                                class="rounded-[10px] bg-slate-100 px-2 py-0.5 font-mono text-xs text-slate-400"
                            >
                                TF-{{ String(task.id).padStart(3, '0') }}
                            </span>
                            <span
                                v-if="task.project"
                                class="rounded-[10px] border border-[#C6D2FF] bg-[#EEF2FF] px-2 py-0.5 text-xs font-medium text-[#432DD7]"
                            >
                                {{ task.project.name }}
                            </span>
                        </div>
                        <h1 class="mt-2 text-lg font-semibold tracking-tight text-slate-900 sm:text-[18px]">
                            {{ task.title }}
                        </h1>
                    </div>

                    <Button
                        v-if="canEdit"
                        variant="outline"
                        size="sm"
                        type="button"
                        class="shrink-0 rounded-2xl border-slate-200 text-slate-600 hover:bg-slate-50"
                        @click="navigateToEdit"
                    >
                        <PencilLine class="mr-1.5 size-3.5" />
                        Edit
                    </Button>
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-2">
                    <span
                        class="inline-flex items-center gap-1.5 rounded-[10px] border px-2 py-0.5 text-xs font-medium"
                        :class="taskStatusConfig[task.status as keyof typeof taskStatusConfig]?.badge"
                    >
                        <component :is="taskStatusConfig[task.status as keyof typeof taskStatusConfig]?.icon" class="size-3" />
                        {{ statusLabel }}
                    </span>
                    <span
                        class="inline-flex items-center gap-1.5 rounded-[10px] border px-2 py-0.5 text-xs font-medium"
                        :class="taskPriorityConfig[task.priority as keyof typeof taskPriorityConfig]?.badge"
                    >
                        <span class="size-1.5 shrink-0 rounded-full bg-current" />
                        {{ priorityLabel }}
                    </span>
                    <span
                        v-for="tag in task.tags"
                        :key="tag.id"
                        class="rounded-[10px] bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700"
                    >
                        {{ tag.name }}
                    </span>
                </div>

                <p v-if="task.description" class="mt-5 text-sm leading-[1.62] text-slate-600">
                    {{ task.description }}
                </p>

                <!-- Tabs -->
                <div class="mt-6 flex items-center gap-1 border-b border-slate-100">
                    <button
                        type="button"
                        class="border-b-2 px-4 py-2 text-sm font-medium capitalize transition-colors"
                        :class="
                            activeTab === 'overview'
                                ? 'border-[#4F39F6] text-[#4F39F6]'
                                : 'border-transparent text-slate-500 hover:text-slate-700'
                        "
                        @click="activeTab = 'overview'"
                    >
                        Overview
                    </button>
                    <button
                        type="button"
                        class="border-b-2 px-4 py-2 text-sm font-medium capitalize transition-colors"
                        :class="
                            activeTab === 'activity'
                                ? 'border-[#4F39F6] text-[#4F39F6]'
                                : 'border-transparent text-slate-500 hover:text-slate-700'
                        "
                        @click="activeTab = 'activity'"
                    >
                        Activity
                    </button>
                </div>

                <!-- Comments (inside overview tab, matching Figma) -->
                <div v-if="activeTab === 'overview'" class="mt-5">
                    <h2 class="text-xs font-semibold uppercase tracking-[0.6px] text-slate-500">
                        Comments ({{ comments.length }})
                    </h2>

                    <div v-if="comments.length" class="mt-3 space-y-4">
                        <div v-for="comment in comments" :key="comment.id" class="flex gap-3">
                            <Avatar class="size-8 shrink-0 ring-2 ring-white">
                                <AvatarFallback
                                    :style="avatarStyle(comment.user.id)"
                                    class="text-xs font-semibold"
                                >
                                    {{ getInitials(comment.user.name) }}
                                </AvatarFallback>
                            </Avatar>
                            <div class="min-w-0 flex-1 rounded-2xl bg-slate-50 p-3">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="text-xs font-semibold text-slate-800">
                                        {{ comment.user.name }}
                                    </p>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs text-slate-400">
                                            {{ timeAgo(comment.created_at) }}
                                        </span>
                                        <button
                                            v-if="comment.user.id === currentUser?.id"
                                            type="button"
                                            class="text-slate-400 transition-colors hover:text-red-600"
                                            aria-label="Delete comment"
                                            @click="deleteComment(comment.id)"
                                        >
                                            <Trash2 class="size-3.5" />
                                        </button>
                                    </div>
                                </div>
                                <p class="mt-1.5 text-sm leading-[1.62] text-slate-600 whitespace-pre-wrap">
                                    {{ comment.body }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <p v-else class="mt-3 flex items-center gap-2 text-sm text-slate-400">
                        <MessageSquare class="size-4" />
                        No comments yet
                    </p>

                    <!-- Composer -->
                    <div class="mt-5 flex gap-3">
                        <Avatar v-if="currentUser" class="size-8 shrink-0 ring-2 ring-white">
                            <AvatarFallback :style="avatarStyle(currentUser.id)" class="text-xs font-semibold">
                                {{ getInitials(currentUser.name) }}
                            </AvatarFallback>
                        </Avatar>
                        <form class="min-w-0 flex-1 space-y-2" @submit.prevent="submitComment">
                            <textarea
                                v-model="commentForm.body"
                                rows="1"
                                placeholder="Add a comment…"
                                :class="controlClass"
                            />
                            <InputError :message="commentForm.errors.body" />
                            <div class="flex justify-end">
                                <Button
                                    type="submit"
                                    size="sm"
                                    class="rounded-2xl"
                                    :disabled="commentForm.processing || !commentForm.body.trim()"
                                >
                                    Post comment
                                </Button>
                            </div>
                        </form>
                    </div>
                </div>

                <div v-else class="mt-5 text-sm text-slate-400">Activity log coming soon</div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="flex w-full flex-col gap-4 sm:w-[285px] sm:shrink-0">
            <!-- Details card -->
            <div class="rounded-2xl border border-slate-200 bg-white p-5">
                <h2 class="text-xs font-semibold uppercase tracking-[0.6px] text-slate-500">Details</h2>

                <div class="mt-4 flex flex-col divide-y divide-slate-100">
                    <div class="flex items-center justify-between py-3.5 first:pt-0">
                        <span class="text-xs text-slate-500">Assignee</span>
                        <div v-if="task.assignee" class="flex items-center gap-2">
                            <Avatar class="size-6 ring-2 ring-white">
                                <AvatarFallback :style="avatarStyle(task.assignee.id)" class="text-xs font-semibold">
                                    {{ getInitials(task.assignee.name) }}
                                </AvatarFallback>
                            </Avatar>
                            <span class="text-sm text-slate-700">{{ task.assignee.name }}</span>
                        </div>
                        <span v-else class="text-sm text-slate-400">Unassigned</span>
                    </div>

                    <div class="flex items-center justify-between py-3.5">
                        <span class="text-xs text-slate-500">Due Date</span>
                        <span
                            class="flex items-center gap-1.5 text-sm"
                            :class="isOverdue(task.due_date, task.status) ? 'text-red-600' : 'text-slate-700'"
                        >
                            <Calendar class="size-3.5 text-slate-400" />
                            {{ task.due_date ? formatDate(task.due_date) : '—' }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between py-3.5">
                        <span class="text-xs text-slate-500">Project</span>
                        <span
                            v-if="task.project"
                            class="rounded-[10px] border border-[#C6D2FF] bg-[#EEF2FF] px-2 py-0.5 text-xs font-medium text-[#432DD7]"
                        >
                            {{ task.project.name }}
                        </span>
                        <span v-else class="text-sm text-slate-400">No project</span>
                    </div>

                    <div class="flex items-center justify-between py-3.5">
                        <span class="text-xs text-slate-500">Created Date</span>
                        <span class="flex items-center gap-1.5 text-sm">
                            <Calendar class="size-3.5 text-slate-400" />
                            {{ task.created_at ? formatDate(task.created_at) : '—' }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between py-3.5">
                        <span class="text-xs text-slate-500">Comments</span>
                        <span class="flex items-center gap-1.5 text-sm text-slate-700">
                            <MessageSquare class="size-3.5 text-slate-400" />
                            {{ comments.length }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between py-3.5 last:pb-0">
                        <span class="text-xs text-slate-500">Attachments</span>
                        <span class="flex items-center gap-1.5 text-sm text-slate-700">
                            <Paperclip class="size-3.5 text-slate-400" />
                            {{ attachments.length }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Attachments card -->
            <div class="rounded-2xl border border-slate-200 bg-white p-5">
                <h2 class="text-xs font-semibold uppercase tracking-[0.6px] text-slate-500">Attachments</h2>

                <div class="mt-3 flex flex-col gap-1">
                    <div
                        v-for="attachment in attachments"
                        :key="attachment.id"
                        class="group flex items-center gap-3 rounded-xl px-1 py-2 hover:bg-slate-50"
                    >
                        <div class="flex size-8 shrink-0 items-center justify-center rounded-xl bg-[#EEF2FF]">
                            <Paperclip class="size-3.5 text-[#615FFF]" />
                        </div>
                        <a
                            :href="attachment.stored_path"
                            target="_blank"
                            rel="noopener"
                            class="min-w-0 flex-1"
                        >
                            <p class="truncate text-xs font-medium text-slate-700">
                                {{ attachment.original_filename }}
                            </p>
                            <p class="text-xs text-slate-400">{{ formatSize(attachment.size_bytes) }}</p>
                        </a>
                        <button
                            v-if="attachment.uploader.id === currentUser?.id"
                            type="button"
                            class="shrink-0 text-slate-300 opacity-0 transition-opacity hover:text-red-600 group-hover:opacity-100"
                            aria-label="Delete attachment"
                            @click="deleteAttachment(attachment.id)"
                        >
                            <Trash2 class="size-3.5" />
                        </button>
                    </div>

                    <p v-if="!attachments.length" class="flex items-center gap-2 py-2 text-sm text-slate-400">
                        <Paperclip class="size-4" />
                        No attachments yet
                    </p>
                </div>

                <form class="mt-3" @submit.prevent="submitAttachment">
                    <label
                        class="flex w-full cursor-pointer items-center justify-center gap-2 rounded-2xl border border-dashed border-slate-300 py-2 text-xs font-medium text-slate-500 transition-colors hover:border-slate-400 hover:text-slate-600"
                    >
                        <Paperclip class="size-3.5" />
                        Upload file
                        <input type="file" class="hidden" @change="onFileChange" />
                    </label>
                    <InputError :message="attachmentForm.errors.file" />
                    <div v-if="attachmentForm.file" class="mt-2 flex items-center justify-between gap-2">
                        <span class="truncate text-xs text-slate-500">{{ attachmentForm.file.name }}</span>
                        <Button
                            type="submit"
                            size="sm"
                            class="rounded-xl"
                            :disabled="attachmentForm.processing"
                        >
                            Upload
                        </Button>
                    </div>
                </form>
            </div>
        </div>

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
