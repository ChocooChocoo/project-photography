<?php

namespace App\Mail;

use App\Models\StudioPlanModel;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionLifecycleMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public StudioPlanModel $subscription,
        public string $title,
        public string $message,
        public $deadline,
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->title.' - '.config('app.name'));
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(view: 'emails.subscription-lifecycle');
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
