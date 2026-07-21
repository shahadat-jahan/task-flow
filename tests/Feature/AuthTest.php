<?php

use App\Models\EmailVerificationCode;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('guests are redirected away from authenticated pages', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
    $this->get(route('tasks.index'))->assertRedirect(route('login'));
});

test('a verified user can log in with valid credentials', function () {
    $user = User::factory()->create([
        'email' => 'login@example.com',
        'password' => Hash::make('password'),
        'email_verified_at' => now(),
    ]);

    $this->post(route('login.store'), [
        'email' => 'login@example.com',
        'password' => 'password',
    ])->assertRedirect();
});

test('an unverified user is redirected to verify-email and given a login-type OTP on login attempt', function () {
    $user = User::factory()->create([
        'email' => 'unverified@example.com',
        'password' => Hash::make('password'),
        'email_verified_at' => null,
    ]);

    $this->post(route('login.store'), [
        'email' => 'unverified@example.com',
        'password' => 'password',
    ])
        ->assertRedirect(route('verification.notice', ['email' => 'unverified@example.com']))
        ->assertSessionHas('status', trans('Please verify your email before signing in. A new code has been sent to your email.'));

    // Assert a login-type code was created
    expect(EmailVerificationCode::where('email', 'unverified@example.com')
        ->where('type', 'login')
        ->exists()
    )->toBeTrue();

    // Assert the user is not logged in
    $this->assertGuest();
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
