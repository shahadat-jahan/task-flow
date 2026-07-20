<?php

namespace App\Http\Resources;

use App\Models\Task;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Task
 */
class TaskResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'priority' => $this->priority,
            'due_date' => $this->due_date,
            'assignee_id' => $this->assignee_id,
            'created_by' => $this->created_by,
            'project_id' => $this->project_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'assignee' => UserResource::make($this->whenLoaded('assignee')),
            'creator' => UserResource::make($this->whenLoaded('creator')),
            'project' => ProjectResource::make($this->whenLoaded('project')),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'comments' => $this->whenLoaded('comments', fn () => $this->comments->map(
                fn ($comment) => [
                    'id' => $comment->id,
                    'body' => $comment->body,
                    'created_at' => $comment->created_at,
                    'user' => UserResource::make($comment->user),
                ]
            )),
            'attachments' => $this->whenLoaded('attachments', fn () => $this->attachments->map(
                fn ($attachment) => [
                    'id' => $attachment->id,
                    'original_filename' => $attachment->original_filename,
                    'stored_path' => $attachment->stored_path,
                    'mime_type' => $attachment->mime_type,
                    'size_bytes' => $attachment->size_bytes,
                    'created_at' => $attachment->created_at,
                    'uploader' => UserResource::make($attachment->uploader),
                ]
            )),
        ];
    }
}
