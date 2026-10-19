<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ForgotPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $token,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reset Your Password - Cardiovascular Care Center',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.forgot-password',
            with: [
                'user' => $this->user,
                'url' => url('/reset-password/'.$this->token.'?email='.urlencode($this->user->email)),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
