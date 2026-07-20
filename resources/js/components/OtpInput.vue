<script setup lang="ts">
import { computed, nextTick, onMounted, ref } from 'vue';

const model = defineModel<string>({ default: '' });

const props = withDefaults(
    defineProps<{
        length?: number;
        disabled?: boolean;
        autofocus?: boolean;
        error?: boolean;
        ariaLabel?: string;
    }>(),
    {
        length: 6,
        disabled: false,
        autofocus: false,
        error: false,
        ariaLabel: 'Verification code',
    },
);

const inputs = ref<(HTMLInputElement | null)[]>([]);
const boxes = computed(() => Array.from({ length: props.length }, (_, i) => i));
const digits = computed<string[]>(() =>
    Array.from({ length: props.length }, (_, i) => model.value[i] ?? ''),
);

function setRef(el: unknown, index: number) {
    inputs.value[index] = (el as HTMLInputElement | null) ?? null;
}

function focusBox(index: number) {
    const target = inputs.value[index];

    if (target) {
        nextTick(() => target.focus());
    }
}

function onlyDigits(value: string): string {
    return value.replace(/\D/g, '');
}

function sync(value: string[]) {
    model.value = value.join('').slice(0, props.length);
}

function distribute(text: string, start: number) {
    const next = [...digits.value];
    let i = start;

    for (const char of onlyDigits(text)) {
        if (i >= props.length) {
            break;
        }

        next[i] = char;
        i++;
    }

    sync(next);
    focusBox(Math.min(i, props.length - 1));
}

function handleInput(event: Event, index: number) {
    const target = event.target as HTMLInputElement;
    const cleaned = onlyDigits(target.value);

    if (cleaned.length > 1) {
        distribute(cleaned, index);

        return;
    }

    const next = [...digits.value];
    next[index] = cleaned.slice(-1);
    sync(next);

    if (cleaned && index < props.length - 1) {
        focusBox(index + 1);
    }
}

function handleKeydown(event: KeyboardEvent, index: number) {
    if (event.key === 'Backspace') {
        event.preventDefault();
        const next = [...digits.value];

        if (next[index]) {
            next[index] = '';
            sync(next);
        } else if (index > 0) {
            next[index - 1] = '';
            sync(next);
            focusBox(index - 1);
        }
    } else if (event.key === 'ArrowLeft' && index > 0) {
        event.preventDefault();
        focusBox(index - 1);
    } else if (event.key === 'ArrowRight' && index < props.length - 1) {
        event.preventDefault();
        focusBox(index + 1);
    }
}

function handlePaste(event: ClipboardEvent) {
    event.preventDefault();
    const text = onlyDigits(event.clipboardData?.getData('text') ?? '');

    if (text) {
        distribute(text, 0);
    }
}

function selectAll(event: FocusEvent) {
    (event.target as HTMLInputElement).select();
}

onMounted(() => {
    if (props.autofocus) {
        focusBox(0);
    }
});
</script>

<template>
    <div
        class="flex items-center justify-center gap-2"
        :class="{ 'opacity-60': disabled }"
        role="group"
        :aria-label="ariaLabel"
    >
        <input
            v-for="index in boxes"
            :key="index"
            :ref="(el) => setRef(el, index)"
            type="text"
            inputmode="numeric"
            autocomplete="one-time-code"
            maxlength="1"
            :disabled="disabled"
            :value="digits[index]"
            :aria-label="`Digit ${index + 1}`"
            class="h-11 w-11 min-w-0 rounded-[12px] border bg-white text-center text-[20px] font-semibold text-[#0F172B] outline-none transition-all focus:border-[#4F39F6] focus:ring-2 focus:ring-[#4F39F6]/30"
            :class="error ? 'border-[#F43F5E] focus:border-[#F43F5E] focus:ring-[#F43F5E]/30' : 'border-[#E2E8F0]'"
            @input="handleInput($event, index)"
            @keydown="handleKeydown($event, index)"
            @paste="handlePaste"
            @focus="selectAll"
        />
    </div>
</template>
