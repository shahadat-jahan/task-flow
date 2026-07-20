<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import OtpInput from '@/components/OtpInput.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { usePasswordStrength } from '@/composables/usePasswordStrength';
import { login } from '@/routes';
import { resend, update } from '@/routes/auth/password';

const code = ref('');
const password = ref('');
const { strength, strengthColors, strengthLabels } = usePasswordStrength(password);

const passwordMin = computed(() => {
    const match = props.passwordRules.match(/minlength:(\d+)/);

    return match ? Number(match[1]) : 8;
});

const props = defineProps<{
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

    <div class="w-full max-w-[448px] flex flex-col gap-4 font-['Inter']">
        <Form
            v-bind="update.form()"
            :reset-on-success="['password', 'password_confirmation']"
            v-slot="{ errors, processing }"
            class="contents"
        >
        <input type="hidden" name="email" :value="email" />
        <input type="hidden" name="code" :value="code" />

        <div class="grid gap-1.5">
            <Label for="code" class="text-[12px] font-semibold leading-[16px] text-[#314158]">Verification code</Label>
            <div class="flex flex-col items-start gap-2">
                <OtpInput
                    v-model="code"
                    :length="6"
                    :disabled="processing"
                    :error="!!errors.code"
                    autofocus
                    aria-label="Email verification code"
                />
                <InputError :message="errors.code" />
            </div>
        </div>

        <div class="grid gap-1.5">
            <Label for="password" class="text-[12px] font-semibold leading-[16px] text-[#314158]">New password</Label>
            <div class="relative w-full h-[42px]">
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
                :tabindex="1"
                autocomplete="new-password"
                placeholder="New Password"
                :passwordrules="passwordRules"
                v-model="password"
                class="!h-[42px] w-full bg-white border border-[#E2E8F0] !rounded-[16px] pl-[40px] pr-10 text-[14px] font-normal leading-[20px] text-[#0F172B] placeholder-[#90A1B9] focus-visible:ring-1 focus-visible:ring-[#4F39F6] focus-visible:border-[#4F39F6]"
            />
            </div>
            <!-- Password Strength Indicator Bars -->
            <div class="flex flex-row w-full gap-1 pt-2">
                <div
                    v-for="i in 4"
                    :key="i"
                    class="h-1 flex-1 rounded-full transition-colors"
                    :style="{ backgroundColor: i <= strength ? strengthColors[strength - 1] : '#E2E8F0' }"
                ></div>
            </div>
            <p
                v-if="strength > 0"
                class="pt-1 text-[12px] font-medium"
                :style="{ color: strengthColors[strength - 1] }"
            >
                {{ strengthLabels[strength - 1] }} password
            </p>
            <p class="pt-1 text-[12px] text-[#90A1B9]">Minimum {{ passwordMin }} characters.</p>
            <InputError :message="errors.password" />
        </div>

        <div class="grid gap-1.5">
            <Label for="password_confirmation" class="text-[12px] font-semibold leading-[16px] text-[#314158]">Confirm password</Label>
            <div class="relative w-full h-[42px]">
            <!-- Lock Icon -->
                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-[#90A1B9] z-10 pointer-events-none">
                        <svg class="h-[15px] w-[15px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                    </span>
            <PasswordInput
                id="password_confirmation"
                name="password_confirmation"
                required
                :tabindex="2"
                autocomplete="new-password"
                placeholder="Confirm New Password"
                class="!h-[42px] w-full bg-white border border-[#E2E8F0] !rounded-[16px] pl-[40px] pr-10 text-[14px] font-normal leading-[20px] text-[#0F172B] placeholder-[#90A1B9] focus-visible:ring-1 focus-visible:ring-[#4F39F6] focus-visible:border-[#4F39F6]"
            />
            </div>
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
        </Form>

        <!-- Resend + back (outside the update form to avoid nested forms) -->
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
    </div>
</template>
