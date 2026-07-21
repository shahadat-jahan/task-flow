<?php

namespace App\Http\Controllers;

use App\Enums\TaskStatus;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use App\Models\User;
use App\Services\TaskService;
use App\Services\TaskSummaryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    /**
     * Shared summary queries (status counts for the summary cards).
     */
    public function __construct(
        private readonly TaskService $tasks,
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
            'pageTitle' => 'Tasks',
            'tasks' => $tasks,
            'filters' => $request->only(['status', 'priority', 'project_id', 'tag_id', 'search', 'sort', 'direction']),
            'projects' => Project::orderBy('name')->get(['id', 'name']),
            'tags' => Tag::orderBy('name')->get(['id', 'name']),
            'users' => User::orderBy('name')->get(['id', 'name']),
        'summary' => $this->summary->summarize(),
    ]);
    }

    /**
     * Store a newly created task, owned by the current user.
     */
    public function store(StoreTaskRequest $request): RedirectResponse
    {
        $this->authorize('create', Task::class);

        $this->tasks->create($request->validated(), $request->user());

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
            'pageTitle' => $task->title,
            'task' => $task,
        ]);
    }

    /**
     * Show the form for editing the specified task.
     */
    public function edit(Request $request, Task $task): Response
    {
        $this->authorize('update', $task);

        return Inertia::render('Tasks/Edit', [
            'pageTitle' => 'Edit Task',
            'task' => $task,
        ]);
    }

    /**
     * Update the specified task.
     */
    public function update(UpdateTaskRequest $request, Task $task): RedirectResponse
    {
        $this->authorize('update', $task);

        $this->tasks->update($task, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Task updated.')]);

        return back();
    }

    /**
     * Remove the specified task.
     */
    public function destroy(Task $task): RedirectResponse
    {
        $this->authorize('delete', $task);

        $this->tasks->delete($task);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Task deleted.')]);

        return back();
    }

    /**
     * Quick inline status change for a task.
     */
    public function updateStatus(Request $request, Task $task): RedirectResponse
{
    $this->authorize('update', $task);

    $validated = $request->validate([
        'status' => ['required', new Enum(TaskStatus::class)],
    ]);

    $this->tasks->updateStatus($task, $validated['status']);

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
