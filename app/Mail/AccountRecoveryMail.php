<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

class AccountRecoveryMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public string $newPassword;

    public function __construct(User $user, string $newPassword)
    {
        $this->user = $user;
        $this->newPassword = $newPassword;
    }

    public function envelope(): Envelope
    {
        $fromAddress = (string) config('mail.from.address', 'mitportalservices@gmail.com');
        $fromName = (string) config('mail.from.name', 'MIT Campus Portal');

        return new Envelope(
            from: new Address($fromAddress, $fromName),
            replyTo: [new Address($fromAddress, $fromName)],
            subject: 'Your MIT Campus Portal Account Details',
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
            view: 'emails.account_recovery',
            text: 'emails.account_recovery_plain',
        );
    }
}
