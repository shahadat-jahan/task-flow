<?php

use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;

test('guests cannot post comments', function () {
    $task = Task::factory()->create();

    $this->post(route('my-tasks.comments.store', $task))
        ->assertRedirect(route('login'));
});

test('authenticated user can comment on a task', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('my-tasks.comments.store', $task), [
            'body' => 'Looks good to me',
        ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('my-tasks.show', $task));

    expect(TaskComment::where('task_id', $task->id)->where('user_id', $user->id)->exists())
        ->toBeTrue();
});

test('comment cannot be created with an empty body', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('my-tasks.comments.store', $task), [
            'body' => '',
        ]);

    $response->assertSessionHasErrors('body');
    expect(TaskComment::where('task_id', $task->id)->count())->toBe(0);
});

test('author can delete their own comment', function () {
    $author = User::factory()->create();
    $comment = TaskComment::factory()->create(['user_id' => $author->id]);

    $response = $this->actingAs($author)
        ->delete(route('comments.destroy', $comment));

    $response->assertRedirect(route('my-tasks.show', $comment->task_id));
    expect(TaskComment::find($comment->id))->toBeNull();
});

test('non-author cannot delete a comment', function () {
    $author = User::factory()->create();
    $other = User::factory()->create();
    $comment = TaskComment::factory()->create(['user_id' => $author->id]);

    $this->actingAs($other)
        ->delete(route('comments.destroy', $comment))
        ->assertForbidden();

    expect(TaskComment::find($comment->id))->not->toBeNull();
});
