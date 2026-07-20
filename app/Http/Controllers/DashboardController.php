<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProjectResource;
use App\Http\Resources\TagResource;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
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
        $tasks = Task::query()
            ->with(['assignee', 'creator', 'project', 'tags'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('priority'), fn ($q) => $q->where('priority', $request->priority))
            ->when($request->filled('project_id'), fn ($q) => $q->where('project_id', $request->project_id))
            ->when($request->filled('tag_id'), fn ($q) => $q->whereHas('tags', fn ($q) => $q->where('id', $request->tag_id)))
            ->when($request->filled('search'), fn ($q) => $q->where('title', 'like', '%'.$request->search.'%'))
            ->orderBy($this->sortColumn($request), $this->direction($request))
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Tasks/Index', [
            'tasks' => TaskResource::collection($tasks),
            'filters' => [
                'status' => $request->status,
                'priority' => $request->priority,
                'project_id' => $request->project_id,
                'tag_id' => $request->tag_id,
                'search' => $request->search,
                'sort' => $request->sort,
                'direction' => $request->direction,
            ],
            'projects' => ProjectResource::collection(Project::orderBy('name')->get()),
            'tags' => TagResource::collection(Tag::orderBy('name')->get()),
            'summary' => $this->summary->summarize(),
        ]);
    }

    /**
     * Resolve the sort column, restricting to an allowlist.
     */
    protected function sortColumn(Request $request): string
    {
        return in_array($request->sort, ['due_date', 'priority', 'created_at', 'title'], true)
            ? $request->sort
            : 'created_at';
    }

    /**
     * Resolve the sort direction, defaulting to ascending.
     */
    protected function direction(Request $request): string
    {
        return in_array($request->direction, ['asc', 'desc'], true)
            ? $request->direction
            : 'asc';
    }
}
