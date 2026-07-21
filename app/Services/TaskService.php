<?php

namespace App\Services;

use App\Models\Task;
use App\Models\User;

/**
 * Task CRUD operations, shared by the HTTP controller.
 *
 * The service assumes the caller has already authorized the action (via the
 * TaskPolicy) and validated the input (via the Form Request); it only performs
 * the persistence work. Tag syncing lives here so create()/update() own the
 * full task-write including its tag relationship.
 */
class TaskService
{
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
}
