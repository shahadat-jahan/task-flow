<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAttachmentRequest;
use App\Models\Task;
use App\Models\TaskAttachment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class TaskAttachmentController extends Controller
{
    /**
     * Store an uploaded file. Any authenticated user may attach files.
     */
    public function store(StoreAttachmentRequest $request, Task $task): RedirectResponse
    {
        $file = $request->file('file');

        $path = $file->storeAs('attachments/'.$task->id, $file->hashName(), 'public');

        TaskAttachment::create([
            'task_id' => $task->id,
            'uploaded_by' => $request->user()->id,
            'original_filename' => $file->getClientOriginalName(),
            'stored_path' => $path,
            'mime_type' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Attachment added.')]);

        return to_route('tasks.show', $task);
    }

    /**
     * Remove the attachment. Only the uploader may delete it.
     */
    public function destroy(Request $request, TaskAttachment $attachment): RedirectResponse
    {
        abort_unless($attachment->uploaded_by === $request->user()->id, 403);

        Storage::disk('public')->delete($attachment->stored_path);
        $attachment->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Attachment deleted.')]);

        return to_route('tasks.show', $attachment->task_id);
    }
}
