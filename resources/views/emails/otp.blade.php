<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>MIT Campus Portal Login Code</title>
</head>
<body style="font-family: 'Segoe UI', Arial, sans-serif; background-color: #f8fafc; padding: 24px; color: #1e293b;">
    <div style="max-width: 480px; margin: 0 auto; background: #ffffff; border-radius: 16px; padding: 32px; border: 1px solid #e2e8f0;">
        <h2 style="margin-top: 0; color: #0f172a; font-size: 20px; font-weight: 700;">MIT Campus Portal — Login Code</h2>
        <p style="font-size: 14px; color: #475569; line-height: 1.6;">
            Hello Admin, please use the 6-digit verification code below to complete your sign-in:
        </p>
        <div style="font-size: 32px; font-weight: 800; letter-spacing: 6px; color: #1e40af; background: #eff6ff; padding: 18px; text-align: center; border-radius: 12px; margin: 20px 0;">
            {{ $otp }}
        </div>
        <p style="font-size: 13px; color: #64748b; line-height: 1.6; margin-bottom: 0;">
            This code is valid for 10 minutes.
        </p>
    </div>
</body>
</html>
