<?php

use App\Mail\SendVerificationCode;
use App\Models\EmailVerificationCode;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

test('forgot password screen can be rendered', function () {
    $response = $this->get(route('auth.password.request'));

    $response->assertOk();
});

test('a reset code is emailed for an existing account', function () {
    Mail::fake();

    $user = User::factory()->create();

    $response = $this->post(route('auth.password.email'), [
        'email' => $user->email,
    ]);

    $response->assertRedirect(route('auth.password.reset', ['email' => $user->email]));

    $this->assertDatabaseHas('email_verification_codes', [
        'email' => $user->email,
        'type' => 'reset',
    ]);
    Mail::assertSent(SendVerificationCode::class, fn ($mail) => $mail->hasTo($user->email) && $mail->purpose === 'reset');
});

test('requesting a reset for an unknown email does not leak its existence', function () {
    Mail::fake();

    $response = $this->post(route('auth.password.email'), [
        'email' => 'nobody@example.com',
    ]);

    $response->assertRedirect(route('auth.password.reset', ['email' => 'nobody@example.com']));
    Mail::assertNothingSent();
});

test('reset password screen can be rendered', function () {
    $response = $this->get(route('auth.password.reset', ['email' => 'test@example.com']));

    $response->assertOk();
});

test('password can be reset with a valid code', function () {
    Mail::fake();

    $user = User::factory()->create(['password' => 'old-password']);

    EmailVerificationCode::create([
        'email' => $user->email,
        'code' => '123456',
        'type' => 'reset',
        'expires_at' => now()->addMinutes(10),
    ]);

    $response = $this->post(route('auth.password.update'), [
        'email' => $user->email,
        'code' => '123456',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertRedirect(route('login'));
    expect(Hash::check('old-password', $user->fresh()->password))->toBeFalse();
    expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
    expect($user->fresh()->email_verified_at)->not->toBeNull();
    $this->assertDatabaseMissing('email_verification_codes', [
        'email' => $user->email,
        'type' => 'reset',
    ]);
});

test('password cannot be reset with an invalid code', function () {
    $user = User::factory()->create(['password' => 'old-password']);

    EmailVerificationCode::create([
        'email' => $user->email,
        'code' => '123456',
        'type' => 'reset',
        'expires_at' => now()->addMinutes(10),
    ]);

    $response = $this->post(route('auth.password.update'), [
        'email' => $user->email,
        'code' => '000000',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors('code');
    expect(Hash::check('old-password', $user->fresh()->password))->toBeTrue();
    expect(Hash::check('password', $user->fresh()->password))->toBeFalse();
});

test('password reset rejects a password shorter than the minimum', function () {
    $user = User::factory()->create(['password' => 'old-password']);

    EmailVerificationCode::create([
        'email' => $user->email,
        'code' => '123456',
        'type' => 'reset',
        'expires_at' => now()->addMinutes(10),
    ]);

    $response = $this->post(route('auth.password.update'), [
        'email' => $user->email,
        'code' => '123456',
        'password' => 'short',
        'password_confirmation' => 'short',
    ]);

    $response->assertSessionHasErrors('password');
    expect(Hash::check('password', $user->fresh()->password))->toBeFalse();
});

test('password reset requires a code', function () {
    $user = User::factory()->create(['password' => 'old-password']);

    $response = $this->post(route('auth.password.update'), [
        'email' => $user->email,
        'code' => '',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors('code');
    expect(Hash::check('password', $user->fresh()->password))->toBeFalse();
});

test('a fresh reset code can be resent', function () {
    Mail::fake();

    $user = User::factory()->create();

    $response = $this->post(route('auth.password.resend'), [
        'email' => $user->email,
    ]);

    $response->assertSessionHas('status');
    $this->assertDatabaseHas('email_verification_codes', [
        'email' => $user->email,
        'type' => 'reset',
    ]);
    Mail::assertSent(SendVerificationCode::class);
});
