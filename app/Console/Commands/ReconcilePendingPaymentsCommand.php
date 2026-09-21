<?php

namespace App\Console\Commands;

use App\Models\BookingModel;
use Illuminate\Console\Command;

class ReconcilePendingPaymentsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bookings:reconcile-payments';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Re-check bookings whose payment status stayed pending and settle them from recorded payments.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Only re-check bookings whose payment status has been pending for more than two minutes.
        $cutoff = now()->subMinutes(2);

        $bookings = BookingModel::where('payment_status', BookingModel::PAYMENT_PENDING)
            ->where('status', '!=', BookingModel::STATUS_CANCELLED)
            ->where('updated_at', '<=', $cutoff)
            // Only bookings that recorded a successful payment can change status;
            // a purely unpaid booking would otherwise be rewritten on every run.
            ->whereHas('payments', function ($query) {
                $query->where('status', 'succeeded');
            })
            ->get();

        $settled = 0;

        foreach ($bookings as $booking) {
            $before = $booking->payment_status;

            // Same entry point the payment webhook/controllers use, so totals stay consistent.
            $booking->updatePaymentStatus();

            if ($booking->payment_status !== $before) {
                $settled++;
            }
        }

        $this->info("Reconciled {$bookings->count()} pending booking(s); settled {$settled}.");

        return self::SUCCESS;
    }
}
