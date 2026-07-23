<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Task CRUD and query-building operations, shared by controllers.
 *
 * This service handles both "write" operations (create, update, delete, status change)
 * and "read" operations (paginated listing, filtering, sorting) so that controllers
 * stay focused on HTTP concerns without duplicating query logic.
 *
 * Write methods assume the caller has already authorized the action (via the
 * TaskPolicy) and validated the input (via the Form Request); they only perform
 * the persistence work. Tag syncing lives here so create()/update() own the
 * full task-write including its tag relationship.
 */
class TaskService
{
    /**
     * Allowed sort columns – everything else falls back to 'created_at'.
     *
     * @var list<string>
     */
    private const SORTABLE = ['due_date', 'priority', 'created_at', 'title'];

    // ─── Write operations ───────────────────────────────────────────────

    /**
     * Create a task owned by the given creator, syncing its tags.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $creator): Task
    {
        $tags = $data['tags'] ?? [];
        unset($data['tags']);

        $task = Task::create([
            ...$data,
            'created_by' => $creator->id,
        ]);

        $task->tags()->sync($tags);

        return $task;
    }

    /**
     * Update a task and re-sync its tags.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Task $task, array $data): Task
    {
        $tags = $data['tags'] ?? [];
        unset($data['tags']);

        $task->update($data);
        $task->tags()->sync($tags);

        return $task;
    }

    /**
     * Delete a task.
     *
     * @return void
     */
    public function delete(Task $task): void
    {
        $task->delete();
    }

    /**
     * Quick inline status change for a task.
     */
    public function updateStatus(Task $task, string $status): Task
    {
        $task->update(['status' => $status]);

        return $task;
    }

    // ─── Read / query operations ────────────────────────────────────────

    /**
     * Paginate tasks for the authenticated user, scoped to their ownership.
     */
    public function myTasks(Request $request): LengthAwarePaginator
    {
        return Task::query()
            ->with(['assignee', 'creator', 'project', 'tags'])
            ->withCount(['comments', 'attachments'])
            ->where('created_by', $request->user()->id)
            ->tap(fn (Builder $q) => $this->applyFilters($q, $request))
            ->orderBy($this->sortColumn($request), $this->direction($request))
            ->paginate($this->perPage($request) ?? 10)
            ->withQueryString();
    }

    /**
     * Paginate all tasks (visible on the dashboard), without owner scoping.
     */
    public function dashboardTasks(Request $request): LengthAwarePaginator
    {
        return Task::query()
            ->with(['assignee:id,name', 'creator:id,name', 'project:id,name,color', 'tags:id,name,color'])
            ->withCount(['comments', 'attachments'])
            ->tap(fn (Builder $q) => $this->applyFilters($q, $request))
            ->orderBy($this->sortColumn($request), $this->direction($request))
            ->paginate($this->perPage($request) ?? 10)
            ->withQueryString();
    }

    /**
     * Select options for filter dropdowns (projects, tags, users).
     *
     * @return array{projects: Collection, tags: Collection, users: Collection}
     */
    public function filterOptions(): array
    {
        return [
            'projects' => Project::orderBy('name', 'asc')->get(['id', 'name']),
            'tags' => Tag::orderBy('name', 'asc')->get(['id', 'name']),
            'users' => User::orderBy('name', 'asc')->get(['id', 'name']),
        ];
    }

    /**
     * Apply reusable filter scopes from the request.
     */
    private function applyFilters(Builder $query, Request $request): void
    {
        $query
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('priority'), fn ($q) => $q->where('priority', $request->priority))
            ->when($request->filled('project_id'), fn ($q) => $q->where('project_id', $request->project_id))
            ->when($request->filled('tag_id'), fn ($q) => $q->whereHas('tags', fn ($q) => $q->where('id', $request->tag_id)))
            ->when($request->filled('search'), fn ($q) => $q->where('title', 'like', '%'.$request->search.'%'));
    }

    /**
     * Resolve the sort column, restricting to an allowlist.
     */
    public function sortColumn(Request $request): string
    {
        return in_array($request->sort, self::SORTABLE, true)
            ? $request->sort
            : 'created_at';
    }

    /**
     * Resolve the sort direction, defaulting to ascending.
     */
    public function direction(Request $request): string
    {
        return in_array($request->direction, ['asc', 'desc'], true)
            ? $request->direction
            : 'asc';
    }

    /**
     * Resolve the per-page value, defaulting to 15.
     *
     * @return int<5, 100>
     */
    private function perPage(Request $request): int
    {
        $value = (int) $request->query('per_page', 10);

        return in_array($value, [5, 10, 15, 25, 50, 100], true) ? $value : 10;
    }
}
