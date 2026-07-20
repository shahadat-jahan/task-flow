<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Models\Task;
use App\Models\TaskComment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TaskCommentController extends Controller
{
    /**
     * Store a new comment. Any authenticated user may comment on any task.
     */
    public function store(StoreCommentRequest $request, Task $task): RedirectResponse
    {
        TaskComment::create([
            'task_id' => $task->id,
            'user_id' => $request->user()->id,
            ...$request->validated(),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Comment added.')]);

        return to_route('tasks.show', $task);
    }

    /**
     * Remove the comment. Only the comment's author may delete it.
     */
    public function destroy(Request $request, TaskComment $comment): RedirectResponse
    {
        abort_unless($comment->user_id === $request->user()->id, 403);

        $comment->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Comment deleted.')]);

        return to_route('tasks.show', $comment->task_id);
    }
}
