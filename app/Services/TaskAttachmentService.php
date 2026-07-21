<?php

namespace App\Services;

use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * File-attachment persistence for tasks.
 *
 * The service assumes the caller has already authorized the action and
 * validated the upload. Ownership of an attachment (only its uploader may
 * delete it, and its file on disk) is enforced in the controller, not here.
 */
class TaskAttachmentService
{
    /**
     * Store an uploaded file on the public disk and create its DB record.
     */
    public function upload(Task $task, User $uploader, UploadedFile $file): TaskAttachment
    {
        $path = $file->storeAs('attachments/'.$task->id, $file->hashName(), 'public');

        return TaskAttachment::create([
            'task_id' => $task->id,
            'uploaded_by' => $uploader->id,
            'original_filename' => $file->getClientOriginalName(),
            'stored_path' => $path,
            'mime_type' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
        ]);
    }

    /**
     * Delete an attachment's file from disk and its DB record.
     */
    public function delete(TaskAttachment $attachment): void
    {
        Storage::disk('public')->delete($attachment->stored_path);
        $attachment->delete();
    }
}
