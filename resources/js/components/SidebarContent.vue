<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    ChevronsUpDown,
    LayoutGrid,
    ListTodo,
    Settings,
    User,
} from '@lucide/vue';
import { computed, toRefs } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
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
    toggle: [];
}>();

const props = withDefaults(defineProps<{
    collapsed?: boolean;
    showToggle?: boolean;
}>(), {
    collapsed: false,
    showToggle: true,
});

const { collapsed, showToggle } = toRefs(props);

const page = usePage();
const appName = page.props.name;
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
    <div class="relative flex h-full w-full flex-col overflow-hidden bg-white">
        <Link
            :href="dashboard()"
            class="flex h-16 items-center gap-2.5 border-b border-border px-6"
            @click="emit('navigate')"
        >
            <AppLogo class="size-9 shrink-0" />
            <span v-if="!collapsed" class="text-lg font-bold tracking-tight text-slate-900">{{ appName }}</span>
        </Link>

        <div v-if="showToggle" class="absolute right-1 top-[1rem] z-10">
            <Button
                variant="ghost"
                size="icon"
                type="button"
                class="size-8 rounded-full bg-[#E2E8F0] p-0 hover:bg-[#E2E8F0]"
                :aria-label="collapsed ? 'Expand sidebar' : 'Collapse sidebar'"
                @click="emit('toggle')"
            >
                <svg v-if="!collapsed" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M0 12C0 5.37258 5.37258 0 12 0C18.6274 0 24 5.37258 24 12C24 18.6274 18.6274 24 12 24C5.37258 24 0 18.6274 0 12Z" fill="#E2E8F0"/>
                    <path d="M13.625 15.25L10.375 12L13.625 8.75" stroke="#45556C" stroke-width="1.08333" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <svg v-else width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M0 12C0 5.37258 5.37258 0 12 0C18.6274 0 24 5.37258 24 12C24 18.6274 18.6274 24 12 24C5.37258 24 0 18.6274 0 12Z" fill="#E2E8F0"/>
                    <path d="M10.375 8.75L13.625 12L10.375 15.25" stroke="#45556C" stroke-width="1.08333" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </Button>
        </div>

        <div v-if="!collapsed" class="flex flex-1 flex-col gap-6 overflow-y-auto px-3 py-4">
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
                    <component :is="item.icon" class="size-4 shrink-0" />
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

        <div v-else class="flex flex-1 flex-col items-center gap-4 overflow-y-auto px-2 py-4">
            <nav class="flex flex-col items-center gap-2">
                <Link
                    v-for="item in mainNavItems"
                    :key="item.title"
                    :href="item.href"
                    class="flex items-center justify-center rounded-md p-2 text-sm transition-colors"
                    :class="
                        isCurrentUrl(item.href)
                            ? 'bg-muted font-medium text-foreground'
                            : 'text-muted-foreground hover:bg-muted hover:text-foreground'
                    "
                    @click="emit('navigate')"
                    :title="item.title"
                >
                    <component :is="item.icon" class="size-5 shrink-0" />
                </Link>
            </nav>
        </div>

        <div v-if="!collapsed" class="border-t border-border p-3">
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
