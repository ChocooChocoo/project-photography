<?php

namespace App\Console\Commands;

use App\Models\BookingModel;
use App\Traits\Notifiable;
use Illuminate\Console\Command;

class ExpirePendingBookingsCommand extends Command
{
    use Notifiable;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bookings:expire-pending';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cancel pending bookings that were not confirmed before their expiry deadline.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $bookings = BookingModel::where('status', 'pending')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->with(['client'])
            ->get();

        foreach ($bookings as $booking) {
            $booking->update([
                'status' => BookingModel::STATUS_CANCELLED,
                'cancellation_reason' => 'Booking expired — not confirmed within the required timeframe.',
            ]);

            if ($booking->client) {
                $this->notifyBookingExpired($booking, $booking->client, 'client.my-bookings.index');
            }

            $studio = $booking->booking_type === 'studio' ? $booking->studio()->first() : null;
            if ($studio && $studio->user) {
                $this->notifyBookingExpired($booking, $studio->user, 'owner.booking.index');
            }
        }

        $this->info("Expired {$bookings->count()} pending booking(s).");

        return self::SUCCESS;
    }
}
