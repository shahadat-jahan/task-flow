<script setup lang="ts">
import type { Component } from 'vue';
import { computed } from 'vue';
import { Card, CardContent } from '@/components/ui/card';

type TrendDirection = 'up' | 'down' | 'neutral';

const props = defineProps<{
    icon: Component;
    label: string;
    value: number;
    /** Tailwind classes for the icon badge, e.g. 'bg-indigo-50 text-indigo-600'. */
    iconClass: string;
    trend?: { value: string; direction: TrendDirection };
}>();

const trendClass = computed(() => {
    switch (props.trend?.direction) {
        case 'up':
            return 'text-emerald-600';
        case 'down':
            return 'text-red-600';
        default:
            return 'text-muted-foreground';
    }
});
</script>

<template>
    <Card>
        <CardContent class="flex items-start justify-between gap-3 p-5">
            <div class="min-w-0">
                <p class="text-sm text-muted-foreground">{{ label }}</p>
                <p
                    class="mt-1 text-3xl font-semibold tracking-tight tabular-nums"
                >
                    {{ value }}
                </p>
                <p
                    v-if="trend"
                    class="mt-1 text-xs font-medium"
                    :class="trendClass"
                >
                    {{ trend.value }}
                    <span class="text-muted-foreground">vs last week</span>
                </p>
            </div>
            <span
                class="flex size-10 shrink-0 items-center justify-center rounded-lg"
                :class="iconClass"
            >
                <component :is="icon" class="size-5" />
            </span>
        </CardContent>
    </Card>
</template>
