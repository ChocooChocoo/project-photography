<?php

namespace App\Mail;

use App\Models\BookingModel;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Tells the client and the studio owner that the photographer checked in
 * on site.
 */
class PhotographerArrivedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public BookingModel $booking,
        public string $photographerName,
        public ?int $distanceMeters,
        public string $actionUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your photographer has arrived - '.$this->booking->booking_reference);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.photographer-arrived');
    }
}
