<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { login, register } from '@/routes';
</script>

<template>
    <Head title="Welcome to TaskFlow">
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link
            rel="preconnect"
            href="https://fonts.gstatic.com"
            crossorigin="true"
        />
        <link
            href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap"
            rel="stylesheet"
        />
    </Head>

    <div
        class="flex min-h-screen w-full flex-col items-center justify-center bg-[#FFFFFF] p-8 font-['Inter'] selection:bg-[#4F39F6] selection:text-white"
    >
        <main class="flex w-full max-w-[512px] flex-col items-center">
            <!-- App Main Logo Container -->
            <div
                class="flex h-16 w-16 items-center justify-center rounded-[16px] bg-[#4F39F6] shadow-[0px_10px_15px_-3px_#C6D2FF,0px_4px_6px_-4px_#C6D2FF]"
            >
                <svg
                    class="h-[28px] w-[28px] text-white"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2.33"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <polygon
                        points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"
                    ></polygon>
                </svg>
            </div>

            <!-- Heading Section -->
            <div class="mt-6 flex w-full flex-col items-center">
                <h1
                    class="w-full text-center text-[36px] leading-[40px] font-bold tracking-[-0.9px] text-[#0F172B]"
                >
                    Welcome to TaskFlow
                </h1>
            </div>

            <!-- Paragraph Section -->
            <div class="mt-3 flex w-full flex-col items-center">
                <p
                    class="w-full text-center text-[16px] leading-[26px] font-normal text-[#62748E]"
                >
                    The modern task management platform designed for
                    high-performance teams.<br />
                    Ship faster, collaborate smarter, stay organized.
                </p>
            </div>

            <!-- Action Buttons Container -->
            <div class="mt-10 flex w-full flex-col gap-3">
                <!-- Primary Action Button -->
                <Link
                    :href="register()"
                    class="flex h-[44px] w-full items-center justify-center rounded-[16px] bg-[#4F39F6] text-[14px] font-semibold text-white shadow-[0px_1px_3px_#C6D2FF,0px_1px_2px_-1px_#C6D2FF] transition-all hover:bg-[#4330d9] focus:ring-2 focus:ring-[#4F39F6] focus:ring-offset-2 focus:outline-none"
                >
                    Get started for free
                </Link>

                <!-- Secondary Action Button -->
                <Link
                    :href="login()"
                    class="flex h-[46px] w-full items-center justify-center rounded-[16px] border border-[#E2E8F0] bg-white text-[14px] font-semibold text-[#314158] transition-all hover:bg-[#F8FAFC] focus:ring-2 focus:ring-[#E2E8F0] focus:ring-offset-2 focus:outline-none"
                >
                    Sign in to your account
                </Link>
            </div>

            <!-- Features Grid Section -->
            <div class="mt-[32px] grid w-full grid-cols-3 gap-[24px]">
                <!-- Feature 1: Smart task tracking -->
                <div class="flex flex-col items-center gap-2">
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-[16px] bg-[#F1F5F9]"
                    >
                        <svg
                            class="h-5 w-5 text-[#615FFF]"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.67"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <polyline points="9 11 12 14 22 4"></polyline>
                            <path
                                d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"
                            ></path>
                        </svg>
                    </div>
                    <span
                        class="text-center text-[12px] leading-[16px] font-medium text-[#62748E]"
                    >
                        Smart task tracking
                    </span>
                </div>

                <!-- Feature 2: Team collaboration -->
                <div class="flex flex-col items-center gap-2">
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-[16px] bg-[#F1F5F9]"
                    >
                        <svg
                            class="h-5 w-5 text-[#00BC7D]"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.67"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path
                                d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"
                            ></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                    </div>
                    <span
                        class="text-center text-[12px] leading-[16px] font-medium text-[#62748E]"
                    >
                        Team collaboration
                    </span>
                </div>

                <!-- Feature 3: Progress analytics -->
                <div class="flex flex-col items-center gap-2">
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-[16px] bg-[#F1F5F9]"
                    >
                        <svg
                            class="h-5 w-5 text-[#8E51FF]"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.67"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <line x1="18" y1="20" x2="18" y2="10"></line>
                            <line x1="12" y1="20" x2="12" y2="4"></line>
                            <line x1="6" y1="20" x2="6" y2="14"></line>
                        </svg>
                    </div>
                    <span
                        class="text-center text-[12px] leading-[16px] font-medium text-[#62748E]"
                    >
                        Progress analytics
                    </span>
                </div>
            </div>
        </main>
    </div>
</template>
