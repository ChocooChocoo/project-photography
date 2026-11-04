<?php

namespace App\Mail;

use App\Models\BookingModel;
use App\Models\PaymentModel;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentReceiptMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public BookingModel $booking, public PaymentModel $payment) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Payment received - '.$this->booking->booking_reference);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.payment-receipt');
    }
}
