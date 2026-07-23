<script setup lang="ts">
import { TrendingDown, TrendingUp } from '@lucide/vue';
import type { Component } from 'vue';
import { computed } from 'vue';
import { Card, CardContent } from '@/components/ui/card';
import { cn } from '@/lib/utils';

type TrendDirection = 'up' | 'down' | 'neutral';

interface TrendData {
  value: string;
  direction: TrendDirection;
  change: number;
  previous: number;
  current: number;
}

interface Props {
  icon: Component;
  label: string;
  value: number | string;
  iconClass?: string;
  trend?: TrendData;
  comparisonText?: string;
}

const props = withDefaults(defineProps<Props>(), {
  iconClass: '',
  comparisonText: 'vs last month',
});

const badgeClass = computed(() => {
  switch (props.trend?.direction) {
    case 'up':
      return 'bg-[#ECFDF5] text-[#007A55]';
    case 'down':
      return 'bg-[#FEF2F2] text-[#E7000B]';
    default:
      return 'bg-[#F1F5F9] text-[#62748E]';
  }
});
</script>

<template>
  <Card class="w-full h-[170px] rounded-[16px] border border-[#E2E8F0] bg-white shadow-none">
    <CardContent class="flex h-full flex-col justify-between p-5">
      <!-- Top Row: Icon + Trend Badge -->
      <div class="flex items-center justify-between">
        <!-- Icon Container -->
        <div
          :class="cn(
            'flex size-10 shrink-0 items-center justify-center rounded-[16px]',
            iconClass
          )"
        >
          <component :is="icon" class="size-[18px]" />
        </div>

        <!-- Trend Badge with Direction Icon -->
        <div
          v-if="trend"
          class="flex items-center gap-1 rounded-full px-2 py-1 text-xs font-semibold"
          :class="badgeClass"
        >
          <template v-if="trend.change !== 0">
            <TrendingUp
              v-if="trend.direction === 'up'"
              class="size-3.5"
            />
            <TrendingDown
              v-else-if="trend.direction === 'down'"
              class="size-3.5"
            />
            <span class="tabular-nums">{{ trend.change }}%</span>
          </template>
          <span v-else>0%</span>
        </div>
      </div>

      <!-- Bottom Content: Value, Label, and Subtext -->
      <div class="flex flex-col">
        <!-- KPI Value -->
        <p class="text-2xl font-bold leading-8 tracking-tight text-[#0F172B]">
          {{ value }}
        </p>

        <!-- Label -->
        <p class="mt-[2px] text-sm font-medium leading-5 text-[#45556C]">
          {{ label }}
        </p>

        <!-- Comparison Subtext -->
        <p
          v-if="trend || comparisonText"
          class="mt-[2px] text-xs font-normal leading-4 text-[#90A1B9]"
        >
          {{ comparisonText }}
        </p>
      </div>
    </CardContent>
  </Card>
</template>
