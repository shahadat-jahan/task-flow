<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { resend, update } from '@/routes/auth/password';

defineProps<{
    email: string;
    passwordRules: string;
    status?: string;
}>();

defineOptions({
    layout: {
        title: 'Reset password',
        description: 'Enter the 6-digit code we sent you and choose a new password.',
    },
});
</script>

<template>
    <Head title="Reset password" />

    <div
        v-if="status"
        class="mb-4 text-center text-sm font-medium text-green-600"
    >
        {{ status }}
    </div>

    <Form
        v-bind="update.form()"
        :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing }"
        class="w-full max-w-[448px] flex flex-col gap-4 font-['Inter']"
    >
        <input type="hidden" name="email" :value="email" />

        <div class="grid gap-1.5">
            <Label for="code" class="text-[12px] font-semibold leading-[16px] text-[#314158]">Verification code</Label>
            <Input
                id="code"
                type="text"
                inputmode="numeric"
                maxlength="6"
                name="code"
                required
                autofocus
                autocomplete="one-time-code"
                placeholder="123456"
                class="!h-[42px] w-full bg-white border border-[#E2E8F0] !rounded-[16px] px-4 text-[14px] font-normal tracking-[0.5em] leading-[20px] text-[#0F172B] placeholder-[#90A1B9] focus-visible:ring-1 focus-visible:ring-[#4F39F6] focus-visible:border-[#4F39F6]"
            />
            <InputError :message="errors.code" />
        </div>

        <div class="grid gap-1.5">
            <Label for="password" class="text-[12px] font-semibold leading-[16px] text-[#314158]">New password</Label>
            <PasswordInput
                id="password"
                name="password"
                required
                :tabindex="1"
                autocomplete="new-password"
                placeholder="Min. 8 characters"
                :passwordrules="passwordRules"
                class="!h-[42px] w-full bg-white border border-[#E2E8F0] !rounded-[16px] pl-[40px] pr-10 text-[14px] font-normal leading-[20px] text-[#0F172B] placeholder-[#90A1B9] focus-visible:ring-1 focus-visible:ring-[#4F39F6] focus-visible:border-[#4F39F6]"
            />
            <InputError :message="errors.password" />
        </div>

        <div class="grid gap-1.5">
            <Label for="password_confirmation" class="text-[12px] font-semibold leading-[16px] text-[#314158]">Confirm password</Label>
            <PasswordInput
                id="password_confirmation"
                name="password_confirmation"
                required
                :tabindex="2"
                autocomplete="new-password"
                placeholder="Min. 8 characters"
                class="!h-[42px] w-full bg-white border border-[#E2E8F0] !rounded-[16px] pl-[40px] pr-10 text-[14px] font-normal leading-[20px] text-[#0F172B] placeholder-[#90A1B9] focus-visible:ring-1 focus-visible:ring-[#4F39F6] focus-visible:border-[#4F39F6]"
            />
        </div>

        <Button
            type="submit"
            class="w-full !h-[44px] bg-[#4F39F6] hover:bg-[#432DD7] text-white text-[14px] font-semibold !rounded-[16px] shadow-[0px_1px_3px_#C6D2FF,0px_1px_2px_-1px_#C6D2FF] transition-colors mt-1"
            :tabindex="3"
            :disabled="processing"
            data-test="reset-password-button"
        >
            <Spinner v-if="processing" class="mr-2 h-4 w-4 border-white" />
            Reset password
        </Button>

        <div class="flex items-center justify-between pt-1">
            <Form
                v-bind="resend.form()"
                v-slot="{ processing: resending }"
                class="contents"
            >
                <input type="hidden" name="email" :value="email" />
                <Button
                    type="submit"
                    variant="link"
                    class="!h-auto !p-0 text-[12px] font-medium text-[#4F39F6] hover:underline"
                    :disabled="resending"
                >
                    Resend code
                </Button>
            </Form>
            <TextLink :href="login.url()" class="text-[12px] font-medium text-[#4F39F6] hover:underline" :tabindex="4">
                Back to sign in
            </TextLink>
        </div>
    </Form>
</template>
