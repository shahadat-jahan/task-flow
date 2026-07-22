<?php

use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('guests cannot upload attachments', function () {
    $task = Task::factory()->create();

    $this->post(route('my-tasks.attachments.store', $task))
        ->assertRedirect(route('login'));
});

test('authenticated user can attach a file', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    $task = Task::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('my-tasks.attachments.store', $task), [
            'file' => UploadedFile::fake()->create('report.pdf', 100, 'application/pdf'),
        ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('my-tasks.show', $task));

    $attachment = TaskAttachment::where('task_id', $task->id)->where('uploaded_by', $user->id)->first();
    expect($attachment)->not->toBeNull();
    expect($attachment->original_filename)->toBe('report.pdf');
    Storage::disk('public')->assertExists($attachment->stored_path);
});

test('attachment cannot be created with a disallowed type', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    $task = Task::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('my-tasks.attachments.store', $task), [
            'file' => UploadedFile::fake()->create('evil.exe', 10),
        ]);

    $response->assertSessionHasErrors('file');
    expect(TaskAttachment::where('task_id', $task->id)->count())->toBe(0);
});

test('uploader can delete their own attachment and its file', function () {
    Storage::fake('public');
    $uploader = User::factory()->create();
    $attachment = TaskAttachment::factory()->create(['uploaded_by' => $uploader->id]);

    Storage::disk('public')->put($attachment->stored_path, 'contents');

    $response = $this->actingAs($uploader)
        ->delete(route('attachments.destroy', $attachment));

    $response->assertRedirect(route('my-tasks.show', $attachment->task_id));
    Storage::disk('public')->assertMissing($attachment->stored_path);
    expect(TaskAttachment::find($attachment->id))->toBeNull();
});

test('non-uploader cannot delete an attachment', function () {
    Storage::fake('public');
    $uploader = User::factory()->create();
    $other = User::factory()->create();
    $attachment = TaskAttachment::factory()->create(['uploaded_by' => $uploader->id]);

    Storage::disk('public')->put($attachment->stored_path, 'contents');

    $this->actingAs($other)
        ->delete(route('attachments.destroy', $attachment))
        ->assertForbidden();

    Storage::disk('public')->assertExists($attachment->stored_path);
    expect(TaskAttachment::find($attachment->id))->not->toBeNull();
});
