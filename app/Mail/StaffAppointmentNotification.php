<?php

namespace App\Mail;

use App\Models\AppointmentRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StaffAppointmentNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public AppointmentRequest $appointment)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'New Appointment Request Received - MIT Kiosk');
    }

    public function content(): Content
    {
        $this->appointment->loadMissing(['targetUser', 'waypoint']);
        return new Content(view: 'emails.staff_appointment_notification');
    }
}
