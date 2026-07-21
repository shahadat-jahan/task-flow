<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    AlertTriangle,
    CircleCheck,
    Clock,
    ListTodo,
    Pencil,
    Plus,
    Trash2,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import DeleteTaskDialog from '@/components/DeleteTaskDialog.vue';
import SummaryCard from '@/components/SummaryCard.vue';
import TaskFormModal from '@/components/TaskFormModal.vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useInitials } from '@/composables/useInitials';
import { useTaskModal } from '@/composables/useTaskModal';
import { statusBadgeClass, priorityBadgeClass } from '@/lib/taskBadges';
import { destroy, index, show } from '@/routes/tasks';

type Status = 'todo' | 'in_progress' | 'in_review' | 'done' | 'cancelled';
type Priority = 'low' | 'medium' | 'high';
type TrendDirection = 'up' | 'down' | 'neutral';

interface TaskTrend {
    value: string;
    direction: TrendDirection;
}

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

interface Task {
    id: number;
    title: string;
    description: string | null;
    status: Status;
    priority: Priority;
    due_date: string | null;
    assignee_id: number | null;
    project_id: number | null;
    assignee: UserSummary | null;
    project: ProjectSummary | null;
    tags: TagSummary[];
}

interface TaskSummary {
    total_tasks: number;
    by_status: Record<Status, number>;
    overdue_count: number;
    trends: {
        total_tasks: TaskTrend;
        completed: TaskTrend;
        in_progress: TaskTrend;
        overdue_count: TaskTrend;
    };
}

interface PaginatedTasks {
    data: Task[];
    links: { url: string | null; label: string; active: boolean }[];
}

const props = defineProps<{
    title?: string;
    tasks: PaginatedTasks;
    filters: {
        status: Status | '' | null;
        priority: Priority | '' | null;
        project_id: number | '' | null;
        tag_id: number | '' | null;
        search: string | null;
        sort: string | null;
        direction: string | null;
    };
    projects: ProjectSummary[];
    tags: TagSummary[];
    users: UserSummary[];
    summary: TaskSummary;
}>();

const { getInitials } = useInitials();
const { isOpen, editingTask, openEdit, openCreate, close } = useTaskModal();

const statusOptions: { value: Status; label: string }[] = [
    { value: 'todo', label: 'Todo' },
    { value: 'in_progress', label: 'In Progress' },
    { value: 'in_review', label: 'In Review' },
    { value: 'done', label: 'Done' },
    { value: 'cancelled', label: 'Cancelled' },
];

const priorityOptions: { value: Priority; label: string }[] = [
    { value: 'low', label: 'Low' },
    { value: 'medium', label: 'Medium' },
    { value: 'high', label: 'High' },
];

const statusLabel = (status: Status): string =>
    statusOptions.find((option) => option.value === status)?.label ?? status;

const today = new Date().toISOString().slice(0, 10);

const isOverdue = (task: Task): boolean =>
    task.due_date !== null &&
    task.due_date < today &&
    task.status !== 'done' &&
    task.status !== 'cancelled';

const formatDate = (date: string): string =>
    new Date(date).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
    });

const summaryCards = computed(() => [
    {
        label: 'Total Tasks',
        value: props.summary.total_tasks,
        icon: ListTodo,
        iconClass: 'bg-indigo-50 text-indigo-600',
        trend: props.summary.trends.total_tasks,
    },
    {
        label: 'Completed',
        value: props.summary.by_status.done ?? 0,
        icon: CircleCheck,
        iconClass: 'bg-emerald-50 text-emerald-600',
        trend: props.summary.trends.completed,
    },
    {
        label: 'In Progress',
        value: props.summary.by_status.in_progress ?? 0,
        icon: Clock,
        iconClass: 'bg-amber-50 text-amber-600',
        trend: props.summary.trends.in_progress,
    },
    {
        label: 'Overdue',
        value: props.summary.overdue_count,
        icon: AlertTriangle,
        iconClass: 'bg-red-50 text-red-600',
        trend: props.summary.trends.overdue_count,
    },
]);

const statusFilter = ref<string>(props.filters.status ?? 'all');
const priorityFilter = ref<string>(props.filters.priority ?? 'all');
const projectFilter = ref<string>(
    props.filters.project_id != null ? String(props.filters.project_id) : 'all',
);
const tagFilter = ref<string>(
    props.filters.tag_id != null ? String(props.filters.tag_id) : 'all',
);
const search = ref(props.filters.search ?? '');

const hasActiveFilters = computed(
    () =>
        statusFilter.value !== 'all' ||
        priorityFilter.value !== 'all' ||
        projectFilter.value !== 'all' ||
        tagFilter.value !== 'all' ||
        search.value.trim() !== '',
);

function applyFilters(): void {
    const params: Record<string, string> = {};

    if (statusFilter.value !== 'all') {
        params.status = statusFilter.value;
    }

    if (priorityFilter.value !== 'all') {
        params.priority = priorityFilter.value;
    }

    if (projectFilter.value !== 'all') {
        params.project_id = projectFilter.value;
    }

    if (tagFilter.value !== 'all') {
        params.tag_id = tagFilter.value;
    }

    if (search.value.trim() !== '') {
        params.search = search.value.trim();
    }

    router.get(window.location.pathname, params, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

// Select changes apply immediately; the search box is debounced.
watch([statusFilter, priorityFilter, projectFilter, tagFilter], applyFilters);

let searchTimer: ReturnType<typeof setTimeout> | undefined;
watch(search, () => {
    if (searchTimer) {
        clearTimeout(searchTimer);
    }

    searchTimer = setTimeout(() => applyFilters(), 300);
});

function resetFilters(): void {
    statusFilter.value = 'all';
    priorityFilter.value = 'all';
    projectFilter.value = 'all';
    tagFilter.value = 'all';
    search.value = '';
}

// Row selection is present but no bulk action is wired yet (out of scope).
const selectedIds = ref<Set<number>>(new Set());

const isSelected = (id: number): boolean => selectedIds.value.has(id);

function toggleSelected(id: number, checked: boolean): void {
    const next = new Set(selectedIds.value);

    if (checked) {
        next.add(id);
    } else {
        next.delete(id);
    }

    selectedIds.value = next;
}

const pendingDelete = ref<Task | null>(null);

function confirmDelete(): void {
    if (!pendingDelete.value) {
        return;
    }

    router.delete(destroy.url(pendingDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            pendingDelete.value = null;
        },
    });
}

function cancelDelete(): void {
    pendingDelete.value = null;
}

function openTask(task: Task): void {
    router.visit(show.url(task.id));
}
</script>

<template>
    <Head v-if="props.title" :title="props.title" />

    <div class="space-y-6">
        <!-- Summary cards -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <SummaryCard
                v-for="card in summaryCards"
                :key="card.label"
                :icon="card.icon"
                :label="card.label"
                :value="card.value"
                :icon-class="card.iconClass"
                :trend="card.trend"
            />
        </div>

        <!-- Filters -->
        <div
            class="flex flex-col gap-3 rounded-lg border border-border bg-white p-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="flex flex-1 flex-wrap items-center gap-3">
                <div class="relative w-full sm:w-64">
                    <Input
                        v-model="search"
                        type="search"
                        placeholder="Search tasks…"
                        class="w-full"
                    />
                </div>

                <Select v-model="statusFilter">
                    <SelectTrigger class="w-40">
                        <SelectValue placeholder="Status" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All statuses</SelectItem>
                        <SelectItem
                            v-for="option in statusOptions"
                            :key="option.value"
                            :value="option.value"
                        >
                            {{ option.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>

                <Select v-model="priorityFilter">
                    <SelectTrigger class="w-36">
                        <SelectValue placeholder="Priority" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All priorities</SelectItem>
                        <SelectItem
                            v-for="option in priorityOptions"
                            :key="option.value"
                            :value="option.value"
                        >
                            {{ option.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>

                <Select v-model="projectFilter">
                    <SelectTrigger class="w-44">
                        <SelectValue placeholder="Project" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All projects</SelectItem>
                        <SelectItem
                            v-for="project in projects"
                            :key="project.id"
                            :value="String(project.id)"
                        >
                            {{ project.name }}
                        </SelectItem>
                    </SelectContent>
                </Select>

                <Select v-model="tagFilter">
                    <SelectTrigger class="w-40">
                        <SelectValue placeholder="Tag" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All tags</SelectItem>
                        <SelectItem
                            v-for="tag in tags"
                            :key="tag.id"
                            :value="String(tag.id)"
                        >
                            {{ tag.name }}
                        </SelectItem>
                    </SelectContent>
                </Select>

                <Button
                    v-if="hasActiveFilters"
                    variant="ghost"
                    size="sm"
                    type="button"
                    @click="resetFilters"
                >
                    Clear
                </Button>
            </div>
        </div>

        <!-- Empty state -->
        <div
            v-if="tasks.data.length === 0"
            class="rounded-lg border border-dashed border-border bg-white p-8 text-center sm:p-12"
        >
            <template v-if="hasActiveFilters">
                <p class="text-lg font-medium">No tasks match your filters</p>
                <p class="mt-1 text-sm text-muted-foreground">
                    Try adjusting or clearing your filters.
                </p>
                <Button
                    class="mt-4"
                    variant="outline"
                    type="button"
                    @click="resetFilters"
                >
                    Clear filters
                </Button>
            </template>
            <template v-else>
                <p class="text-lg font-medium">No tasks found</p>
                <p class="mt-1 text-sm text-muted-foreground">
                    Tasks you create will appear here.
                </p>
                <Button class="mt-4" type="button" @click="openCreate()">
                    <Plus class="size-4" />
                    Create your first task
                </Button>
            </template>
        </div>

        <!-- Desktop table -->
        <div
            v-else
            class="hidden overflow-hidden rounded-lg border border-border bg-white md:block"
        >
            <table class="w-full text-sm">
                <thead
                    class="border-b border-border bg-muted/40 text-xs tracking-wide text-muted-foreground uppercase"
                >
                    <tr>
                        <th class="w-10 px-4 py-3">
                            <Checkbox :checked="false" disabled />
                        </th>
                        <th class="px-4 py-3 text-left font-medium">ID</th>
                        <th class="px-4 py-3 text-left font-medium">Task</th>
                        <th class="px-4 py-3 text-left font-medium">Status</th>
                        <th class="px-4 py-3 text-left font-medium">
                            Priority
                        </th>
                        <th class="px-4 py-3 text-left font-medium">
                            Assignee
                        </th>
                        <th class="px-4 py-3 text-left font-medium">
                            Due Date
                        </th>
                        <th class="w-24 px-4 py-3 text-right font-medium">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <tr
                        v-for="task in tasks.data"
                        :key="task.id"
                        class="cursor-pointer transition-colors hover:bg-muted/40"
                        @click="openTask(task)"
                    >
                        <td class="px-4 py-3" @click.stop>
                            <Checkbox
                                :checked="isSelected(task.id)"
                                @update:model-value="
                                    (value: boolean | 'indeterminate') =>
                                        toggleSelected(task.id, value === true)
                                "
                            />
                        </td>
                        <td
                            class="px-4 py-3 text-muted-foreground tabular-nums"
                        >
                            #{{ task.id }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="font-medium text-foreground">
                                {{ task.title }}
                            </div>
                            <div
                                class="mt-1 flex items-center gap-1.5 text-xs text-muted-foreground"
                            >
                                <span
                                    v-if="task.project"
                                    class="flex items-center gap-1.5"
                                >
                                    <span
                                        class="size-2 shrink-0 rounded-full"
                                        :style="{
                                            backgroundColor: task.project.color,
                                        }"
                                    />
                                    {{ task.project.name }}
                                </span>
                                <span
                                    v-for="tag in task.tags"
                                    :key="tag.id"
                                    class="rounded-full bg-muted px-2 py-0.5 text-muted-foreground"
                                >
                                    {{ tag.name }}
                                </span>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <span
                                class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium"
                                :class="statusBadgeClass[task.status]"
                            >
                                {{ statusLabel(task.status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <span
                                class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium"
                                :class="priorityBadgeClass[task.priority]"
                            >
                                {{ task.priority }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div
                                v-if="task.assignee"
                                class="flex items-center gap-2"
                            >
                                <Avatar class="size-7">
                                    <AvatarFallback class="text-xs">
                                        {{ getInitials(task.assignee.name) }}
                                    </AvatarFallback>
                                </Avatar>
                                <span class="truncate">{{
                                    task.assignee.name
                                }}</span>
                            </div>
                            <span v-else class="text-muted-foreground">—</span>
                        </td>
                        <td
                            class="px-4 py-3 tabular-nums"
                            :class="
                                isOverdue(task)
                                    ? 'font-medium text-red-600'
                                    : 'text-muted-foreground'
                            "
                        >
                            <span v-if="task.due_date">{{
                                formatDate(task.due_date)
                            }}</span>
                            <span v-else>—</span>
                        </td>
                        <td class="px-4 py-3" @click.stop>
                            <div class="flex items-center justify-end gap-1">
                                <button
                                    type="button"
                                    class="inline-flex size-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                                    :data-test="`edit-task-${task.id}-button`"
                                    @click="openEdit(task)"
                                >
                                    <Pencil class="size-4" />
                                </button>
                                <button
                                    type="button"
                                    class="inline-flex size-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-red-50 hover:text-red-600"
                                    :data-test="`delete-task-${task.id}-button`"
                                    @click="pendingDelete = task"
                                >
                                    <Trash2 class="size-4" />
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Mobile cards -->
        <div v-if="tasks.data.length > 0" class="space-y-3 md:hidden">
            <div
                v-for="task in tasks.data"
                :key="task.id"
                class="rounded-lg border border-border bg-white p-4"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="font-medium">{{ task.title }}</p>
                        <p class="mt-0.5 text-xs text-muted-foreground">
                            #{{ task.id }}
                            <template v-if="task.project">
                                · {{ task.project.name }}
                            </template>
                        </p>
                    </div>
                    <button
                        type="button"
                        class="inline-flex size-8 shrink-0 items-center justify-center rounded-md text-muted-foreground hover:bg-red-50 hover:text-red-600"
                        @click="pendingDelete = task"
                    >
                        <Trash2 class="size-4" />
                    </button>
                </div>

                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <span
                        class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium"
                        :class="statusBadgeClass[task.status]"
                    >
                        {{ statusLabel(task.status) }}
                    </span>
                    <span
                        class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium"
                        :class="priorityBadgeClass[task.priority]"
                    >
                        {{ task.priority }}
                    </span>
                    <span
                        v-if="task.due_date"
                        class="text-xs"
                        :class="
                            isOverdue(task)
                                ? 'font-medium text-red-600'
                                : 'text-muted-foreground'
                        "
                    >
                        {{ formatDate(task.due_date) }}
                    </span>
                    <span
                        v-for="tag in task.tags"
                        :key="tag.id"
                        class="rounded-full bg-muted px-2 py-0.5 text-xs text-muted-foreground"
                    >
                        {{ tag.name }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <nav
            v-if="tasks.links?.length"
            class="flex flex-wrap items-center gap-1"
        >
            <template v-for="(link, index) in tasks.links" :key="index">
                <span
                    v-if="!link.url"
                    class="px-3 py-1 text-sm text-muted-foreground"
                    v-html="link.label"
                />
                <Link
                    v-else
                    :href="link.url"
                    preserve-scroll
                    class="rounded-md px-3 py-1 text-sm transition-colors"
                    :class="
                        link.active
                            ? 'bg-primary font-medium text-primary-foreground'
                            : 'hover:bg-muted'
                    "
                >
                    <span v-html="link.label" />
                </Link>
            </template>
        </nav>

        <DeleteTaskDialog
            :open="pendingDelete !== null"
            :task-title="pendingDelete?.title ?? ''"
            @confirm="confirmDelete"
            @cancel="cancelDelete"
        />

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
