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
    /**
     * The Dashboard is the same task table with summary cards above it as
     * Tasks/Index, so it renders the shared Tasks/Index view (see DECISIONS.md)
     * and reuses the same summary service.
     */
    public function __construct(
        private readonly TaskSummaryService $summary,
    ) {}

    public function index(Request $request): Response
    {
        $filters = $request->only(['status', 'priority', 'project_id', 'tag_id', 'search', 'sort', 'direction']);

        $tasks = Task::query()
            ->with(['assignee:id,name', 'creator:id,name', 'project:id,name', 'tags:id,name,color'])
            ->filter($filters)
            ->orderBy($this->sortColumn($request->input('sort')), $this->direction($request->input('direction')))
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Dashboard', [
            'pageTitle' => 'Dashboard',
            'tasks' => $tasks,
            'filters' => $filters,
            'projects' => fn () => Project::select(['id', 'name'])->orderBy('name')->get(),
            'tags' => fn () => Tag::select(['id', 'name', 'color'])->orderBy('name')->get(),
            'users' => fn () => User::select(['id', 'name'])->orderBy('name')->get(),
            'summary' => fn () => $this->summary->summarize(),
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
        return in_array(strtolower((string) $direction), ['asc', 'desc'], true)
            ? strtolower($direction)
            : 'asc';
    }
}
