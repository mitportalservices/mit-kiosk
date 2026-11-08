Hello {{ $appointment->requester_honorific }} {{ $appointment->requester_name }},

Your appointment request for {{ optional($appointment->targetUser)->name ?? 'the selected staff' }} could not be accommodated at this time.

Purpose: {{ $appointment->purpose_code }}
@if(!empty($appointment->purpose_other_text))
Details: {{ $appointment->purpose_other_text }}
@endif

You may submit a new appointment request for a different time.

Thank you,
MIT Campus Portal
