<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your TaskFlow verification code</title>
</head>
<body style="margin: 0; padding: 24px; background-color: #f5f5f5; font-family: ui-sans-serif, system-ui, sans-serif; color: #1f2937;">
    <div style="max-width: 480px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; padding: 32px;">
        <h1 style="font-size: 20px; font-weight: 700; margin: 0 0 16px;">Your verification code</h1>

        <p style="font-size: 15px; line-height: 1.5; margin: 0 0 16px;">
            Use the following 6-digit code to
            @if ($purpose === 'registration')
                verify your email address
            @else
                reset your password
            @endif
            for your TaskFlow account.
        </p>

        <div style="font-size: 32px; font-weight: 700; letter-spacing: 8px; margin: 16px 0; color: #4f39f6;">
            {{ $code }}
        </div>

        <p style="font-size: 14px; line-height: 1.5; color: #6b7280; margin: 0 0 24px;">
            This code expires in 10 minutes. If you did not request this, you can safely ignore this email.
        </p>

        <p style="font-size: 14px; line-height: 1.5; color: #6b7280; margin: 0;">
            Thanks,<br>
            {{ config('app.name') }}
        </p>
    </div>
</body>
</html>
