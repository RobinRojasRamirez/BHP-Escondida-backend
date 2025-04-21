<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;

class VerificationCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $code;

    /**
     * Create a new message instance.
     */
    public function __construct(string $code)
    {
        $this->code = $code;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tu código de verificación'
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.verification_code', // <- tu vista personalizada
            with: [
                'code' => $this->code
            ]
        );
    }

    /**
     * No attachments
     */
    public function attachments(): array
    {
        return [];
    }
}