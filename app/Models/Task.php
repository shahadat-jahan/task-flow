<?php

namespace App\Models;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'description',
        'status',
        'priority',
        'due_date',
        'assignee_id',
        'created_by',
        'project_id',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'todo',
        'priority' => 'medium',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TaskStatus::class,
            'priority' => TaskPriority::class,
            'due_date' => 'date',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return BelongsToMany<Tag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    /**
     * @return HasMany<TaskComment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class)->latest();
    }

    /**
     * @return HasMany<TaskAttachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(TaskAttachment::class);
    }

    /**
     * Serialize for Inertia payloads.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
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
        ];

        if ($this->relationLoaded('assignee')) {
            $data['assignee'] = $this->assignee?->toArray();
        }

        if ($this->relationLoaded('creator')) {
            $data['creator'] = $this->creator?->toArray();
        }

        if ($this->relationLoaded('project')) {
            $data['project'] = $this->project?->toArray();
        }

        if ($this->relationLoaded('tags')) {
            $data['tags'] = $this->tags->toArray();
        }

        if ($this->relationLoaded('comments')) {
            $data['comments'] = $this->comments->map(fn ($comment) => [
                'id' => $comment->id,
                'body' => $comment->body,
                'created_at' => $comment->created_at,
                'user' => $comment->user->toArray(),
            ]);
        }

        if ($this->relationLoaded('attachments')) {
            $data['attachments'] = $this->attachments->map(fn ($attachment) => [
                'id' => $attachment->id,
                'original_filename' => $attachment->original_filename,
                'stored_path' => $attachment->stored_path,
                'mime_type' => $attachment->mime_type,
                'size_bytes' => $attachment->size_bytes,
                'created_at' => $attachment->created_at,
                'uploader' => $attachment->uploader->toArray(),
            ]);
        }

        return $data;
    }
}
