<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { Bell, Menu } from '@lucide/vue';
import { ref } from 'vue';
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
            <main class="flex-1 p-4 sm:p-6">
                <slot />
            </main>
        </div>

        <Toaster />
    </div>
</template>
