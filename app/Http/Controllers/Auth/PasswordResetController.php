<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\IssueVerificationCode;
use App\Http\Controllers\Controller;
use App\Models\EmailVerificationCode;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class PasswordResetController extends Controller
{
    /**
     * Show the "forgot password" email entry screen.
     */
    public function create()
    {
        return Inertia::render('auth/ForgotPassword');
    }

    /**
     * Issue a reset code for the given email (if it exists) and redirect to
     * the reset screen. Always redirects to avoid user enumeration.
     */
    public function store(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', $request->email)->first();

        if ($user) {
            app(IssueVerificationCode::class)->handle($user->email, 'reset');
        }

        return redirect()->route('auth.password.reset', ['email' => $request->email]);
    }

    /**
     * Show the reset-password screen (code + new password).
     */
    public function edit(Request $request)
    {
        return Inertia::render('auth/ResetPassword', [
            'email' => $request->query('email'),
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Verify the reset code and update the user's password.
     */
    public function update(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'digits:6'],
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
        ]);

        $code = EmailVerificationCode::where('email', $request->email)
            ->where('type', 'reset')
            ->valid()
            ->where('code', $request->code)
            ->first();

        if (! $code) {
            throw ValidationException::withMessages([
                'code' => 'That code is invalid or has expired.',
            ]);
        }

        $user = User::where('email', $request->email)->firstOrFail();
        $user->forceFill([
            'password' => $request->password,
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();
        $code->delete();

        return redirect()->route('login')
            ->with('status', 'Password reset — please sign in with your new password.');
    }

    /**
     * Resend a fresh reset code.
     */
    public function resend(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', $request->email)->first();

        if ($user) {
            app(IssueVerificationCode::class)->handle($user->email, 'reset');
        }

        return redirect()->back()
            ->with('status', 'A new code has been sent to your email.');
    }
}
