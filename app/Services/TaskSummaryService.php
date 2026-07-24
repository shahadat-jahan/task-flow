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
     *     trends: array<string, array{value: string, direction: 'up'|'down'|'neutral', change: int, previous: int, current: int}>
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
     * Build month-over-month trend deltas for each summary card.
     *
     * Deltas compare the current value against the same metric as it stood a
     * month ago, using each task's `created_at` as the historical cutoff. Task
     * status is not snapshotted over time, so the completed/in-progress/overdue
     * "month ago" figures assume the record's *current* status also applied a
     * month ago — a documented approximation (see DECISIONS.md).
     *
     * @return array<string, array{value: string, direction: 'up'|'down'|'neutral', change: int, previous: int, current: int}>
     */
    protected function trends(int $totalTasks, int $completed, int $inProgress, int $overdue): array
    {
        $monthAgo = now()->subMonth();
        $excluded = [TaskStatus::Done->value, TaskStatus::Cancelled->value];

        $previousTotal = Task::query()
            ->where('created_at', '<', $monthAgo)
            ->count();

        $previousCompleted = Task::query()
            ->where('status', TaskStatus::Done->value)
            ->where('created_at', '<', $monthAgo)
            ->count();

        $previousInProgress = Task::query()
            ->where('status', TaskStatus::InProgress->value)
            ->where('created_at', '<', $monthAgo)
            ->count();

        $previousOverdue = Task::query()
            ->whereDate('due_date', '<', $monthAgo->toDateString())
            ->whereNotIn('status', $excluded)
            ->where('created_at', '<', $monthAgo)
            ->count();

        return [
            'total_tasks' => $this->delta($totalTasks, $previousTotal),
            'completed' => $this->delta($completed, $previousCompleted),
            'in_progress' => $this->delta($inProgress, $previousInProgress),
            'overdue_count' => $this->invertDelta($this->delta($overdue, $previousOverdue)),
        ];
    }

    /**
     * Compute a single percentage delta between a current and previous count.
     *
     * @return array{value: string, direction: 'up'|'down'|'neutral', change: int, previous: int, current: int}
     */
    protected function delta(int $current, int $previous): array
    {
        if ($previous === 0) {
            $value = $current > 0 ? '100%' : '0%';
            $direction = $current > 0 ? 'up' : 'neutral';
            $change = $current > 0 ? 100 : 0;

            return [
                'value' => $value,
                'direction' => $direction,
                'change' => $change,
                'previous' => $previous,
                'current' => $current,
            ];
        }

        $rawChange = (($current - $previous) / $previous) * 100;
        $absoluteChange = abs(round($rawChange));

        if ($rawChange == 0) {
            return [
                'value' => '0%',
                'direction' => 'neutral',
                'change' => 0,
                'previous' => $previous,
                'current' => $current,
            ];
        }

        return [
            'value' => "{$absoluteChange}%",
            'direction' => $rawChange > 0 ? 'up' : 'down',
            'change' => $absoluteChange,
            'previous' => $previous,
            'current' => $current,
        ];
    }

    /**
     * Invert trend direction for negative metrics.
     *
     * For metrics like "overdue", an increase is bad (should show red/down),
     * but the delta calculation treats any increase as "up". This method
     * flips the direction so the frontend displays the correct color.
     */
    protected function invertDelta(array $delta): array
    {
        if ($delta['direction'] === 'up') {
            $delta['direction'] = 'down';
        } elseif ($delta['direction'] === 'down') {
            $delta['direction'] = 'up';
        }

        return $delta;
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
