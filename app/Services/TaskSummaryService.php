<?php

namespace App\Services;

use App\Models\Task;

/**
 * Shared task summary queries (e.g. status counts for the dashboard-style
 * summary cards). Centralised here so the Tasks index and any future
 * dashboard reuse the same logic instead of duplicating the query.
 */
class TaskSummaryService
{
    /**
     * Count of tasks grouped by status.
     *
     * @return array<string, int>
     */
    public function statusCounts(): array
    {
        return Task::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();
    }
}
