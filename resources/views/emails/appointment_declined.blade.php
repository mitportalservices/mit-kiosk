<p>Hello {{ $appointment->requester_honorific }} {{ $appointment->requester_name }},</p>

<p>
    Your appointment request for
    <strong>{{ optional($appointment->targetUser)->name ?? 'the selected staff' }}</strong>
    was declined.
</p>

<ul>
    <li><strong>Purpose:</strong> {{ $appointment->purpose_code }}</li>
    @if(!empty($appointment->purpose_other_text))
        <li><strong>Details:</strong> {{ $appointment->purpose_other_text }}</li>
    @endif
</ul>

<p>You may try requesting a new appointment at a different time.</p>
