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
    label: 'Low',
  },
  medium: {
    badge: 'bg-[#FFFBEB] text-[#D08700] border-[#FEE685]',
    indicator: 'bg-amber-500',
    label: 'Medium',
  },
  high: {
    badge: 'bg-[#FFF7ED] text-[#CA3500] border-[#FFD6A8]',
    indicator: 'bg-orange-500',
    label: 'High',
  },
  urgent: {
    badge: 'bg-[#FEF2F2] text-[#E7000B] border-[#FFC9C9]',
    indicator: 'bg-red-500',
    label: 'Urgent',
  },
} as const;

export const taskProjectConfig = {
    
} as const;
