<?php

namespace App\Services;

use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;

/**
 * Comment persistence for tasks.
 *
 * The service assumes the caller has already authorized the action and
 * validated the input. Ownership of a comment (only its author may delete it)
 * is enforced in the controller, not here.
 */
class TaskCommentService
{
    /**
     * Create a comment authored by the given user on the given task.
     */
    public function create(Task $task, User $author, string $body): TaskComment
    {
        return TaskComment::create([
            'task_id' => $task->id,
            'user_id' => $author->id,
            'body' => $body,
        ]);
    }

    /**
     * Delete a comment.
     */
    public function delete(TaskComment $comment): void
    {
        $comment->delete();
    }
}
