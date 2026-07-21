<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\IssueVerificationCode;
use App\Http\Controllers\Controller;
use App\Models\EmailVerificationCode;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class EmailVerificationController extends Controller
{
    /**
     * Show the email verification code entry screen.
     */
    public function notice(Request $request)
    {
        return Inertia::render('auth/VerifyEmail', [
            'email' => $request->query('email'),
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Verify the emailed code and mark the user's email as verified.
     */
    public function verify(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'digits:6'],
        ]);

        $code = EmailVerificationCode::where('email', $request->email)
            ->whereIn('type', ['registration', 'login'])
            ->valid()
            ->where('code', $request->code)
            ->first();

        if (! $code) {
            throw ValidationException::withMessages([
                'code' => 'That code is invalid or has expired.',
            ]);
        }

        $user = User::where('email', $request->email)->firstOrFail();
        $user->forceFill(['email_verified_at' => now()])->save();
        $code->delete();

        return redirect()->route('login')
            ->with('status', 'Email verified — you can now sign in.');
    }

    /**
     * Resend a fresh verification code.
     */
    public function resend(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', $request->email)->first();

        if ($user) {
            $latest = EmailVerificationCode::where('email', $request->email)
                ->whereIn('type', ['registration', 'login'])
                ->latest()
                ->first();

            $type = $latest?->type ?? 'registration';

            app(IssueVerificationCode::class)->handle($user->email, $type);
        }

        return redirect()->back()
            ->with('status', 'A new code has been sent to your email.');
    }
}
