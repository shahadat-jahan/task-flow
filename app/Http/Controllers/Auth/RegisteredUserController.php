<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\IssueVerificationCode;
use App\Actions\Fortify\CreateNewUser;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class RegisteredUserController extends Controller
{
    /**
     * Show the registration form.
     */
    public function create()
    {
        return Inertia::render('auth/Register', [
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ]);
    }

    /**
     * Handle an incoming registration request, issue a verification code,
     * and redirect to the verify screen without logging the user in.
     */
    public function store(Request $request)
    {
        $user = (new CreateNewUser)->create($request->all());

        app(IssueVerificationCode::class)->handle($user->email, 'registration');

        return redirect()->route('verification.notice', ['email' => $user->email]);
    }
}
