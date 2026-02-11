<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactMessageReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $fullname,
        public readonly string $email,
        public readonly string $mobile,
        public readonly string $message,
    ) {
        //
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Contact Inquiry - ' . $this->fullname,
            replyTo: [$this->email],           // Allows admin to reply directly
            from: 'no-reply@propertyplusafrica.com', // optional override
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.contact-received',  // ← this is the view we'll design
            with: [
                'fullname' => $this->fullname,
                'email'    => $this->email,
                'mobile'   => $this->mobile,
                'message'  => $this->message,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}