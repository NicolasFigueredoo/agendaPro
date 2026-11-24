<?php

namespace App\Mail;

use App\Models\Booking;
use App\Models\Location;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingConfirmed extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Booking $booking)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tu turno está confirmado - '.Location::current()->name,
        );
    }

    public function content(): Content
    {
        $this->booking->loadMissing(['client', 'service', 'professional', 'payments']);

        return new Content(
            view: 'mail.booking-confirmed',
            with: [
                'booking' => $this->booking,
                'location' => Location::current(),
                'paid' => $this->booking->paidAmount(),
            ],
        );
    }
}
