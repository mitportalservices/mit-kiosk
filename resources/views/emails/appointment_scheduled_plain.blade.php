Hello {{ $appointment->requester_honorific }} {{ $appointment->requester_name }},

Your appointment request for {{ optional($appointment->targetUser)->name ?? 'the selected staff' }} has been accepted and scheduled.

Scheduled Date/Time: {{ optional($appointment->scheduled_at)->format('Y-m-d h:i A') }}
Purpose: {{ $appointment->purpose_code }}
@if(!empty($appointment->purpose_other_text))
Details: {{ $appointment->purpose_other_text }}
@endif

Please arrive on time. Thank you.

MIT Campus Portal

