<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AlumniWelcome extends Mailable
{
    use Queueable, SerializesModels;

    public $alumni;

    public function __construct($alumni)
    {
        $this->alumni = $alumni;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: 'alumni@kwenenginternational.com',
            subject: 'Welcome to Kweneng International Alumni Network!',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.alumni-welcome',
            with: [
                'alumni' => $this->alumni,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
