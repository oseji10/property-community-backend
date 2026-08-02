// app/Mail/NewMessageReceived.php
<?php

namespace App\Mail;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewMessageReceived extends Mailable
{
    use Queueable, SerializesModels;

    public $message;
    public $recipient;
    public $sender;

    public function __construct(Message $message)
    {
        $this->message = $message;
        $this->recipient = $message->receiver;
        $this->sender = $message->sender;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Message from ' . ($this->sender->firstName ?? 'User'),
        );
    }

    public function build()
    {
        return $this->subject('New Message from ' . ($this->sender->firstName ?? 'User'))
                    ->view('emails.messages.new-message');
    }

    public function attachments(): array
    {
        return [];
    }
}