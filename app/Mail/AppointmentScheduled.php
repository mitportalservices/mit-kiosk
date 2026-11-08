<?php

namespace App\Mail;

use App\Models\AppointmentRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AppointmentScheduled extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public AppointmentRequest $appointment)
    {
    }

    public function envelope(): Envelope
    {
        $fromAddress = (string) config('mail.from.address', 'mitportalservices@gmail.com');
        $fromName = (string) config('mail.from.name', 'MIT Campus Portal');

        return new Envelope(
            from: new Address($fromAddress, $fromName),
            replyTo: [new Address($fromAddress, $fromName)],
            subject: 'MIT Campus Portal - Your Appointment Schedule',
        );
    }

    public function content(): Content
    {
        $this->appointment->loadMissing('targetUser');
        return new Content(
            view: 'emails.appointment_scheduled',
            text: 'emails.appointment_scheduled_plain',
        );
    }
}
