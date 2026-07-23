<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    ChevronsUpDown,
    LayoutGrid,
    ListTodo,
    Settings,
    User,
} from '@lucide/vue';
import { computed } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { useInitials } from '@/composables/useInitials';
import { taskProjectConfig } from '@/composables/useTaskBadges';
import { dashboard } from '@/routes';
import { index as myTasksIndex } from '@/routes/my-tasks';
import { edit as profileEdit } from '@/routes/profile';
import type { NavItem } from '@/types';

const emit = defineEmits<{
    navigate: [];
}>();

const page = usePage();
const user = computed(() => page.props.auth.user);
const projects = computed(() => page.props.sidebarProjects ?? []);
const myTasksCount = computed<number>(() => (page.props.sidebarMyTasksCount as number) ?? 0);

const { isCurrentUrl } = useCurrentUrl();
const { getInitials } = useInitials();

const mainNavItems: NavItem[] = [
    { title: 'Dashboard', href: dashboard(), icon: LayoutGrid },
    { title: 'My Tasks', href: myTasksIndex(), icon: ListTodo },
    { title: 'Profile', href: profileEdit(), icon: User },
    { title: 'Settings', href: profileEdit(), icon: Settings },
];
</script>

<template>
    <div class="flex h-full w-full flex-col bg-white">
        <Link
            :href="dashboard()"
            class="flex h-16 items-center gap-2 border-b border-border px-6"
            @click="emit('navigate')"
        >
            <AppLogoIcon class="size-7 fill-current text-indigo-600" />
            <span class="text-lg font-semibold tracking-tight">TaskFlow</span>
        </Link>

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
                    @click="emit('navigate')"
                >
                    <component :is="item.icon" class="size-4" />
                    <span>{{ item.title }}</span>
                    <span
                        v-if="item.title === 'My Tasks' && myTasksCount > 0"
                        class="ml-auto text-xs font-medium tabular-nums"
                    >
                        {{ myTasksCount }}
                    </span>
                </Link>
            </nav>

            <div>
                <p
                    :class="taskProjectConfig.section.header"
                >
                    Projects
                </p>
                <ul :class="taskProjectConfig.section.list">
                    <li v-for="project in projects" :key="project.id">
                        <div
                            :class="taskProjectConfig.item.row"
                        >
                            <span
                                :class="taskProjectConfig.item.dot"
                                :style="{ backgroundColor: project.color }"
                            />
                            <span :class="taskProjectConfig.item.name">{{ project.name }}</span>
                            <span :class="taskProjectConfig.item.count">
                                {{ project.tasks_count }}
                            </span>
                        </div>
                    </li>
                </ul>
            </div>
        </div>

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
                            <p class="truncate text-xs text-muted-foreground">
                                {{ user.email }}
                            </p>
                        </div>
                        <ChevronsUpDown class="size-4 text-muted-foreground" />
                    </button>
                </DropdownMenuTrigger>
                <DropdownMenuContent class="w-56" align="end">
                    <UserMenuContent :user="user" />
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    </div>
</template>
