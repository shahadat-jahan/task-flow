<?php

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\TaskComment;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests cannot access tasks', function () {
    $this->get(route('tasks.index'))->assertRedirect(route('login'));
    $this->post(route('tasks.store'))->assertRedirect(route('login'));
    $task = Task::factory()->create();
    $this->get(route('tasks.show', $task))->assertRedirect(route('login'));
});

test('authenticated user can list tasks with filters, projects, tags, and summary', function () {
    $user = User::factory()->create();
    Task::factory()->count(3)->create();

    $this->actingAs($user)
        ->get(route('tasks.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Tasks/Index')
            ->has('tasks.data')
            ->has('filters')
            ->has('projects')
            ->has('tags')
            ->has('summary')
            ->has('summary.trends.total_tasks')
            ->has('summary.trends.completed')
            ->has('summary.trends.in_progress')
            ->has('summary.trends.overdue_count'));
});

test('index filters by status, project, and search', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();

    Task::factory()->create([
        'title' => 'Write the report',
        'status' => TaskStatus::Done,
        'project_id' => $project->id,
    ]);
    Task::factory()->create([
        'title' => 'Buy groceries',
        'status' => TaskStatus::Todo,
        'project_id' => null,
    ]);

    $this->actingAs($user)
        ->get(route('tasks.index', ['status' => TaskStatus::Done->value]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('tasks.data', 1));

    $this->actingAs($user)
        ->get(route('tasks.index', ['project_id' => $project->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('tasks.data', 1));

    $this->actingAs($user)
        ->get(route('tasks.index', ['search' => 'report']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('tasks.data', 1));

    $this->actingAs($user)
        ->get(route('tasks.index', ['search' => 'nonexistent-term']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('tasks.data', 0));
});

test('authenticated user can create a task with tags', function () {
    $user = User::factory()->create();
    $assignee = User::factory()->create();
    $project = Project::factory()->create();
    $tags = Tag::factory()->count(2)->create();

    $response = $this->actingAs($user)
        ->post(route('tasks.store'), [
            'title' => 'Ship the feature',
            'description' => 'Details here',
            'status' => TaskStatus::InProgress->value,
            'priority' => TaskPriority::High->value,
            'due_date' => '2026-08-01',
            'assignee_id' => $assignee->id,
            'project_id' => $project->id,
            'tags' => $tags->pluck('id')->all(),
        ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('tasks.index'));

    $task = Task::where('title', 'Ship the feature')->first();
    expect($task)->not->toBeNull();
    expect($task->created_by)->toBe($user->id);
    expect($task->tags()->count())->toBe(2);
});

test('task cannot be created with invalid data', function () {
    $user = User::factory()->create();
    Tag::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('tasks.store'), [
            'title' => '',
            'status' => 'not-a-status',
            'priority' => 'urgent',
            'assignee_id' => 999999,
            'tags' => [999999],
        ]);

    $response->assertSessionHasErrors(['title', 'status', 'priority', 'assignee_id', 'tags.0']);
    expect(Task::count())->toBe(0);
});

test('authenticated user can view a task with comments and attachments', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create();
    TaskComment::factory()->create(['task_id' => $task->id, 'user_id' => $user->id]);
    TaskAttachment::factory()->create(['task_id' => $task->id, 'uploaded_by' => $user->id]);

    $this->actingAs($user)
        ->get(route('tasks.show', $task))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Tasks/Show')
            ->has('task')
            ->has('task.comments.0.user')
            ->has('task.attachments.0.uploader'));
});

test('creator can update a task and its tags', function () {
    $creator = User::factory()->create();
    $task = Task::factory()->create(['created_by' => $creator->id]);
    $tag = Tag::factory()->create();

    $response = $this->actingAs($creator)
        ->from(route('tasks.index'))
        ->put(route('tasks.update', $task), [
            'title' => 'Renamed task',
            'status' => TaskStatus::InReview->value,
            'priority' => TaskPriority::Low->value,
            'tags' => [$tag->id],
        ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('tasks.index'));

    $task->refresh();
    expect($task->title)->toBe('Renamed task');
    expect($task->status)->toBe(TaskStatus::InReview);
    expect($task->tags()->count())->toBe(1);
});

test('non-creator cannot update a task', function () {
    $creator = User::factory()->create();
    $other = User::factory()->create();
    $task = Task::factory()->create(['created_by' => $creator->id]);

    $this->actingAs($other)
        ->put(route('tasks.update', $task), [
            'title' => 'Hijacked',
            'status' => TaskStatus::Done->value,
            'priority' => TaskPriority::Low->value,
        ])
        ->assertForbidden();

    expect($task->refresh()->title)->not->toBe('Hijacked');
});

test('creator can delete a task; non-creator cannot', function () {
    $creator = User::factory()->create();
    $other = User::factory()->create();
    $task = Task::factory()->create(['created_by' => $creator->id]);

    $this->actingAs($other)
        ->delete(route('tasks.destroy', $task))
        ->assertForbidden();

    expect(Task::find($task->id))->not->toBeNull();

    $this->actingAs($creator)
        ->from(route('tasks.index'))
        ->delete(route('tasks.destroy', $task))
        ->assertRedirect(route('tasks.index'));

    expect(Task::find($task->id))->toBeNull();
});

test('creator can change a task status inline; non-creator cannot', function () {
    $creator = User::factory()->create();
    $other = User::factory()->create();
    $task = Task::factory()->create([
        'created_by' => $creator->id,
        'status' => TaskStatus::Todo,
    ]);

    $this->actingAs($other)
        ->patch(route('tasks.status.update', $task), ['status' => TaskStatus::Done->value])
        ->assertForbidden();

    $this->actingAs($creator)
        ->patch(route('tasks.status.update', $task), ['status' => TaskStatus::Done->value])
        ->assertRedirect();

    expect($task->refresh()->status)->toBe(TaskStatus::Done);
});
