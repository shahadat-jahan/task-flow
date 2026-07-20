<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Bell,
    ChevronsUpDown,
    LayoutGrid,
    ListTodo,
    Settings,
    User,
} from '@lucide/vue';
import { computed } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Toaster } from '@/components/ui/sonner';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { useInitials } from '@/composables/useInitials';
import { useTaskModal } from '@/composables/useTaskModal';
import { dashboard } from '@/routes';
import { edit as profileEdit } from '@/routes/profile';
import { index as tasksIndex } from '@/routes/tasks';
import type { BreadcrumbItem, NavItem } from '@/types';

withDefaults(
    defineProps<{
        title?: string;
        subtitle?: string;
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const page = usePage();
const user = computed(() => page.props.auth.user);
const projects = computed(() => page.props.sidebarProjects ?? []);

const { isCurrentUrl } = useCurrentUrl();
const { getInitials } = useInitials();
const { openCreate } = useTaskModal();

const mainNavItems: NavItem[] = [
    { title: 'Dashboard', href: dashboard(), icon: LayoutGrid },
    { title: 'My Tasks', href: tasksIndex(), icon: ListTodo },
    { title: 'Profile', href: profileEdit(), icon: User },
    { title: 'Settings', href: profileEdit(), icon: Settings },
];
</script>

<template>
    <div class="flex min-h-svh bg-muted/40">
        <!-- Sidebar -->
        <aside
            class="hidden w-64 shrink-0 flex-col border-r border-border bg-white md:flex"
        >
            <!-- Logo -->
            <Link
                :href="dashboard()"
                class="flex h-16 items-center gap-2 border-b border-border px-6"
            >
                <AppLogoIcon class="size-7 fill-current text-indigo-600" />
                <span class="text-lg font-semibold tracking-tight"
                    >TaskFlow</span
                >
            </Link>

            <!-- Nav + Projects -->
            <div class="flex flex-1 flex-col gap-6 overflow-y-auto px-3 py-4">
                <nav class="space-y-1">
                    <Link
                        v-for="item in mainNavItems"
                        :key="item.title"
                        :href="item.href"
                        class="flex items-center gap-3 rounded-md px-3 py-2 text-sm transition-colors"
                        :class="
                            isCurrentUrl(item.href)
                                ? 'bg-muted font-medium text-foreground'
                                : 'text-muted-foreground hover:bg-muted hover:text-foreground'
                        "
                    >
                        <component :is="item.icon" class="size-4" />
                        <span>{{ item.title }}</span>
                    </Link>
                </nav>

                <!-- Projects -->
                <div>
                    <p
                        class="px-3 pb-2 text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                    >
                        Projects
                    </p>
                    <ul class="space-y-1">
                        <li v-for="project in projects" :key="project.id">
                            <div
                                class="flex items-center gap-3 rounded-md px-3 py-2 text-sm text-muted-foreground"
                            >
                                <span
                                    class="size-2 shrink-0 rounded-full"
                                    :style="{ backgroundColor: project.color }"
                                />
                                <span class="truncate">{{ project.name }}</span>
                                <span class="ml-auto text-xs tabular-nums">
                                    {{ project.tasks_count }}
                                </span>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- User -->
            <div class="border-t border-border p-3">
                <DropdownMenu>
                    <DropdownMenuTrigger :as-child="true">
                        <button
                            class="flex w-full items-center gap-2 rounded-md px-2 py-2 text-left transition-colors hover:bg-muted"
                        >
                            <Avatar class="size-8">
                                <AvatarImage
                                    v-if="user.avatar"
                                    :src="user.avatar"
                                    :alt="user.name"
                                />
                                <AvatarFallback>{{
                                    getInitials(user.name)
                                }}</AvatarFallback>
                            </Avatar>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium">
                                    {{ user.name }}
                                </p>
                                <p
                                    class="truncate text-xs text-muted-foreground"
                                >
                                    {{ user.email }}
                                </p>
                            </div>
                            <ChevronsUpDown
                                class="size-4 text-muted-foreground"
                            />
                        </button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent class="w-56" align="end">
                        <UserMenuContent :user="user" />
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </aside>

        <!-- Main -->
        <div class="flex min-w-0 flex-1 flex-col">
            <!-- Top bar -->
            <header
                class="flex h-16 items-center gap-4 border-b border-border bg-white px-6"
            >
                <div class="min-w-0">
                    <h1 class="truncate text-lg font-semibold tracking-tight">
                        {{ title ?? page.props.name }}
                    </h1>
                    <p
                        v-if="subtitle"
                        class="truncate text-xs text-muted-foreground"
                    >
                        {{ subtitle }}
                    </p>
                </div>

                <div class="ml-auto flex items-center gap-3">
                    <div class="relative hidden sm:block">
                        <Input
                            type="search"
                            placeholder="Search tasks…"
                            class="w-56"
                        />
                    </div>

                    <Button
                        type="button"
                        data-test="new-task-button"
                        @click="openCreate()"
                    >
                        + New Task
                    </Button>

                    <Button
                        variant="ghost"
                        size="icon"
                        type="button"
                        class="size-9"
                    >
                        <Bell class="size-5" />
                    </Button>
                </div>
            </header>

            <!-- Content -->
            <main class="flex-1 p-6">
                <slot />
            </main>
        </div>

        <Toaster />
    </div>
</template>
