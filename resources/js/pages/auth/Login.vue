<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { register } from '@/routes';
import { request } from '@/routes/auth/password';
import { store } from '@/routes/login';

defineOptions({
    layout: {
        title: 'Sign in to TaskFlow',
        description: 'Enter your credentials to access your workspace',
    },
});

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();
</script>

<template>
    <Head title="Log in" />

    <div
        v-if="status"
        class="mb-4 text-center text-sm font-medium text-green-600"
    >
        {{ status }}
    </div>

    <Form
        v-bind="store.form()"
        :reset-on-success="['password']"
        v-slot="{ errors, processing }"
        class="w-full max-w-[448px] flex flex-col gap-4 font-['Inter']"
    >
        <div class="grid gap-4">
            <!-- Email Field Container -->
            <div class="grid gap-1.5">
                <Label for="email" class="text-[12px] font-semibold leading-[16px] text-[#314158]">Email address</Label>
                <div class="relative w-full">
                    <!-- Mail Icon -->
                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-[#90A1B9] z-10 pointer-events-none">
                        <svg class="h-[15px] w-[15px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                            <polyline points="22,6 12,13 2,6"></polyline>
                        </svg>
                    </span>
                    <Input
                        id="email"
                        type="email"
                        name="email"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="email"
                        placeholder="you@company.com"
                        class="!h-[42px] w-full bg-white border border-[#E2E8F0] !rounded-[16px] pl-[40px] pr-4 text-[14px] font-normal leading-[20px] text-[#0F172B] placeholder-[#90A1B9] focus-visible:ring-1 focus-visible:ring-[#4F39F6] focus-visible:border-[#4F39F6]"
                    />
                </div>
                <InputError :message="errors.email" />
            </div>

            <!-- Password Field Container -->
            <div class="grid gap-1.5">
                <div class="flex items-center justify-between">
                    <Label for="password" class="text-[12px] font-semibold leading-[16px] text-[#314158]">Password</Label>
                    <TextLink
                        v-if="canResetPassword"
                        :href="request.url()"
                        class="text-[12px] font-medium leading-[16px] text-[#4F39F6] hover:underline"
                        :tabindex="5"
                    >
                        Forgot password?
                    </TextLink>
                </div>
                <div class="relative w-full">
                    <!-- Lock Icon -->
                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-[#90A1B9] z-10 pointer-events-none">
                        <svg class="h-[15px] w-[15px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                    </span>
                    <PasswordInput
                        id="password"
                        name="password"
                        required
                        :tabindex="2"
                        autocomplete="current-password"
                        placeholder="••••••••"
                        class="!h-[42px] w-full bg-white border border-[#E2E8F0] !rounded-[16px] pl-[40px] pr-10 text-[14px] font-normal leading-[20px] text-[#0F172B] placeholder-[#90A1B9] focus-visible:ring-1 focus-visible:ring-[#4F39F6] focus-visible:border-[#4F39F6]"
                    />
                </div>
                <InputError :message="errors.password" />
            </div>

            <!-- Submit Sign In Button -->
            <Button
                type="submit"
                class="w-full !h-[44px] bg-[#4F39F6] hover:bg-[#432DD7] text-white text-[14px] font-semibold !rounded-[16px] shadow-[0px_1px_3px_#C6D2FF,0px_1px_2px_-1px_#C6D2FF] transition-colors mt-1"
                :tabindex="4"
                :disabled="processing"
                data-test="login-button"
            >
                <Spinner v-if="processing" class="mr-2 h-4 w-4 border-white" />
                Sign in
            </Button>

            <!-- Divider -->
            <div class="relative flex items-center justify-center py-3">
                <div class="absolute inset-x-0 h-px bg-[#E2E8F0]"></div>
                <span class="relative z-10 bg-[#F8FAFC] px-3 text-[12px] font-normal text-[#90A1B9]">
                    or continue with
                </span>
            </div>

            <!-- Google OAuth Button Fix -->
            <Button
    variant="outline"
    type="button"
    class="w-full !h-[42px] border border-[#E2E8F0] !bg-white text-[#314158] hover:bg-slate-50 hover:text-[#314158] text-[14px] font-medium !rounded-[16px] flex items-center justify-center gap-3 transition-colors !shadow-none"
    :tabindex="6"
>
    <svg class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24">
        <path fill="#EA4335" d="M12 5.04c1.64 0 3.12.56 4.28 1.67l3.2-3.2C17.52 1.58 14.96 1 12 1 7.35 1 3.4 3.65 1.48 7.5l3.68 2.85c.88-2.64 3.36-4.31 6.84-4.31z"/>
        <path fill="#FBBC05" d="M1.48 7.5C.54 9.36 0 11.42 0 13.6c0 2.18.54 4.24 1.48 6.1l3.68-2.85c-.24-.72-.36-1.49-.36-2.25s.12-1.53.36-2.25L1.48 7.5z"/>
        <path fill="#34A853" d="M12 22.2c2.96 0 5.44-.98 7.24-2.66l-3.52-2.73c-1 .67-2.28 1.08-3.72 1.08-3.48 0-5.96-1.67-6.84-4.31L1.48 16.43c1.92 3.85 5.87 6.5 10.52 6.5z"/>
        <path fill="#4285F4" d="M23.48 12.3c0-.82-.07-1.6-.2-2.3H12v4.4h6.44c-.28 1.47-1.11 2.71-2.36 3.55l3.52 2.73c2.06-1.9 3.32-4.71 3.32-8.38z"/>
    </svg>
    Continue with Google
</Button>
        </div>

        <!-- Footer -->
        <div class="text-center text-[12px] font-normal text-[#62748E] pt-3 flex items-center justify-center gap-1.5">
            Don't have an account?
            <TextLink :href="register()" :tabindex="7" class="text-[16px] font-semibold text-[#4F39F6] hover:underline">
                Sign up free
            </TextLink>
        </div>
    </Form>
</template>
