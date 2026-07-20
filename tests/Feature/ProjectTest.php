<?php

use App\Models\Project;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('projects index can be rendered', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();

    $this->actingAs($user)
        ->get(route('projects.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Projects/Index')
            ->has('projects'));
});

test('authenticated user can create a project', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('projects.store'), [
            'name' => 'New Initiative',
            'color' => '#7C3AED',
        ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('projects.index'));

    $project = Project::where('name', 'New Initiative')->first();
    expect($project)->not->toBeNull();
    expect($project->owner_id)->toBe($user->id);
    expect($project->color)->toBe('#7C3AED');
});

test('project cannot be created with invalid data', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('projects.store'), [
            'name' => '',
            'color' => '#zzz',
        ]);

    $response->assertSessionHasErrors(['name', 'color']);
    expect(Project::count())->toBe(0);
});

test('authenticated user can update a project', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();

    $response = $this->actingAs($user)
        ->put(route('projects.update', $project), [
            'name' => 'Renamed',
            'color' => '#2563EB',
        ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('projects.index'));

    $project->refresh();
    expect($project->name)->toBe('Renamed');
    expect($project->color)->toBe('#2563EB');
});

test('authenticated user can delete a project', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();

    $response = $this->actingAs($user)
        ->delete(route('projects.destroy', $project));

    $response->assertRedirect(route('projects.index'));
    expect(Project::find($project->id))->toBeNull();
});

test('guests cannot access project routes', function () {
    $this->get(route('projects.index'))->assertRedirect(route('login'));
    $this->post(route('projects.store'))->assertRedirect(route('login'));
});
