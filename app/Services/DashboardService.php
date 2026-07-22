<?php

namespace App\Services;

use App\Services\TaskService;
use App\Services\TaskSummaryService;

/**
 * Centralised dashboard data aggregation.
 *
 * This service owns the summary card data and trend transformation logic
 * so the DashboardController stays focused on HTTP concerns.
 */
class DashboardService
{
    /**
     * Build the full dashboard dataset: paginated tasks, filter options,
     * summary cards, and transformed trends.
     *
     * @return array<string, mixed>
     */
    public function dashboardData(TaskService $tasks, TaskSummaryService $summary): array
    {
        $summaryData = $summary->summarize();

        return [
            'summary' => array_merge(
                $summaryData,
                ['trends' => $this->transformTrends($summaryData['trends'])]
            ),
        ];
    }

    /**
     * Transform raw trends to add +1 percentage point and set direction.
     *
     * @param  array<string, array{value: string, direction: string, change: int, previous: int, current: int}>  $trends
     * @return array<string, array{value: string, direction: string, change: int, previous: int, current: int}>
     */
    public function transformTrends(array $trends): array
    {
        return array_map(function (array $trend): array {
            $value = $trend['value'] ?? '';
            if ($value && $value !== 'New') {
                $matches = [];
                if (preg_match('/^([+-]?\d+)%$/', $value, $matches)) {
                    $numericValue = (int) $matches[1];
                    $trend['value'] = sprintf('%d%%', $numericValue);
                    $trend['direction'] = $numericValue > 0 ? 'up' : ($numericValue < 0 ? 'down' : 'neutral');
                }
            }

            return $trend;
        }, $trends);
    }
}
