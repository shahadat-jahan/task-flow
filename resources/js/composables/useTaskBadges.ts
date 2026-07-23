import { Circle, RefreshCw, Eye, CheckCircle2, X } from '@lucide/vue';

export const taskStatusConfig = {
  todo: {
    badge: 'bg-white text-slate-500 border-slate-200',
    icon: Circle,
    label: 'Todo',
  },
  in_progress: {
    badge: 'bg-[#EFF6FF] text-[#1447E6] border-[#BEDBFF]',
    icon: RefreshCw,
    label: 'In Progress',
  },
  in_review: {
    badge: 'bg-[#FAF5FF] text-[#9810FA] border-[#E9D4FF]',
    icon: Eye,
    label: 'In Review',
  },
  done: {
    badge: 'bg-[#F0FDF4] text-[#00A63E] border-[#B9F8CF]',
    icon: CheckCircle2,
    label: 'Done',
  },
  cancelled: {
    badge: 'bg-white text-slate-400 border-slate-200',
    icon: X,
    label: 'Cancelled',
  },
} as const;

export const taskPriorityConfig = {
  low: {
    badge: 'bg-slate-100 text-slate-500 border-slate-200',
    indicator: 'bg-slate-400',
    dotSmall: 'bg-slate-400 size-1.5 shrink-0 rounded-full',
    label: 'Low',
  },
  medium: {
    badge: 'bg-[#FFFBEB] text-[#D08700] border-[#FEE685]',
    indicator: 'bg-amber-500',
    dotSmall: 'bg-amber-500 size-1.5 shrink-0 rounded-full',
    label: 'Medium',
  },
  high: {
    badge: 'bg-[#FFF7ED] text-[#CA3500] border-[#FFD6A8]',
    indicator: 'bg-orange-500',
    dotSmall: 'bg-orange-500 size-1.5 shrink-0 rounded-full',
    label: 'High',
  },
  urgent: {
    badge: 'bg-[#FEF2F2] text-[#E7000B] border-[#FFC9C9]',
    indicator: 'bg-red-500',
    dotSmall: 'bg-red-500 size-1.5 shrink-0 rounded-full',
    label: 'Urgent',
  },
} as const;

export const taskProjectConfig = {
  section: {
    header: 'px-3 pb-2 text-xs font-semibold tracking-wider text-muted-foreground uppercase',
    list: 'space-y-1',
  },
  item: {
    row: 'flex items-center gap-3 rounded-2xl px-3 py-2 text-sm text-slate-600 hover:bg-slate-50',
    badge: 'inline-flex items-center gap-1.5 rounded-[10px] border px-2 py-0.5 text-xs font-medium',
    dot: 'size-2.5 shrink-0 rounded-full',
    dotSmall: 'size-1.5 shrink-0 rounded-full',
    name: 'truncate',
    count: 'ml-auto text-xs tabular-nums text-slate-400',
  },
} as const;
