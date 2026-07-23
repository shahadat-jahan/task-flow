import { Circle, RefreshCw, Eye, CheckCircle2, X } from '@lucide/vue';

export const taskStatusConfig = {
  todo: {
    badge: 'bg-[#F3F4F6] text-[#4B5563] border-[#E5E7EB]',
    icon: Circle,
    label: 'Todo',
  },
  in_progress: {
    badge: 'bg-[#EFF6FF] text-[#2563EB] border-[#BFDBFE]',
    icon: RefreshCw,
    label: 'In Progress',
  },
  in_review: {
    badge: 'bg-[#F3E8FF] text-[#9333EA] border-[#E9D5FF]',
    icon: Eye,
    label: 'In Review',
  },
  done: {
    badge: 'bg-[#ECFDF5] text-[#059669] border-[#A7F3D0]',
    icon: CheckCircle2,
    label: 'Done',
  },
  cancelled: {
    badge: 'bg-[#F3F4F6] text-[#6B7280] border-[#E5E7EB]',
    icon: X,
    label: 'Cancelled',
  },
} as const;

export const taskPriorityConfig = {
  low: {
    badge: 'bg-[#F1F5F9] text-[#64748B] border-[#E2E8F0]',
    indicator: 'bg-[#94A3B8]',
    dotSmall: 'bg-[#94A3B8] size-1.5 shrink-0 rounded-full',
    label: 'Low',
  },
  medium: {
    badge: 'bg-[#FFFBEB] text-[#D97706] border-[#FEF3C7]',
    indicator: 'bg-[#F59E0B]',
    dotSmall: 'bg-[#F59E0B] size-1.5 shrink-0 rounded-full',
    label: 'Medium',
  },
  high: {
    badge: 'bg-[#FFF7ED] text-[#EA580C] border-[#FFEDD5]',
    indicator: 'bg-[#F97316]',
    dotSmall: 'bg-[#F97316] size-1.5 shrink-0 rounded-full',
    label: 'High',
  },
  urgent: {
    badge: 'bg-[#FEF2F2] text-[#DC2626] border-[#FECACA]',
    indicator: 'bg-[#EF4444]',
    dotSmall: 'bg-[#EF4444] size-1.5 shrink-0 rounded-full',
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
