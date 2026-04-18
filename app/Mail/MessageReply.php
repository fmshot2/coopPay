<?php

namespace App\Mail;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MessageReply extends Mailable
{
    use Queueable, SerializesModels;

    public $message;
    public $replyBody;
    public $adminName;

    /**
     * Create a new message instance.
     */
    public function __construct(Message $message, string $replyBody, string $adminName)
    {
        $this->message = $message;
        $this->replyBody = $replyBody;
        $this->adminName = $adminName;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Re: ' . ($this->message->subject ?? 'No Subject'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.reply',
            with: [
                'message' => $this->message,
                'replyBody' => $this->replyBody,
                'adminName' => $this->adminName,
            ],
        );
    }
}
