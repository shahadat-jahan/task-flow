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
     * @return array{
     *     total_tasks: int,
     *     by_status: array<string, int>,
     *     by_priority: array<string, int>,
     *     overdue_count: int,
     *     trends: array<string, array{value: string, direction: 'up'|'down'|'neutral'}>
     * }
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

        $totalTasks = (int) $byStatus->sum();
        $completed = (int) ($byStatus[TaskStatus::Done->value] ?? 0);
        $inProgress = (int) ($byStatus[TaskStatus::InProgress->value] ?? 0);

        return [
            'total_tasks' => $totalTasks,
            'by_status' => $this->zeroFill(TaskStatus::cases(), $byStatus),
            'by_priority' => $this->zeroFill(TaskPriority::cases(), $byPriority),
            'overdue_count' => (int) $overdue,
            'trends' => $this->trends($totalTasks, $completed, $inProgress, (int) $overdue),
        ];
    }

    /**
     * Build week-over-week trend deltas for each summary card.
     *
     * Deltas compare the current value against the same metric as it stood a
     * week ago, using each task's `created_at` as the historical cutoff. Task
     * status is not snapshotted over time, so the completed/in-progress/overdue
     * "week ago" figures assume the record's *current* status also applied a
     * week ago — a documented approximation (see DECISIONS.md).
     *
     * @return array<string, array{value: string, direction: 'up'|'down'|'neutral'}>
     */
    protected function trends(int $totalTasks, int $completed, int $inProgress, int $overdue): array
    {
        $weekAgo = now()->subWeek();
        $excluded = [TaskStatus::Done->value, TaskStatus::Cancelled->value];

        $previousTotal = Task::query()
            ->where('created_at', '<', $weekAgo)
            ->count();

        $previousCompleted = Task::query()
            ->where('status', TaskStatus::Done->value)
            ->where('created_at', '<', $weekAgo)
            ->count();

        $previousInProgress = Task::query()
            ->where('status', TaskStatus::InProgress->value)
            ->where('created_at', '<', $weekAgo)
            ->count();

        $previousOverdue = Task::query()
            ->whereDate('due_date', '<', $weekAgo->toDateString())
            ->whereNotIn('status', $excluded)
            ->where('created_at', '<', $weekAgo)
            ->count();

        return [
            'total_tasks' => $this->delta($totalTasks, $previousTotal),
            'completed' => $this->delta($completed, $previousCompleted),
            'in_progress' => $this->delta($inProgress, $previousInProgress),
            'overdue_count' => $this->delta($overdue, $previousOverdue),
        ];
    }

    /**
     * Compute a single percentage delta between a current and previous count.
     *
     * @return array{value: string, direction: 'up'|'down'|'neutral'}
     */
    protected function delta(int $current, int $previous): array
    {
        if ($previous === 0) {
            return $current > 0
                ? ['value' => 'New', 'direction' => 'up']
                : ['value' => '0%', 'direction' => 'neutral'];
        }

        $change = (int) round((($current - $previous) / $previous) * 100);

        if ($change === 0) {
            return ['value' => '0%', 'direction' => 'neutral'];
        }

        return [
            'value' => sprintf('%+d%%', $change),
            'direction' => $change > 0 ? 'up' : 'down',
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
