<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use App\Services\TaskService;
use App\Services\TaskSummaryService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private readonly TaskService $tasks,
        private readonly TaskSummaryService $summary,
        private readonly DashboardService $dashboard,
    ) {}

    /**
     * Display dashboard with read-only task list and summary cards.
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['status', 'priority', 'project_id', 'tag_id', 'search', 'sort', 'direction']);
        $user =  $request->user()?->name;

        return Inertia::render('Dashboard', [
            'pageTitle' => 'Dashboard',
            'subtitle' => "Good morning, {$user} — here's what's happening",
            'readOnly' => true,
            'tasks' => $this->tasks->dashboardTasks($request),
            'filters' => $filters,
            ...$this->tasks->filterOptions(),
            ...$this->dashboard->dashboardData($this->tasks, $this->summary),
        ]);
    }
}
