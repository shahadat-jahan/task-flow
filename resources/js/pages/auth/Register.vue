<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { usePasswordStrength } from '@/composables/usePasswordStrength';
import { login } from '@/routes';
import { store } from '@/routes/register';

const props = defineProps<{
    passwordRules: string;
}>();

const password = ref('');
const { strength, strengthColors, strengthLabels } = usePasswordStrength(password);

const passwordMin = computed(() => {
    const match = props.passwordRules.match(/minlength:(\d+)/);

    return match ? Number(match[1]) : 8;
});

defineOptions({
    layout: {
        title: 'Create your account',
        description: 'Start managing tasks like a pro - it\'s free forever',
    },
});
</script>

<template>
    <Head title="Register" />

        <Form
            v-bind="store.form()"
            :reset-on-success="['password']"
            v-slot="{ errors, processing }"
            class="flex flex-col pt-8"
        >
            <div class="flex flex-col gap-4">
                <!-- Full name Field -->
                <div class="flex flex-col items-start gap-1.5">
                    <Label for="name" class="text-[12px] font-semibold leading-[16px] text-[#314158]">Full name</Label>
                    <div class="relative w-full h-[42px]">
                        <!-- User Icon Spec -->
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-[#90A1B9] z-10">
                            <svg class="h-[15px] w-[15px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                        </span>
                        <Input
                            id="name"
                            type="text"
                            required
                            autofocus
                            :tabindex="1"
                            autocomplete="name"
                            name="name"
                            placeholder="Alex Morgan"
                            class="absolute inset-0 h-[42px] w-full bg-white border border-[#E2E8F0] rounded-[16px] pl-[40px] pr-4 text-[14px] font-normal leading-[20px] text-[#0F172B] placeholder-[#90A1B9] focus-visible:ring-1 focus-visible:ring-[#4F39F6] focus-visible:border-[#4F39F6]"
                        />
                    </div>
                    <InputError :message="errors.name" />
                </div>

                <!-- Work email Field -->
                <div class="flex flex-col items-start gap-1.5">
                    <Label for="email" class="text-[12px] font-semibold leading-[16px] text-[#314158]">Work email</Label>
                    <div class="relative w-full h-[42px]">
                        <!-- Mail Icon Spec -->
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-[#90A1B9] z-10">
                            <svg class="h-[15px] w-[15px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                <polyline points="22,6 12,13 2,6"></polyline>
                            </svg>
                        </span>
                        <Input
                            id="email"
                            type="email"
                            required
                            :tabindex="2"
                            autocomplete="email"
                            name="email"
                            placeholder="you@company.com"
                            class="absolute inset-0 h-[42px] w-full bg-white border border-[#E2E8F0] rounded-[16px] pl-[40px] pr-4 text-[14px] font-normal leading-[20px] text-[#0F172B] placeholder-[#90A1B9] focus-visible:ring-1 focus-visible:ring-[#4F39F6] focus-visible:border-[#4F39F6]"
                        />
                    </div>
                    <InputError :message="errors.email" />
                </div>

                <!-- Password Field -->
                <div class="flex flex-col items-start gap-1.5">
                    <Label for="password" class="text-[12px] font-semibold leading-[16px] text-[#314158]">Password</Label>
                    <div class="relative w-full h-[42px]">
                        <!-- Lock Icon Spec -->
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-[#90A1B9] z-10">
                            <svg class="h-[15px] w-[15px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                            </svg>
                        </span>
                        <PasswordInput
                            id="password"
                            required
                            :tabindex="3"
                            autocomplete="new-password"
                            name="password"
                            placeholder="Min. 8 characters"
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

                <!-- Submit Button -->
                <Button
                    type="submit"
                    class="w-full h-[44px] bg-[#4F39F6] hover:bg-[#432DD7] text-white text-[14px] font-semibold rounded-[16px] shadow-[0px_1px_3px_#C6D2FF,0px_1px_2px_-1px_#C6D2FF] transition-colors mt-2"
                    :tabindex="5"
                    :disabled="processing"
                    data-test="register-user-button"
                >
                    <Spinner v-if="processing" class="mr-2 h-4 w-4 border-white" />
                    Create account
                </Button>

                <!-- Terms & Privacy Text -->
                <p class="text-center text-[12px] font-normal leading-[16px] text-[#90A1B9] px-2 py-1.5">
                    By creating an account, you agree to our
                    <a href="#" class="text-[16px] font-medium leading-[24px] text-[#4F39F6] hover:underline mx-0.5">Terms</a>
                    and
                    <a href="#" class="text-[16px] font-medium leading-[24px] text-[#4F39F6] hover:underline mx-0.5">Privacy Policy</a>.
                </p>
            </div>

            <!-- Already have an account footer -->
            <div class="text-center text-[12px] font-normal leading-[16px] text-[#62748E] pt-6 flex items-center justify-center gap-1">
                Already have an account?
                <TextLink
                    :href="login()"
                    class="text-[16px] font-semibold leading-[24px] text-[#4F39F6] hover:underline"
                    :tabindex="6"
                >
                    Sign in
                </TextLink>
            </div>
        </Form>
</template>
