<?php

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Tasks/Index')
            ->has('tasks')
            ->has('summary'));
});

test('dashboard summary is zero-filled and includes overdue', function () {
    $user = User::factory()->create();

    Task::factory()->create(['status' => TaskStatus::Todo, 'priority' => TaskPriority::High]);
    Task::factory()->create([
        'status' => TaskStatus::Done,
        'priority' => TaskPriority::Low,
        'due_date' => now()->subDays(3),
    ]);
    Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'priority' => TaskPriority::Medium,
        'due_date' => now()->subDay(),
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.total_tasks', 3)
            ->where('summary.overdue_count', 1)
            ->where('summary.by_status.'.TaskStatus::Todo->value, 1)
            ->where('summary.by_status.'.TaskStatus::Done->value, 1)
            ->where('summary.by_status.'.TaskStatus::InProgress->value, 1)
            ->where('summary.by_status.'.TaskStatus::InReview->value, 0)
            ->where('summary.by_status.'.TaskStatus::Cancelled->value, 0)
            ->where('summary.by_priority.'.TaskPriority::High->value, 1)
            ->where('summary.by_priority.'.TaskPriority::Medium->value, 1)
            ->where('summary.by_priority.'.TaskPriority::Low->value, 1));
});

test('shared projects prop includes task counts', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['name' => 'Alpha']);
    Task::factory()->count(3)->create([
        'project_id' => $project->id,
        'status' => TaskStatus::Todo,
        'priority' => TaskPriority::High,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('sidebarProjects')
            ->where('sidebarProjects.0.id', $project->id)
            ->where('sidebarProjects.0.name', 'Alpha')
            ->where('sidebarProjects.0.color', $project->color)
            ->where('sidebarProjects.0.tasks_count', 3));
});
