<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>New Appointment Request</title>
</head>
<body style="font-family: 'Segoe UI', Arial, sans-serif; background-color: #f8fafc; padding: 24px; color: #1e293b;">
    <div style="max-width: 560px; margin: 0 auto; background: #ffffff; border-radius: 16px; padding: 32px; border: 1px solid #e2e8f0; box-shadow: 0 4px 12px rgba(15, 23, 42, 0.05);">
        <h2 style="margin-top: 0; color: #0f172a; font-size: 20px; font-weight: 800;">MIT Kiosk — New Appointment Request</h2>
        <p style="font-size: 14px; color: #475569; line-height: 1.6;">
            Hello <strong>{{ optional($appointment->targetUser)->name ?? 'Staff' }}</strong>,
        </p>
        <p style="font-size: 14px; color: #475569; line-height: 1.6;">
            You have received a new appointment request from the campus kiosk:
        </p>

        <div style="background: #f1f5f9; border-radius: 12px; padding: 20px; margin: 20px 0;">
            <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                <tr>
                    <td style="padding: 6px 0; color: #64748b; font-weight: 600; width: 40%;">Requester:</td>
                    <td style="padding: 6px 0; color: #0f172a; font-weight: 700;">{{ trim(($appointment->requester_honorific ?? '') . ' ' . $appointment->requester_name) }}</td>
                </tr>
                <tr>
                    <td style="padding: 6px 0; color: #64748b; font-weight: 600;">Requester Type:</td>
                    <td style="padding: 6px 0; color: #0f172a; font-weight: 700;">{{ ucfirst($appointment->requester_type) }}</td>
                </tr>
                @if(!empty($appointment->grade_level))
                <tr>
                    <td style="padding: 6px 0; color: #64748b; font-weight: 600;">Grade / Level:</td>
                    <td style="padding: 6px 0; color: #0f172a; font-weight: 700;">{{ $appointment->grade_level }}</td>
                </tr>
                @endif
                <tr>
                    <td style="padding: 6px 0; color: #64748b; font-weight: 600;">Requester Email:</td>
                    <td style="padding: 6px 0; color: #0f172a; font-weight: 700;">{{ $appointment->requester_email }}</td>
                </tr>
                <tr>
                    <td style="padding: 6px 0; color: #64748b; font-weight: 600;">Purpose:</td>
                    <td style="padding: 6px 0; color: #0f172a; font-weight: 700;">{{ $appointment->purpose_code }}</td>
                </tr>
                @if(!empty($appointment->purpose_other_text))
                <tr>
                    <td style="padding: 6px 0; color: #64748b; font-weight: 600;">Details:</td>
                    <td style="padding: 6px 0; color: #0f172a; font-weight: 700;">{{ $appointment->purpose_other_text }}</td>
                </tr>
                @endif
            </table>
        </div>

        <p style="font-size: 13px; color: #64748b; line-height: 1.6; margin-bottom: 0;">
            Please open the <strong>MIT Staff &amp; Admin Portal</strong> to accept and schedule or decline this request.
        </p>
    </div>
</body>
</html>

