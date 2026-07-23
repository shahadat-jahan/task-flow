<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Activity,
    AlertCircle,
    Calendar,
    CircleCheck,
    Plus,
    SquareCheck,
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
import { priorityBadgeClass, statusBadgeClass } from '@/lib/taskBadges';
import { destroy, show } from '@/routes/my-tasks';

type Status = 'todo' | 'in_progress' | 'in_review' | 'done' | 'cancelled';
type Priority = 'low' | 'medium' | 'high';
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
    };
    projects: ProjectSummary[];
    tags: TagSummary[];
    users: UserSummary[];
    summary?: TaskSummary;
    readOnly?: boolean;
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
    <Head :title="`${props.pageTitle}`"/>

    <div class="space-y-6">
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
            class="overflow-hidden rounded-lg border border-border bg-white md:block"
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
                        <th v-if="!readOnly" class="w-24 px-4 py-3 text-right font-medium">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <tr
                        v-for="task in tasks.data"
                        :key="task.id"
                        class="transition-colors hover:bg-muted/40 cursor-pointer"
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
                            class="px-4 py-3 text-[#90A1B9] tabular-nums"
                        >
                            TF-{{ task.id }}
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
                                        :style="{ backgroundColor: task.project.color }"
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
                                <span class="truncate">{{ task.assignee.name }}</span>
                            </div>
                            <span v-else class="text-muted-foreground">—</span>
                        </td>
                        <td
                            class="px-4 py-3"
                            :class="
                                isOverdue(task)
                                    ? 'font-medium text-red-600'
                                    : 'text-[#90A1B9]'
                            "
                        >
                            <span v-if="task.due_date" class="inline-flex items-center gap-1.5">
                                <Calendar class="size-3.5" />
                                {{ formatDate(task.due_date) }}
                            </span>
                            <span v-else>—</span>
                        </td>
                        <td v-if="!readOnly" class="px-4 py-3" @click.stop>
                            <div class="flex items-center justify-end gap-1">
                                <button
                                    type="button"
                                    class="inline-flex size-5 items-center justify-center rounded-md transition-colors hover:bg-blue-50"
                                    :data-test="`edit-task-${task.id}-button`"
                                    @click="openEdit(task)"
                                >
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M21.4549 5.416C21.5499 5.5602 21.5922 5.73278 21.5747 5.90458C21.5573 6.07639 21.481 6.23689 21.3589 6.359L12.1659 15.551C12.0718 15.645 11.9545 15.7123 11.8259 15.746L7.99689 16.746C7.87032 16.779 7.73732 16.7783 7.61109 16.7441C7.48485 16.7098 7.36978 16.6431 7.27729 16.5506C7.18479 16.4581 7.1181 16.343 7.08382 16.2168C7.04955 16.0906 7.04888 15.9576 7.08189 15.831L8.08189 12.003C8.1118 11.8884 8.16679 11.7818 8.24289 11.691L17.4699 2.47C17.6105 2.32955 17.8011 2.25066 17.9999 2.25066C18.1986 2.25066 18.3893 2.32955 18.5299 2.47L21.3589 5.298C21.3938 5.33483 21.4259 5.37428 21.4549 5.416ZM19.7679 5.828L17.9999 4.061L9.48189 12.579L8.85689 14.972L11.2499 14.347L19.7679 5.828Z" fill="#0075FF"/>
                                        <path d="M19.641 17.16C19.9143 14.824 20.0016 12.4699 19.902 10.12C19.8997 10.0646 19.9088 10.0094 19.929 9.95771C19.9491 9.90606 19.9798 9.85917 20.019 9.82L21.003 8.836C21.0299 8.80896 21.064 8.79026 21.1013 8.78215C21.1385 8.77403 21.1774 8.77685 21.2131 8.79026C21.2488 8.80368 21.2798 8.82711 21.3025 8.85776C21.3252 8.8884 21.3386 8.92494 21.341 8.963C21.5257 11.7542 21.4554 14.5565 21.131 17.335C20.895 19.357 19.271 20.942 17.258 21.167C13.7633 21.5538 10.2367 21.5538 6.74201 21.167C4.73001 20.942 3.10501 19.357 2.86901 17.335C2.45512 13.7904 2.45512 10.2096 2.86901 6.665C3.10501 4.643 4.72901 3.058 6.74201 2.833C9.39446 2.54005 12.0667 2.4688 14.731 2.62C14.7691 2.62274 14.8057 2.63635 14.8363 2.65921C14.867 2.68208 14.8904 2.71325 14.9039 2.74902C14.9173 2.7848 14.9203 2.82368 14.9123 2.86108C14.9044 2.89847 14.8859 2.9328 14.859 2.96L13.866 3.952C13.8272 3.99085 13.7808 4.02128 13.7297 4.04141C13.6786 4.06154 13.6239 4.07093 13.569 4.069C11.3458 3.99285 9.11993 4.07807 6.90901 4.324C6.26295 4.39551 5.65986 4.68272 5.19717 5.13925C4.73447 5.59578 4.43919 6.19496 4.35901 6.84C3.95787 10.2683 3.95787 13.7317 4.35901 17.16C4.43919 17.805 4.73447 18.4042 5.19717 18.8607C5.65986 19.3173 6.26295 19.6045 6.90901 19.676C10.264 20.051 13.736 20.051 17.092 19.676C17.7381 19.6045 18.3412 19.3173 18.8039 18.8607C19.2666 18.4042 19.5608 17.805 19.641 17.16Z" fill="#0075FF"/>
                                    </svg>

                                </button>
                                <button
                                    type="button"
                                    class="inline-flex size-5 items-center justify-center rounded-md transition-colors hover:bg-red-50"
                                    :data-test="`delete-task-${task.id}-button`"
                                    @click="pendingDelete = task"
                                >
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M10.125 4.875V5.25H13.875V4.875C13.875 4.37772 13.6775 3.90081 13.3258 3.54917C12.9742 3.19754 12.4973 3 12 3C11.5027 3 11.0258 3.19754 10.6742 3.54917C10.3225 3.90081 10.125 4.37772 10.125 4.875ZM8.625 5.25V4.875C8.625 3.97989 8.98058 3.12145 9.61351 2.48851C10.2464 1.85558 11.1049 1.5 12 1.5C12.8951 1.5 13.7535 1.85558 14.3865 2.48851C15.0194 3.12145 15.375 3.97989 15.375 4.875V5.25H21C21.1989 5.25 21.3897 5.32902 21.5303 5.46967C21.671 5.61032 21.75 5.80109 21.75 6C21.75 6.19891 21.671 6.38968 21.5303 6.53033C21.3897 6.67098 21.1989 6.75 21 6.75H19.869L18.45 19.176C18.3454 20.0911 17.9076 20.9358 17.2201 21.5488C16.5326 22.1618 15.6436 22.5004 14.7225 22.5H9.2775C8.3564 22.5004 7.46735 22.1618 6.77989 21.5488C6.09243 20.9358 5.65464 20.0911 5.55 19.176L4.131 6.75H3C2.80109 6.75 2.61032 6.67098 2.46967 6.53033C2.32902 6.38968 2.25 6.19891 2.25 6C2.25 5.80109 2.32902 5.61032 2.46967 5.46967C2.61032 5.32902 2.80109 5.25 3 5.25H8.625ZM7.041 19.005C7.10362 19.5539 7.36602 20.0606 7.77819 20.4286C8.19037 20.7965 8.7235 20.9999 9.276 21H14.7233C15.2757 20.9999 15.8089 20.7965 16.2211 20.4286C16.6332 20.0606 16.8956 19.5539 16.9583 19.005L18.36 6.75H5.64075L7.041 19.005ZM9.75 9.375C9.94891 9.375 10.1397 9.45402 10.2803 9.59467C10.421 9.73532 10.5 9.92609 10.5 10.125V17.625C10.5 17.8239 10.421 18.0147 10.2803 18.1553C10.1397 18.296 9.94891 18.375 9.75 18.375C9.55109 18.375 9.36032 18.296 9.21967 18.1553C9.07902 18.0147 9 17.8239 9 17.625V10.125C9 9.92609 9.07902 9.73532 9.21967 9.59467C9.36032 9.45402 9.55109 9.375 9.75 9.375ZM15 10.125C15 9.92609 14.921 9.73532 14.7803 9.59467C14.6397 9.45402 14.4489 9.375 14.25 9.375C14.0511 9.375 13.8603 9.45402 13.7197 9.59467C13.579 9.73532 13.5 9.92609 13.5 10.125V17.625C13.5 17.8239 13.579 18.0147 13.7197 18.1553C13.8603 18.296 14.0511 18.375 14.25 18.375C14.4489 18.375 14.6397 18.296 14.7803 18.1553C14.921 18.0147 15 17.8239 15 17.625V10.125Z" fill="#E40000"/>
                                    </svg>
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
                        v-if="!readOnly"
                        type="button"
                        class="inline-flex size-8 shrink-0 items-center justify-center rounded-md text-muted-foreground hover:bg-red-50 hover:text-red-600"
                        @click="pendingDelete = task"
                    >
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M10.125 4.875V5.25H13.875V4.875C13.875 4.37772 13.6775 3.90081 13.3258 3.54917C12.9742 3.19754 12.4973 3 12 3C11.5027 3 11.0258 3.19754 10.6742 3.54917C10.3225 3.90081 10.125 4.37772 10.125 4.875ZM8.625 5.25V4.875C8.625 3.97989 8.98058 3.12145 9.61351 2.48851C10.2464 1.85558 11.1049 1.5 12 1.5C12.8951 1.5 13.7535 1.85558 14.3865 2.48851C15.0194 3.12145 15.375 3.97989 15.375 4.875V5.25H21C21.1989 5.25 21.3897 5.32902 21.5303 5.46967C21.671 5.61032 21.75 5.80109 21.75 6C21.75 6.19891 21.671 6.38968 21.5303 6.53033C21.3897 6.67098 21.1989 6.75 21 6.75H19.869L18.45 19.176C18.3454 20.0911 17.9076 20.9358 17.2201 21.5488C16.5326 22.1618 15.6436 22.5004 14.7225 22.5H9.2775C8.3564 22.5004 7.46735 22.1618 6.77989 21.5488C6.09243 20.9358 5.65464 20.0911 5.55 19.176L4.131 6.75H3C2.80109 6.75 2.61032 6.67098 2.46967 6.53033C2.32902 6.38968 2.25 6.19891 2.25 6C2.25 5.80109 2.32902 5.61032 2.46967 5.46967C2.61032 5.32902 2.80109 5.25 3 5.25H8.625ZM7.041 19.005C7.10362 19.5539 7.36602 20.0606 7.77819 20.4286C8.19037 20.7965 8.7235 20.9999 9.276 21H14.7233C15.2757 20.9999 15.8089 20.7965 16.2211 20.4286C16.6332 20.0606 16.8956 19.5539 16.9583 19.005L18.36 6.75H5.64075L7.041 19.005ZM9.75 9.375C9.94891 9.375 10.1397 9.45402 10.2803 9.59467C10.421 9.73532 10.5 9.92609 10.5 10.125V17.625C10.5 17.8239 10.421 18.0147 10.2803 18.1553C10.1397 18.296 9.94891 18.375 9.75 18.375C9.55109 18.375 9.36032 18.296 9.21967 18.1553C9.07902 18.0147 9 17.8239 9 17.625V10.125C9 9.92609 9.07902 9.73532 9.21967 9.59467C9.36032 9.45402 9.55109 9.375 9.75 9.375ZM15 10.125C15 9.92609 14.921 9.73532 14.7803 9.59467C14.6397 9.45402 14.4489 9.375 14.25 9.375C14.0511 9.375 13.8603 9.45402 13.7197 9.59467C13.579 9.73532 13.5 9.92609 13.5 10.125V17.625C13.5 17.8239 13.579 18.0147 13.7197 18.1553C13.8603 18.296 14.0511 18.375 14.25 18.375C14.4489 18.375 14.6397 18.296 14.7803 18.1553C14.921 18.0147 15 17.8239 15 17.625V10.125Z" fill="#E40000"/>
                                    </svg>
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
