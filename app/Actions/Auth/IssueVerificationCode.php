<?php

namespace App\Actions\Auth;

use App\Mail\SendVerificationCode;
use App\Models\EmailVerificationCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class IssueVerificationCode
{
    /**
     * Issue a fresh 6-digit verification code for the given email and purpose,
     * persisting it and emailing it to the user.
     *
     * Uses a pessimistic lock (FOR UPDATE) within a transaction to prevent
     * race conditions when two requests issue a code for the same (email, type)
     * simultaneously. The unique (email, type) constraint on the table provides
     * a database-level safety net.
     */
    public function handle(string $email, string $type): void
    {
        $code = (string) random_int(100000, 999999);

        DB::transaction(function () use ($email, $type, $code) {
            // Lock the row for this (email, type) pair to prevent concurrent
            // requests from both deleting and both inserting.
            EmailVerificationCode::where('email', $email)
                ->where('type', $type)
                ->lockForUpdate()
                ->delete();

            EmailVerificationCode::create([
                'email' => $email,
                'code' => $code,
                'type' => $type,
                'expires_at' => now()->addMinutes(10),
            ]);
        });

        try {
            Mail::to($email)->queue(new SendVerificationCode($code, $type));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
