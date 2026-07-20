<?php

namespace App\Services;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Support\Collection;

/**
 * Shared task summary queries for the dashboard-style summary cards.
 * Centralised here so the Tasks index and the Dashboard reuse the same
 * logic instead of duplicating the queries.
 */
class TaskSummaryService
{
    /**
     * Build the summary shown on the Tasks and Dashboard pages.
     *
     * @return array{total_tasks: int, by_status: array<string, int>, by_priority: array<string, int>, overdue_count: int}
     */
    public function summarize(): array
    {
        $byStatus = Task::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $byPriority = Task::query()
            ->selectRaw('priority, count(*) as total')
            ->groupBy('priority')
            ->pluck('total', 'priority');

        $excluded = [TaskStatus::Done->value, TaskStatus::Cancelled->value];

        $overdue = Task::query()
            ->whereDate('due_date', '<', now()->toDateString())
            ->whereNotIn('status', $excluded)
            ->count();

        return [
            'total_tasks' => (int) $byStatus->sum(),
            'by_status' => $this->zeroFill(TaskStatus::cases(), $byStatus),
            'by_priority' => $this->zeroFill(TaskPriority::cases(), $byPriority),
            'overdue_count' => (int) $overdue,
        ];
    }

    /**
     * Zero-fill grouped counts for every enum case.
     *
     * @param  array<int, \BackedEnum>  $cases
     * @param  Collection<int|string, int>  $counts
     * @return array<string, int>
     */
    protected function zeroFill(array $cases, Collection $counts): array
    {
        $filled = [];

        foreach ($cases as $case) {
            $filled[$case->value] = (int) ($counts[$case->value] ?? 0);
        }

        return $filled;
    }
}
