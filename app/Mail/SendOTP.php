<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

class SendOTP extends Mailable
{
    use Queueable, SerializesModels;

    public $otp;

    public function __construct($otp)
    {
        $this->otp = $otp;
    }

    public function envelope(): Envelope
    {
        $fromAddress = (string) config('mail.from.address', 'mitportalservices@gmail.com');
        $fromName = (string) config('mail.from.name', 'MIT Campus Portal');

        return new Envelope(
            from: new Address($fromAddress, $fromName),
            replyTo: [new Address($fromAddress, $fromName)],
            subject: 'MIT Campus Portal Login Code: ' . $this->otp,
        );
    }

    public function headers(): Headers
    {
        return new Headers(
            text: [
                'X-Priority' => '1 (Highest)',
                'Importance' => 'High',
            ],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.otp',
            text: 'emails.otp_plain',
        );
    }
}
