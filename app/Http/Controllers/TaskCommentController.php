<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Models\Task;
use App\Models\TaskComment;
use App\Services\TaskCommentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TaskCommentController extends Controller
{
    public function __construct(
        private readonly TaskCommentService $comments,
    ) {}

    /**
     * Store a new comment. Any authenticated user may comment on any task.
     */
    public function store(StoreCommentRequest $request, Task $task): RedirectResponse
    {
        $this->comments->create($task, $request->user(), $request->validated()['body']);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Comment added.')]);

        return to_route('my-tasks.show', $task);
    }

    /**
     * Remove the comment. Only the comment's author may delete it.
     */
    public function destroy(Request $request, TaskComment $comment): RedirectResponse
    {
        abort_unless($comment->user_id === $request->user()->id, 403);

        $this->comments->delete($comment);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Comment deleted.')]);

        return to_route('my-tasks.show', $comment->task_id);
    }
}
