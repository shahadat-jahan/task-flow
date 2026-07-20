<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Toaster } from '@/components/ui/sonner';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="min-h-svh">
        <div class="grid min-h-svh md:grid-cols-[45%_55%]">
            <!-- Left panel: gradient branding / testimonial / stats (hidden below md) -->
            <div
                class="relative hidden flex-col bg-gradient-to-br from-purple-600 to-indigo-600 p-10 text-white md:flex"
            >
                <Link
                    :href="home()"
                    class="flex items-center gap-2 text-lg font-semibold"
                >
                    <AppLogoIcon class="size-8 fill-current text-white" />
                    <span>TaskFlow</span>
                </Link>

                <div class="flex flex-1 items-center">
                    <div class="max-w-md">
                        <p class="text-xl leading-relaxed italic">
                            "TaskFlow completely transformed how our team
                            collaborates. We ship 40% faster now."
                        </p>
                        <div class="mt-6 flex items-center gap-3">
                            <div
                                class="flex size-10 items-center justify-center rounded-full bg-white/20 font-semibold"
                            >
                                MR
                            </div>
                            <div>
                                <p class="text-sm font-medium">
                                    Maya Rodriguez
                                </p>
                                <p class="text-sm text-white/70">
                                    CTO, Meridian Labs
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <p class="text-2xl font-bold">10K+</p>
                        <p class="text-sm text-white/70">Teams</p>
                    </div>
                    <div>
                        <p class="text-2xl font-bold">99.9%</p>
                        <p class="text-sm text-white/70">Uptime</p>
                    </div>
                    <div>
                        <p class="text-2xl font-bold">4.9/5</p>
                        <p class="text-sm text-white/70">Rating</p>
                    </div>
                </div>
            </div>

            <!-- Right panel: white, centered form -->
            <div class="flex items-center justify-center p-8">
                <div class="w-full max-w-sm">
                    <div class="mb-6 space-y-2 text-center">
                        <h1 class="text-2xl font-semibold tracking-tight">
                            {{ title }}
                        </h1>
                        <p
                            v-if="description"
                            class="text-sm text-muted-foreground"
                        >
                            {{ description }}
                        </p>
                    </div>

                    <slot />
                </div>
            </div>
        </div>

        <Toaster />
    </div>
</template>
