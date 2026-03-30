<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmailReport extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $result
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Email Report',
        );
    }

    public function content(): Content
    {
        return new Content(
            html: 'emails.node.report',
            with: [
                'result' => $this->result,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
