<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAttachmentRequest;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Services\TaskAttachmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TaskAttachmentController extends Controller
{
    public function __construct(
        private readonly TaskAttachmentService $attachments,
    ) {}

    /**
     * Store an uploaded file. Any authenticated user may attach files.
     */
    public function store(StoreAttachmentRequest $request, Task $task): RedirectResponse
    {
        $file = $request->file('file');

        $this->attachments->upload($task, $request->user(), $file);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Attachment added.')]);

        return to_route('my-tasks.show', $task);
    }

    /**
     * Show an attachment inline in the browser. Any authenticated user may view.
     */
    public function show(Request $request, TaskAttachment $attachment): BinaryFileResponse
    {
        $filePath = storage_path('app/public/'.$attachment->stored_path);

        abort_if(!file_exists($filePath), 404);

        return response()->file($filePath, [
            'Content-Disposition' => 'inline; filename="'.$attachment->original_filename.'"',
        ]);
    }

    /**
     * Remove the attachment. Only the uploader may delete it.
     */
    public function destroy(Request $request, TaskAttachment $attachment): RedirectResponse
    {
        abort_unless($attachment->uploaded_by === $request->user()->id, 403);

        $this->attachments->delete($attachment);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Attachment deleted.')]);

        return to_route('my-tasks.show', $attachment->task_id);
    }
}
