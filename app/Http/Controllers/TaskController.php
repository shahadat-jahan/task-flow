<?php

namespace App\Http\Controllers;

use App\Enums\TaskStatus;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    public function __construct(
        private readonly TaskService $tasks,
    ) {}

    /**
     * Display a paginated, filterable, sortable list of tasks for the authenticated user.
     */
    public function myTasks(Request $request): Response
    {
        $filters = $request->only(['status', 'priority', 'project_id', 'tag_id', 'search', 'sort', 'direction']);
        $user =  $request->user()?->name;

        return Inertia::render('Tasks/Index', [
            'pageTitle' => 'My Tasks',
            'subtitle' => "Good morning, {$user} — here's what's happening",
            'tasks' => $this->tasks->myTasks($request),
            'filters' => $filters,
            ...$this->tasks->filterOptions(),
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

        return to_route('my-tasks.index');
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
            'pageTitle' => 'Task Details',
            'subtitle' => "TF-" . str_pad($task->id, 3, '0', STR_PAD_LEFT) . " - " . $task->project->name,
            'task' => $task,
            'canEdit' => $request->user()?->can('update', $task),
            ...$this->tasks->filterOptions(),
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
            'subtitle' => "Editing TF-" . str_pad($task->id, 3, '0', STR_PAD_LEFT),
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
}
