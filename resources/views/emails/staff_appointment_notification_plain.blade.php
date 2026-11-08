Hello {{ optional($appointment->targetUser)->name ?? 'Staff' }},

You have received a new appointment request from the campus kiosk:

Requester: {{ trim(($appointment->requester_honorific ?? '') . ' ' . $appointment->requester_name) }}
Requester Type: {{ ucfirst($appointment->requester_type) }}
@if(!empty($appointment->grade_level))
Grade / Level: {{ $appointment->grade_level }}
@endif
Requester Email: {{ $appointment->requester_email }}
Purpose: {{ $appointment->purpose_code }}
@if(!empty($appointment->purpose_other_text))
Details: {{ $appointment->purpose_other_text }}
@endif

Please open the MIT Staff & Admin Portal to accept and schedule or decline this request.

Thank you,
MIT Campus Portal
