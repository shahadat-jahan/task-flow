<script setup lang="ts">
import type { LinkComponentBaseProps, Method } from '@inertiajs/core';
import { Link } from '@inertiajs/vue3';
import { cn } from '@/lib/utils'; // যদি আপনার প্রোজেক্টে shadcn এর cn হেল্পার থাকে

type Props = {
    href: LinkComponentBaseProps['href'];
    tabindex?: number;
    method?: Method;
    as?: string;
    class?: string;
};

defineProps<Props>();
</script>

<template>
    <Link
        :href="href"
        :tabindex="tabindex"
        :method="method"
        :as="as"
        :class="cn(
            'text-[16px] font-semibold text-[#4F39F6] transition-all hover:underline underline-offset-4',
            $props.class
        )"
    >
        <slot />
    </Link>
</template>
