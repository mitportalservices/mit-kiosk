<p>Hello {{ $appointment->requester_honorific }} {{ $appointment->requester_name }},</p>

<p>
    Your appointment request for
    <strong>{{ optional($appointment->targetUser)->name ?? 'the selected staff' }}</strong>
    has been received and is currently pending review.
</p>

<ul>
    <li><strong>Requester Type:</strong> {{ ucfirst($appointment->requester_type) }}</li>
    @if(!empty($appointment->grade_level))
        <li><strong>Grade / Level:</strong> {{ $appointment->grade_level }}</li>
    @endif
    <li><strong>Purpose:</strong> {{ $appointment->purpose_code }}</li>
    @if(!empty($appointment->purpose_other_text))
        <li><strong>Details:</strong> {{ $appointment->purpose_other_text }}</li>
    @endif
    <li><strong>Status:</strong> Pending Review</li>
</ul>

<p>You will receive an email update once your appointment request has been scheduled or processed by the staff.</p>

<p>Thank you for using MIT Kiosk.</p>
