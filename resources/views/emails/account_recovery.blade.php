<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>MIT Kiosk Account Recovery</title>
</head>
<body style="font-family: 'Segoe UI', Arial, sans-serif; background-color: #f8fafc; padding: 24px; color: #1e293b;">
    <div style="max-width: 560px; margin: 0 auto; background: #ffffff; border-radius: 16px; padding: 32px; border: 1px solid #e2e8f0; box-shadow: 0 4px 12px rgba(15, 23, 42, 0.05);">
        <h2 style="margin-top: 0; color: #0f172a; font-size: 22px; font-weight: 800;">MIT Kiosk — Account Recovery & Details</h2>
        <p style="font-size: 14px; color: #475569; line-height: 1.6;">
            Hello <strong>{{ $user->name }}</strong>,
        </p>
        <p style="font-size: 14px; color: #475569; line-height: 1.6;">
            We received a forgot password / account information request for your MIT Kiosk Staff &amp; Admin Portal account. Below is your complete account information along with your newly generated password:
        </p>

        <div style="background: #f1f5f9; border-radius: 12px; padding: 20px; margin: 24px 0;">
            <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                <tr>
                    <td style="padding: 8px 0; color: #64748b; font-weight: 600; width: 42%;">Full Name:</td>
                    <td style="padding: 8px 0; color: #0f172a; font-weight: 700;">{{ $user->name }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #64748b; font-weight: 600;">Email Address:</td>
                    <td style="padding: 8px 0; color: #0f172a; font-weight: 700;">{{ $user->email }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #64748b; font-weight: 600;">Account Role:</td>
                    <td style="padding: 8px 0; color: #0f172a; font-weight: 700;">{{ ucwords(str_replace('_', ' ', $user->role)) }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #64748b; font-weight: 600;">Current Status:</td>
                    <td style="padding: 8px 0; color: #0f172a; font-weight: 700;">{{ ucfirst($user->availability_status ?? 'available') }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #64748b; font-weight: 600;">Time Availability:</td>
                    <td style="padding: 8px 0; color: #0f172a; font-weight: 700;">{{ $user->time_availability ?: '8:00 AM - 5:00 PM' }}</td>
                </tr>
                <tr>
                    <td style="padding: 12px 0 4px 0; color: #2563eb; font-weight: 800; border-top: 1px solid #cbd5e1;">New Password:</td>
                    <td style="padding: 12px 0 4px 0; color: #2563eb; font-size: 18px; font-weight: 900; font-family: monospace; border-top: 1px solid #cbd5e1;">{{ $newPassword }}</td>
                </tr>
            </table>
        </div>

        <p style="font-size: 13px; color: #64748b; line-height: 1.6; margin-bottom: 0;">
            You can now sign in to the MIT Kiosk Mobile App using your email address and the new password above. Once signed in, you can change your password anytime from your <strong>Profile</strong> screen.
        </p>
    </div>
</body>
</html>

