Hello {{ $appointment->requester_honorific }} {{ $appointment->requester_name }},

Your appointment request for {{ optional($appointment->targetUser)->name ?? 'the selected staff' }} has been received and is currently pending review.

Requester Type: {{ ucfirst($appointment->requester_type) }}
@if(!empty($appointment->grade_level))
Grade / Level: {{ $appointment->grade_level }}
@endif
Purpose: {{ $appointment->purpose_code }}
@if(!empty($appointment->purpose_other_text))
Details: {{ $appointment->purpose_other_text }}
@endif
Status: Pending Review

You will receive an email update once your appointment request has been scheduled or processed by the staff.

Thank you,
MIT Campus Portal

