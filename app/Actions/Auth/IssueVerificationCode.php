<?php

namespace App\Actions\Auth;

use App\Mail\SendVerificationCode;
use App\Models\EmailVerificationCode;
use Illuminate\Support\Facades\Mail;

class IssueVerificationCode
{
    /**
     * Issue a fresh 6-digit verification code for the given email and purpose,
     * persisting it and emailing it to the user.
     */
    public function handle(string $email, string $type): void
    {
        $code = (string) random_int(100000, 999999);

        EmailVerificationCode::where('email', $email)
            ->where('type', $type)
            ->delete();

        EmailVerificationCode::create([
            'email' => $email,
            'code' => $code,
            'type' => $type,
            'expires_at' => now()->addMinutes(10),
        ]);

        Mail::to($email)->send(new SendVerificationCode($code, $type));
    }
}
