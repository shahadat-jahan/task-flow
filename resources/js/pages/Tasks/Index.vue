<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Activity,
    AlertCircle,
    Calendar,
    CircleCheck,
    MessageSquare,
    Paperclip,
    Plus,
    SquareCheck,
    SquarePen,
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
import { taskPriorityConfig,taskProjectConfig, taskStatusConfig } from '@/composables/useTaskBadges';
import { useTaskModal } from '@/composables/useTaskModal';
import { destroy, show } from '@/routes/my-tasks';

type Status = 'todo' | 'in_progress' | 'in_review' | 'done' | 'cancelled';
type Priority = 'low' | 'medium' | 'high' | 'urgent';
type TrendDirection = 'up' | 'down' | 'neutral';

interface TaskTrend {
    value: string;
    direction: TrendDirection;
    change: number;
    previous: number;
    current: number;
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
    creator: UserSummary | null;
    project: ProjectSummary | null;
    tags: TagSummary[];
    comments_count?: number;
    attachments_count?: number;
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
    total?: number;
    links: { url: string | null; label: string; active: boolean }[];
}

const props = defineProps<{
    pageTitle?: string;
    tasks: PaginatedTasks;
    filters: {
        status: Status | '' | null;
        priority: Priority | '' | null;
        project_id: number | '' | null;
        tag_id: number | '' | null;
        search: string | null;
        sort: string | null;
        direction: string | null;
        per_page: number | null;
    };
    projects: ProjectSummary[];
    tags: TagSummary[];
    users: UserSummary[];
    summary?: TaskSummary;
    readOnly?: boolean;
}>();

const { getInitials } = useInitials();
const { isOpen, editingTask, openEdit, openCreate, close } = useTaskModal();

const avatarColors = [
    'bg-purple-100 text-purple-700 border-purple-200',
    'bg-blue-100 text-blue-700 border-blue-200',
    'bg-emerald-100 text-emerald-700 border-emerald-200',
    'bg-rose-100 text-rose-700 border-rose-200',
    'bg-amber-100 text-amber-700 border-amber-200',
    'bg-indigo-100 text-indigo-700 border-indigo-200',
];

function getAvatarBg(name?: string | null): string {
    if (!name) {
        return avatarColors[0];
    }

    let hash = 0;

    for (let i = 0; i < name.length; i++) {
        hash = name.charCodeAt(i) + ((hash << 5) - hash);
    }

    return avatarColors[Math.abs(hash) % avatarColors.length];
}

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
    { value: 'urgent', label: 'Urgent' },
];

const statusLabel = (status: Status): string =>
    statusOptions.find((option) => option.value === status)?.label ?? status;

const today = new Date().toISOString().slice(0, 10);

const isOverdue = (task: Task): boolean =>
    task.due_date !== null &&
    task.due_date < today &&
    task.status !== 'done' &&
    task.status !== 'cancelled';

const formatDate = (date: string): string => date.slice(0, 10);

const summaryCards = computed(() =>
    props.readOnly !== true
        ? []
        : [
              {
                  label: 'Total Tasks',
                  value: props.summary?.total_tasks ?? 0,
                  icon: SquareCheck,
                  iconClass: 'bg-indigo-50 text-indigo-600',
                  trend: props.summary?.trends.total_tasks,
              },
              {
                  label: 'Completed',
                  value: props.summary?.by_status.done ?? 0,
                  icon: CircleCheck,
                  iconClass: 'bg-emerald-50 text-emerald-600',
                  trend: props.summary?.trends.completed,
              },
              {
                  label: 'In Progress',
                  value: props.summary?.by_status.in_progress ?? 0,
                  icon: Activity,
                  iconClass: 'bg-amber-50 text-amber-600',
                  trend: props.summary?.trends.in_progress,
              },
              {
                  label: 'Overdue',
                  value: props.summary?.overdue_count ?? 0,
                  icon: AlertCircle,
                  iconClass: 'bg-red-50 text-red-600',
                  trend: props.summary?.trends.overdue_count,
              },
          ],
);

const statusFilter = ref<string>(props.filters.status ?? 'all');
const priorityFilter = ref<string>(props.filters.priority ?? 'all');
const projectFilter = ref<string>(
    props.filters.project_id != null ? String(props.filters.project_id) : 'all',
);
const tagFilter = ref<string>(
    props.filters.tag_id != null ? String(props.filters.tag_id) : 'all',
);
const search = ref(props.filters.search ?? '');
const perPage = ref<number>(Number(props.filters.per_page ?? 10));

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

    if (perPage.value !== 10) {
        params.per_page = String(perPage.value);
    }

    router.get(window.location.pathname, params, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

// Select changes apply immediately; the search box is debounced.
watch([statusFilter, priorityFilter, projectFilter, tagFilter, perPage], applyFilters);

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
    perPage.value = 10;
}

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
    <Head :title="`${props.pageTitle}`"/>

    <div class="space-y-5">
        <!-- Summary cards -->
        <div v-if="props.readOnly" class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
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
            class="flex flex-col gap-3 rounded-2xl border border-slate-200/80 bg-white p-3.5 sm:flex-row sm:items-center sm:justify-between shadow-xs"
        >
            <div class="flex flex-1 flex-wrap items-center gap-2.5">
                <div class="relative w-full sm:w-72">
                    <Input
                        v-model="search"
                        type="search"
                        placeholder="Search tasks, IDs, tags..."
                        class="h-9 w-full rounded-full border-0 bg-[#F1F5F9] px-4 text-xs font-normal text-slate-800 placeholder:text-slate-400 focus-visible:ring-2 focus-visible:ring-indigo-500/20 shadow-none"
                    />
                </div>

                <Select v-model="statusFilter">
                    <SelectTrigger class="h-9 w-36 rounded-full border-0 bg-[#F1F5F9] px-4 text-xs font-medium text-slate-600 shadow-none hover:bg-slate-200/60">
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
                    <SelectTrigger class="h-9 w-36 rounded-full border-0 bg-[#F1F5F9] px-4 text-xs font-medium text-slate-600 shadow-none hover:bg-slate-200/60">
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
                    <SelectTrigger class="h-9 w-40 rounded-full border-0 bg-[#F1F5F9] px-4 text-xs font-medium text-slate-600 shadow-none hover:bg-slate-200/60">
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
                    <SelectTrigger class="h-9 w-36 rounded-full border-0 bg-[#F1F5F9] px-4 text-xs font-medium text-slate-600 shadow-none hover:bg-slate-200/60">
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
                    class="h-9 rounded-full px-3 text-xs text-slate-500 hover:bg-slate-100"
                    @click="resetFilters"
                >
                    Clear
                </Button>
            </div>

            <div class="text-xs font-medium text-slate-400 shrink-0">
                {{ props.tasks.total ?? props.tasks.data.length }} tasks
            </div>
        </div>

        <!-- Empty state -->
        <div
            v-if="tasks.data.length === 0"
            class="rounded-2xl border border-dashed border-slate-200 bg-white p-8 text-center sm:p-12"
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
            class="overflow-hidden rounded-2xl border border-slate-200/70 bg-white shadow-xs md:block"
        >
            <table class="w-full text-left text-xs border-collapse">
                <thead
                    class="border-b border-slate-100 bg-[#FAFAFC]/80 text-[11px] font-bold tracking-wider text-[#90A1B9] uppercase"
                >
                    <tr>
                        <th class="w-10 px-4 py-3.5">
                            <Checkbox :checked="false" disabled class="rounded border-slate-300" />
                        </th>
                        <th class="px-4 py-3.5 font-bold">ID</th>
                        <th class="px-4 py-3.5 font-bold">TASK</th>
                        <th class="px-4 py-3.5 font-bold">STATUS</th>
                        <th class="px-4 py-3.5 font-bold">PRIORITY</th>
                        <th class="px-4 py-3.5 font-bold">ASSIGNEE</th>
                        <th class="px-4 py-3.5 font-bold">DUE DATE</th>
                        <th v-if="!readOnly" class="w-20 px-4 py-3.5 text-right font-bold">
                            ACTIONS
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100/80">
                    <tr
                        v-for="task in tasks.data"
                        :key="task.id"
                        class="transition-colors hover:bg-slate-50/60 cursor-pointer"
                        @click="openTask(task)"
                    >
                        <td class="px-4 py-3.5" @click.stop>
                            <Checkbox
                                :checked="isSelected(task.id)"
                                class="rounded border-slate-300"
                                @update:model-value="
                                    (value: boolean | 'indeterminate') =>
                                        toggleSelected(task.id, value === true)
                                "
                            />
                        </td>
                        <td
                            class="px-4 py-3.5 text-[#90A1B9] font-medium tabular-nums"
                        >
                            TF-{{ String(task.id).padStart(3, '0') }}
                        </td>
                        <td class="px-4 py-3.5">
                            <div class="font-semibold text-slate-900 text-sm">
                                {{ task.title }}
                            </div>
                            <div
                                class="mt-1 flex items-center gap-1.5 text-xs text-muted-foreground"
                            >
                               <span
                                v-if="task.project"
                                :class="taskProjectConfig.item.badge"
                                :style="{ backgroundColor: task.project.color + '20', color: task.project.color, borderColor: task.project.color + '40' }"
                            >
                                {{ task.project.name }}
                            </span>
                                <span
                                    v-for="tag in task.tags"
                                    :key="tag.id"
                                    class="rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-normal text-slate-500"
                                >
                                    {{ tag.name }}
                                </span>
                                <span class="inline-flex items-center gap-1 text-[11px] text-slate-400">
                                    <MessageSquare class="size-3 text-slate-400" />
                                    {{ task.comments_count ?? 0 }}
                                </span>
                                <span class="inline-flex items-center gap-1 text-[11px] text-slate-400">
                                    <Paperclip class="size-3 text-slate-400" />
                                    {{ task.attachments_count ?? 0 }}
                                </span>
                            </div>
                        </td>
                        <td class="px-4 py-3.5">
                            <span
                                class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium border"
                                :class="taskStatusConfig[task.status as keyof typeof taskStatusConfig]?.badge"
                            >
                                <component :is="taskStatusConfig[task.status as keyof typeof taskStatusConfig]?.icon" class="size-3" />
                                {{ statusLabel(task.status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3.5">
                            <span
                                class="inline-flex items-center gap-1.5 rounded-full px-3 py-0.5 text-xs font-medium border"
                                :class="taskPriorityConfig[task.priority as keyof typeof taskPriorityConfig]?.badge"
                            >
                                <span :class="taskPriorityConfig[task.priority as keyof typeof taskPriorityConfig]?.dotSmall" />
                                {{ taskPriorityConfig[task.priority as keyof typeof taskPriorityConfig]?.label }}
                            </span>
                        </td>
                        <td class="px-4 py-3.5">
                            <div
                                v-if="task.assignee"
                                class="flex items-center gap-2"
                            >
                                <Avatar class="size-7">
                                    <AvatarFallback
                                        class="text-[11px] font-bold"
                                        :class="getAvatarBg(task.assignee.name)"
                                    >
                                        {{ getInitials(task.assignee.name) }}
                                    </AvatarFallback>
                                </Avatar>
                                <span class="truncate text-xs font-medium text-slate-700">{{ task.assignee.name }}</span>
                            </div>
                            <span v-else class="text-slate-400">—</span>
                        </td>
                        <td
                            class="px-4 py-3.5 text-xs"
                            :class="
                                isOverdue(task)
                                    ? 'font-medium text-red-600'
                                    : 'text-[#90A1B9]'
                            "
                        >
                            <span v-if="task.due_date" class="inline-flex items-center gap-1.5">
                                <Calendar class="size-3.5 text-[#90A1B9]" />
                                {{ formatDate(task.due_date) }}
                            </span>
                            <span v-else>—</span>
                        </td>
                        <td v-if="!readOnly" class="px-4 py-3.5" @click.stop>
                            <div class="flex items-center justify-end gap-1">
                                <button
                                    type="button"
                                    class="inline-flex size-7 items-center justify-center rounded-md text-[#0075FF] transition-colors hover:bg-blue-50"
                                    :data-test="`edit-task-${task.id}-button`"
                                    @click="openEdit(task)"
                                >
                                    <SquarePen class="size-4" />
                                </button>
                                <button
                                    type="button"
                                    class="inline-flex size-7 items-center justify-center rounded-md text-[#E40000] transition-colors hover:bg-red-50"
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
                class="rounded-2xl border border-slate-200 bg-white p-4 shadow-xs"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="font-semibold text-slate-900 text-sm">{{ task.title }}</p>
                        <div class="mt-1 flex flex-wrap items-center gap-1.5">
                            <span class="text-xs text-[#90A1B9] font-medium">TF-{{ String(task.id).padStart(3, '0') }}</span>
                            <span
                                v-if="task.project"
                                class="rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-normal text-slate-500"
                            >
                                {{ task.project.name }}
                            </span>
                        </div>
                    </div>
                    <button
                        v-if="!readOnly"
                        type="button"
                        class="inline-flex size-8 shrink-0 items-center justify-center rounded-md text-slate-400 hover:bg-red-50 hover:text-red-600 transition-colors"
                        @click="pendingDelete = task"
                    >
                        <Trash2 class="size-4" />
                    </button>
                </div>

                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium border"
                        :class="taskStatusConfig[task.status as keyof typeof taskStatusConfig]?.badge"
                    >
                        <component :is="taskStatusConfig[task.status as keyof typeof taskStatusConfig]?.icon" class="size-3" />
                        {{ statusLabel(task.status) }}
                    </span>
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full px-3 py-0.5 text-xs font-medium border"
                        :class="taskPriorityConfig[task.priority as keyof typeof taskPriorityConfig]?.badge"
                    >
                        <span :class="(taskPriorityConfig[task.priority as keyof typeof taskPriorityConfig]?.dotSmall ?? '') + ' size-1.5 shrink-0 rounded-full'" />
                        {{ taskPriorityConfig[task.priority as keyof typeof taskPriorityConfig]?.label }}
                    </span>
                    <span
                        v-if="task.due_date"
                        class="text-xs"
                        :class="
                            isOverdue(task)
                                ? 'font-medium text-red-600'
                                : 'text-slate-500'
                        "
                    >
                        {{ formatDate(task.due_date) }}
                    </span>
                    <span
                        v-for="tag in task.tags"
                        :key="tag.id"
                        class="rounded-md bg-slate-100 px-2 py-0.5 text-xs text-slate-500"
                    >
                        {{ tag.name }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <div
            v-if="tasks.links?.length || tasks.data.length"
            class="flex flex-wrap items-center justify-between gap-4 px-1 py-2 text-xs"
        >
            <div class="text-slate-400 font-medium">
                Showing {{ tasks.data.length }} of {{ props.tasks.total ?? tasks.data.length }} tasks
            </div>

            <Select v-model="perPage">
                <SelectTrigger class="h-8 w-16 rounded-full border-0 bg-[#F1F5F9] px-2.5 text-xs font-medium text-slate-600 shadow-none">
                    <SelectValue placeholder="10" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="5">5</SelectItem>
                    <SelectItem value="10">10</SelectItem>
                    <SelectItem value="15">15</SelectItem>
                    <SelectItem value="25">25</SelectItem>
                    <SelectItem value="50">50</SelectItem>
                    <SelectItem value="100">100</SelectItem>
                </SelectContent>
            </Select>

            <nav
                v-if="tasks.links?.length"
                class="flex items-center gap-1.5"
            >
                <template v-for="(link, index) in tasks.links" :key="index">
                    <span
                        v-if="!link.url"
                        class="px-2 py-1 text-slate-300"
                        v-html="link.label"
                    />
                    <Link
                        v-else
                        :href="link.url"
                        preserve-scroll
                        class="transition-all"
                        :class="
                            link.active
                                ? 'flex size-7 items-center justify-center rounded-full bg-[#4F46E5] font-semibold text-white shadow-xs'
                                : 'flex h-7 items-center justify-center rounded-md px-2.5 font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-900'
                        "
                    >
                        <span v-html="link.label" />
                    </Link>
                </template>
            </nav>
        </div>

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
plate>
