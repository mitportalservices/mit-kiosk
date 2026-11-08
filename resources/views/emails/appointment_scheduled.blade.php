<p>Hello {{ $appointment->requester_honorific }} {{ $appointment->requester_name }},</p>

<p>
    Your appointment request for
    <strong>{{ optional($appointment->targetUser)->name ?? 'the selected staff' }}</strong>
    has been accepted and scheduled.
</p>

<ul>
    <li><strong>Scheduled date/time:</strong> {{ optional($appointment->scheduled_at)->format('Y-m-d h:i A') }}</li>
    <li><strong>Purpose:</strong> {{ $appointment->purpose_code }}</li>
    @if(!empty($appointment->purpose_other_text))
        <li><strong>Details:</strong> {{ $appointment->purpose_other_text }}</li>
    @endif
</ul>

<p>Please arrive on time. Thank you.</p>
