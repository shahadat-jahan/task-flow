<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TaskPolicy
{
    use HandlesAuthorization;

    /**
     * Any authenticated user may list tasks.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Any authenticated user may view a task.
     */
    public function view(User $user, Task $task): bool
    {
        return true;
    }

    /**
     * Any authenticated user may create tasks.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Only the task's creator may update it.
     */
    public function update(User $user, Task $task): bool
    {
        return $user->id === $task->created_by;
    }

    /**
     * Only the task's creator may delete it.
     */
    public function delete(User $user, Task $task): bool
    {
        return $user->id === $task->created_by;
    }
}
