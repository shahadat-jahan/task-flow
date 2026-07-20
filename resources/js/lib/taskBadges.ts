export const statusBadgeClass: Record<string, string> = {
    todo: 'bg-slate-100 text-slate-700',
    in_progress: 'bg-amber-100 text-amber-700',
    in_review: 'bg-blue-100 text-blue-700',
    done: 'bg-emerald-100 text-emerald-700',
    cancelled: 'bg-red-100 text-red-700',
};

export const priorityBadgeClass: Record<string, string> = {
    low: 'bg-slate-100 text-slate-700',
    medium: 'bg-amber-100 text-amber-700',
    high: 'bg-red-100 text-red-700',
};
