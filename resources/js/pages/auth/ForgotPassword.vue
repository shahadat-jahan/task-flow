<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { email } from '@/routes/auth/password';
import { login } from '@/routes';

defineOptions({
    layout: {
        title: 'Forgot password',
        description: "Enter your email and we'll send you a 6-digit code to reset your password.",
    },
});
</script>

<template>
    <Head title="Forgot password" />

    <Form
        v-bind="email.form()"
        v-slot="{ errors, processing }"
        class="w-full max-w-[448px] flex flex-col gap-4 font-['Inter']"
    >
        <div class="grid gap-1.5">
            <Label for="email" class="text-[12px] font-semibold leading-[16px] text-[#314158]">Email address</Label>
            <div class="relative w-full">
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
                    autocomplete="email"
                    placeholder="you@company.com"
                    class="!h-[42px] w-full bg-white border border-[#E2E8F0] !rounded-[16px] pl-[40px] pr-4 text-[14px] font-normal leading-[20px] text-[#0F172B] placeholder-[#90A1B9] focus-visible:ring-1 focus-visible:ring-[#4F39F6] focus-visible:border-[#4F39F6]"
                />
            </div>
            <InputError :message="errors.email" />
        </div>

        <Button
            type="submit"
            class="w-full !h-[44px] bg-[#4F39F6] hover:bg-[#432DD7] text-white text-[14px] font-semibold !rounded-[16px] shadow-[0px_1px_3px_#C6D2FF,0px_1px_2px_-1px_#C6D2FF] transition-colors mt-1"
            :tabindex="1"
            :disabled="processing"
            data-test="send-reset-code-button"
        >
            <Spinner v-if="processing" class="mr-2 h-4 w-4 border-white" />
            Send reset code
        </Button>

        <div class="text-center text-[12px] font-normal text-[#62748E] pt-3 flex items-center justify-center gap-1.5">
            Remembered your password?
            <TextLink :href="login.url()" class="text-[16px] font-semibold text-[#4F39F6] hover:underline" :tabindex="2">
                Back to sign in
            </TextLink>
        </div>
    </Form>
</template>
