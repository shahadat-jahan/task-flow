<script setup lang="ts">
import { usePage, router } from '@inertiajs/vue3';
import { Bell, Menu } from '@lucide/vue';
import { ref, watch } from 'vue';
import SidebarContent from '@/components/SidebarContent.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Sheet, SheetContent } from '@/components/ui/sheet';
import { Toaster } from '@/components/ui/sonner';
import { useTaskModal } from '@/composables/useTaskModal';

import type { BreadcrumbItem } from '@/types';

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
const { openCreate } = useTaskModal();
const mobileNavOpen = ref(false);
const search = ref('');
watch(search, (val) => {
  router.get(window.location.pathname, { search: val }, { preserveState: true, preserveScroll: true, replace: true });
});
</script>

<template>
    <div class="flex min-h-svh bg-muted/40">
        <!-- Sidebar (desktop) -->
        <aside class="hidden w-64 shrink-0 border-r border-border md:flex">
            <SidebarContent class="w-full" />
        </aside>

        <!-- Mobile nav drawer -->
        <Sheet v-model:open="mobileNavOpen">
            <SheetContent side="left" class="w-64 p-0">
                <SidebarContent
                    class="w-full"
                    @navigate="mobileNavOpen = false"
                />
            </SheetContent>
        </Sheet>

        <!-- Main -->
        <div class="flex min-w-0 flex-1 flex-col">
            <!-- Top bar -->
            <header
                class="flex h-16 items-center gap-4 border-b border-border bg-white px-4 sm:px-6"
            >
                <Button
                    variant="ghost"
                    size="icon"
                    type="button"
                    class="size-9 md:hidden"
                    aria-label="Open menu"
                    @click="mobileNavOpen = true"
                >
                    <Menu class="size-5" />
                </Button>

                <div class="min-w-0">
                    <h1 class="truncate text-lg font-semibold tracking-tight">
                        {{ title ?? page.props.pageTitle ?? '' }}
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
                            v-model="search"
                            type="search"
                            placeholder="Search tasks…"
                            class="w-56 rounded-full border-0 bg-[#F1F5F9] px-4 text-xs font-normal text-slate-800 placeholder:text-slate-400 focus-visible:ring-2 focus-visible:ring-indigo-500/20 shadow-none"
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
            <main class="flex-1 p-4 sm:p-6">
                <slot />
            </main>
        </div>

        <Toaster />
    </div>
</template>
