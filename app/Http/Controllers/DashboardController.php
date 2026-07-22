<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use App\Models\User;
use App\Services\TaskSummaryService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private readonly TaskSummaryService $summary,
    ) {}

    /**
     * Display dashboard with read-only task list and summary cards.
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['status', 'priority', 'project_id', 'tag_id', 'search', 'sort', 'direction']);

        $tasks = Task::query()
            ->with(['assignee:id,name', 'creator:id,name', 'project:id,name', 'tags:id,name,color'])
            ->filter($filters)
            ->orderBy($this->sortColumn($request->input('sort')), $this->direction($request->input('direction')))
            ->paginate(15)
            ->withQueryString();

        // Get the summary (which includes raw trends) and transform trends to add +1 percentage point
        $summary = $this->summary->summarize();
        $transformedTrends = array_map(function ($trend) {
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
        }, $summary['trends']);

        return Inertia::render('Dashboard', [
            'pageTitle' => 'Dashboard',
            'readOnly' => true,
            'tasks' => $tasks,
            'filters' => $filters,
            'projects' => Project::orderBy('name')->get(['id', 'name']),
            'tags' => Tag::orderBy('name')->get(['id', 'name']),
            'users' => User::orderBy('name')->get(['id', 'name']),
            'summary' => array_merge(
                $summary,
                ['trends' => $transformedTrends]
            ),
        ]);
    }

    /**
     * Resolve the sort column, restricting to an allowlist.
     */
    protected function sortColumn(?string $sort): string
    {
        return in_array($sort, ['due_date', 'priority', 'created_at', 'title'], true)
            ? $sort
            : 'created_at';
    }

    /**
     * Resolve the sort direction, defaulting to ascending.
     */
    protected function direction(?string $direction): string
    {
        return $direction !== null && in_array(strtolower($direction), ['asc', 'desc'], true)
            ? strtolower($direction)
            : 'asc';
    }
}
