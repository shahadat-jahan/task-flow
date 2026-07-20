<?php

use App\Models\Tag;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('tags index can be rendered', function () {
    $user = User::factory()->create();
    Tag::factory()->create();

    $this->actingAs($user)
        ->get(route('tags.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Tags/Index')
            ->has('tags'));
});

test('authenticated user can create a tag', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('tags.store'), [
            'name' => 'Backend',
            'color' => '#059669',
        ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('tags.index'));

    expect(Tag::where('name', 'Backend')->exists())->toBeTrue();
});

test('tag name must be unique', function () {
    $user = User::factory()->create();
    Tag::factory()->create(['name' => 'Duplicate']);

    $response = $this->actingAs($user)
        ->post(route('tags.store'), [
            'name' => 'Duplicate',
            'color' => '#EF4444',
        ]);

    $response->assertSessionHasErrors('name');
    expect(Tag::where('name', 'Duplicate')->count())->toBe(1);
});

test('tag cannot be created with invalid color', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('tags.store'), [
            'name' => 'Valid',
            'color' => 'not-a-color',
        ]);

    $response->assertSessionHasErrors('color');
    expect(Tag::where('name', 'Valid')->exists())->toBeFalse();
});

test('guests cannot access tag routes', function () {
    $this->get(route('tags.index'))->assertRedirect(route('login'));
    $this->post(route('tags.store'))->assertRedirect(route('login'));
});
