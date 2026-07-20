<?php

namespace App\Http\Controllers;

use App\Enums\TaskStatus;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\TagResource;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use App\Services\TaskSummaryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    /**
     * Shared summary queries (status counts for the summary cards).
     */
    public function __construct(
        private readonly TaskSummaryService $summary,
    ) {}

    /**
     * Display a paginated, filterable, sortable list of tasks.
     */
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
            'summary' => $this->summary->statusCounts(),
        ]);
    }

    /**
     * Store a newly created task, owned by the current user.
     */
    public function store(StoreTaskRequest $request): RedirectResponse
    {
        $this->authorize('create', Task::class);

        $data = $request->validated();
        $tags = $data['tags'] ?? [];
        unset($data['tags']);

        $task = Task::create([
            ...$data,
            'created_by' => $request->user()->id,
        ]);

        $task->tags()->sync($tags);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Task created.')]);

        return to_route('tasks.index');
    }

    /**
     * Display the specified task (Task Details page).
     */
    public function show(Request $request, Task $task): Response
    {
        $this->authorize('view', $task);

        $task->load([
            'assignee',
            'creator',
            'project',
            'tags',
            'comments.user',
            'attachments.uploader',
        ]);

        return Inertia::render('Tasks/Show', [
            'task' => (new TaskResource($task))->resolve($request),
        ]);
    }

    /**
     * Show the form for editing the specified task.
     */
    public function edit(Request $request, Task $task): Response
    {
        $this->authorize('update', $task);

        return Inertia::render('Tasks/Edit', [
            'task' => (new TaskResource($task))->resolve($request),
        ]);
    }

    /**
     * Update the specified task.
     */
    public function update(UpdateTaskRequest $request, Task $task): RedirectResponse
    {
        $this->authorize('update', $task);

        $data = $request->validated();
        $tags = $data['tags'] ?? [];
        unset($data['tags']);

        $task->update($data);
        $task->tags()->sync($tags);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Task updated.')]);

        return back();
    }

    /**
     * Remove the specified task.
     */
    public function destroy(Task $task): RedirectResponse
    {
        $this->authorize('delete', $task);

        $task->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Task deleted.')]);

        return back();
    }

    /**
     * Quick inline status change for a task.
     */
    public function updateStatus(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('update', $task);

        $request->validate([
            'status' => ['required', Rule::in(array_map(fn (TaskStatus $s) => $s->value, TaskStatus::cases()))],
        ]);

        $task->update(['status' => $request->status]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Status updated.')]);

        return back();
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
