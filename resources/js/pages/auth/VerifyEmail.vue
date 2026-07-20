<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import OtpInput from '@/components/OtpInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { resend, verify } from '@/routes/verification';

const code = ref('');

defineProps<{
    email: string;
    status?: string;
}>();
</script>

<template>
    <Head title="Check your email" />

    <div
        v-if="status"
        class="mb-4 text-center text-sm font-medium text-green-600"
    >
        {{ status }}
    </div>

    <Form
        v-bind="verify.form()"
        v-slot="{ errors, processing }"
        class="w-full max-w-[448px] flex flex-col gap-6 font-['Inter']"
    >
        <!-- Heading (in-template so the email stays dynamic) -->
        <div class="mb-2 space-y-2">
            <h1 class="text-[24px] font-bold leading-[32px] text-[#0F172B]">
                Check your email
            </h1>
            <span class="text-[14px] leading-[20px] text-[#62748E]">
                We sent a 6-digit verification code to {{ email }}
            </span>
        </div>

        <input type="hidden" name="email" :value="email" />
        <input type="hidden" name="code" :value="code" />

        <div class="flex flex-col items-center gap-2">
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

        <Button
            type="submit"
            class="w-full !h-[44px] bg-[#4F39F6] hover:bg-[#432DD7] text-white text-[14px] font-semibold !rounded-[16px] shadow-[0px_1px_3px_#C6D2FF,0px_1px_2px_-1px_#C6D2FF] transition-colors"
            :tabindex="1"
            :disabled="processing || code.length < 6"
            data-test="verify-email-button"
        >
            <Spinner v-if="processing" class="mr-2 h-4 w-4 border-white" />
            Verify &amp; continue
        </Button>

        <!-- Resend -->
        <div class="text-center text-[12px] text-[#90A1B9]">
            <p class="mb-2">Didn't receive the code?</p>
            <Form
                v-bind="resend.form()"
                v-slot="{ processing: resending }"
                class="contents"
            >
                <input type="hidden" name="email" :value="email" />
                <Button
                    type="submit"
                    variant="link"
                    class="!h-auto !p-0 inline-flex items-center gap-1.5 text-[12px] font-semibold text-[#4F39F6] hover:underline"
                    :disabled="resending"
                >
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 12a9 9 0 0 1 15-6.7L21 8" />
                        <path d="M21 3v5h-5" />
                        <path d="M21 12a9 9 0 0 1-15 6.7L3 16" />
                        <path d="M3 21v-5h5" />
                    </svg>
                    Resend code
                </Button>
            </Form>
        </div>

        <!-- Back to sign in -->
        <div class="pt-2 text-center">
            <TextLink
                :href="login.url()"
                class="inline-flex items-center gap-1 text-[14px] font-semibold text-[#4F39F6] transition-all hover:gap-2"
                :tabindex="2"
            >
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m15 18-6-6 6-6" />
                </svg>
                Back to sign in
            </TextLink>
        </div>
    </Form>
</template>
