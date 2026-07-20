<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('guests are redirected away from authenticated pages', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
    $this->get(route('tasks.index'))->assertRedirect(route('login'));
});

test('a user can log in with valid credentials', function () {
    $user = User::factory()->create([
        'email' => 'login@example.com',
        'password' => Hash::make('password'),
    ]);

    $this->post(route('login.store'), [
        'email' => 'login@example.com',
        'password' => 'password',
    ])->assertRedirect();
});

test('a user cannot log in with invalid credentials', function () {
    User::factory()->create([
        'email' => 'login@example.com',
        'password' => Hash::make('password'),
    ]);

    $this->post(route('login.store'), [
        'email' => 'login@example.com',
        'password' => 'wrong-password',
    ])->assertSessionHasErrors()->assertRedirect();
});

test('an authenticated user can log out', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('logout'))
        ->assertRedirect();
});

test('a user can register with valid data', function () {
    $this->post(route('register.store'), [
        'name' => 'New User',
        'email' => 'new@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect();

    expect(User::where('email', 'new@example.com')->exists())->toBeTrue();
});

test('registration requires valid data', function () {
    $this->post(route('register.store'), [
        'name' => 'New User',
        'email' => 'new@example.com',
        'password' => 'short',
        'password_confirmation' => 'short',
    ])->assertSessionHasErrors();

    expect(User::where('email', 'new@example.com')->exists())->toBeFalse();
});
