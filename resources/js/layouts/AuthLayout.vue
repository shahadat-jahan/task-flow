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
    <div class="min-h-screen w-full bg-[#F8FAFC] font-['Inter'] selection:bg-[#4F39F6] selection:text-white">
        <div class="grid min-h-screen md:grid-cols-[45%_55%] lg:grid-cols-2">

            <!-- Left Panel: Gradient and Stats Design -->
            <div
                class="relative hidden h-full flex-col justify-between overflow-hidden bg-gradient-to-br from-[#4F39F6] via-[#432DD7] to-[#7008E7] p-12 text-white md:flex"
            >
                <!-- Background Decorative Circles -->
                <div class="absolute -top-[192px] left-[704px] h-[384px] w-[384px] rounded-full bg-white/5"></div>
                <div class="absolute top-[862px] left-[-85.33px] h-[256px] w-[256px] rounded-full bg-white/5"></div>

                <!-- Top Section: Logo and App Name -->
                <div class="relative z-10 flex flex-row items-center gap-3">
                    <Link
                    :href="home()"
                    class="flex items-center gap-2 text-lg font-semibold"
                >
                    <AppLogoIcon class="size-8 fill-current text-white" />
                    <span>TaskFlow</span>
                </Link>
                </div>

                <!-- Middle Section: Testimonial & Quote -->
                <div class="relative z-10 flex flex-col items-start gap-4 max-w-[842px]">
                    <blockquote class="text-[20px] font-medium leading-[32px] text-white/90">
                        "TaskFlow completely transformed how our team collaborates. We ship 40% faster now."
                    </blockquote>
                    <div class="flex flex-row items-center gap-3 pt-4">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-full bg-white/20 text-[14px] font-bold"
                        >
                            MR
                        </div>
                        <div class="flex flex-col items-start">
                            <span class="text-[14px] font-semibold leading-[20px] text-white">Maya Rodriguez</span>
                            <span class="text-[12px] font-normal leading-[16px] text-white/60">CTO, Meridian Labs</span>
                        </div>
                    </div>
                </div>

                <!-- Bottom Section: Platform Grid Stats -->
                <div class="relative z-10 grid grid-cols-3 border-t border-white/10 pt-6 text-center">
                    <div class="flex flex-col items-center">
                        <span class="text-[20px] font-bold leading-[28px]">10K+</span>
                        <span class="text-[12px] font-normal leading-[16px] text-white/60">Teams</span>
                    </div>
                    <div class="flex flex-col items-center">
                        <span class="text-[20px] font-bold leading-[28px]">99.9%</span>
                        <span class="text-[12px] font-normal leading-[16px] text-white/60">Uptime</span>
                    </div>
                    <div class="flex flex-col items-center">
                        <span class="text-[20px] font-bold leading-[28px]">4.9/5</span>
                        <span class="text-[12px] font-normal leading-[16px] text-white/60">Rating</span>
                    </div>
                </div>
            </div>

            <!-- Right Panel: White Content Layout Area -->
            <div class="flex items-center justify-center p-8 bg-[#F8FAFC]">
                <div class="w-full max-w-[448px] flex flex-col">

                    <!-- Dynamic Headers inside Forms Slot context -->
                    <div class="mb-8 space-y-2" v-if="title || description">
                        <h1 v-if="title" class="text-[24px] font-bold leading-[32px] text-[#0F172B]">
                            {{ title }}
                        </h1>
                        <p
                            v-if="description"
                            class="text-[14px] leading-[20px] text-[#62748E]"
                        >
                            {{ description }}
                        </p>
                    </div>

                    <!-- Inner Login/Register or Forgot Password Forms -->
                    <slot />
                </div>
            </div>
        </div>

        <Toaster />
    </div>
</template>
