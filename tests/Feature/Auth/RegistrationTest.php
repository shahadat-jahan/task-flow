<?php

use App\Mail\SendVerificationCode;
use App\Models\EmailVerificationCode;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users register unverified, are not logged in, and receive a code', function () {
    Mail::fake();

    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertGuest();
    $response->assertRedirect(route('verification.notice', ['email' => 'test@example.com']));

    $user = User::where('email', 'test@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->email_verified_at)->toBeNull();

    $this->assertDatabaseHas('email_verification_codes', [
        'email' => 'test@example.com',
        'type' => 'registration',
    ]);

    Mail::assertSent(SendVerificationCode::class, fn ($mail) => $mail->hasTo('test@example.com') && $mail->purpose === 'registration');
});

test('email is verified with the correct code', function () {
    $user = User::factory()->unverified()->create();

    EmailVerificationCode::create([
        'email' => $user->email,
        'code' => '123456',
        'type' => 'registration',
        'expires_at' => now()->addMinutes(10),
    ]);

    $response = $this->post(route('verification.verify'), [
        'email' => $user->email,
        'code' => '123456',
    ]);

    $response->assertRedirect(route('login'));
    expect($user->fresh()->email_verified_at)->not->toBeNull();
    $this->assertDatabaseMissing('email_verification_codes', [
        'email' => $user->email,
        'type' => 'registration',
    ]);
});

test('email is not verified with an incorrect code', function () {
    $user = User::factory()->unverified()->create();

    EmailVerificationCode::create([
        'email' => $user->email,
        'code' => '123456',
        'type' => 'registration',
        'expires_at' => now()->addMinutes(10),
    ]);

    $response = $this->post(route('verification.verify'), [
        'email' => $user->email,
        'code' => '000000',
    ]);

    $response->assertSessionHasErrors('code');
    expect($user->fresh()->email_verified_at)->toBeNull();
});

test('email is not verified with an expired code', function () {
    $user = User::factory()->unverified()->create();

    EmailVerificationCode::create([
        'email' => $user->email,
        'code' => '123456',
        'type' => 'registration',
        'expires_at' => now()->subMinute(),
    ]);

    $response = $this->post(route('verification.verify'), [
        'email' => $user->email,
        'code' => '123456',
    ]);

    $response->assertSessionHasErrors('code');
    expect($user->fresh()->email_verified_at)->toBeNull();
});

test('a fresh verification code can be resent', function () {
    Mail::fake();

    $user = User::factory()->unverified()->create();

    $response = $this->post(route('verification.resend'), [
        'email' => $user->email,
    ]);

    $response->assertSessionHas('status');
    $this->assertDatabaseHas('email_verification_codes', [
        'email' => $user->email,
        'type' => 'registration',
    ]);
    Mail::assertSent(SendVerificationCode::class);
});
