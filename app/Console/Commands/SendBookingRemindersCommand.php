<?php

namespace App\Console\Commands;

use App\Models\BookingModel;
use App\Models\NotificationModel;
use App\Traits\Notifiable;
use Illuminate\Console\Command;

class SendBookingRemindersCommand extends Command
{
    use Notifiable;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bookings:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send reminders for confirmed bookings happening in the next few days.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $sent = 0;
        $daysAhead = [1, 3];

        $bookings = BookingModel::where('status', BookingModel::STATUS_CONFIRMED)
            ->where(function ($query) use ($daysAhead) {
                foreach ($daysAhead as $days) {
                    $query->orWhereDate('event_date', now()->addDays($days)->toDateString());
                }
            })
            ->get();

        foreach ($bookings as $booking) {
            $days = $booking->event_date->diffInDays(now()->startOfDay());

            if ($booking->client) {
                $sent += $this->sendReminderIfDue($booking, $booking->client, $days);
            }

            $studio = $booking->booking_type === 'studio' ? $booking->studio()->first() : null;
            if ($studio && $studio->user) {
                $sent += $this->sendReminderIfDue($booking, $studio->user, $days);
            }

            $freelancer = $booking->booking_type === 'freelancer' ? $booking->freelancer()->first() : null;
            if ($freelancer && $freelancer->user) {
                $sent += $this->sendReminderIfDue($booking, $freelancer->user, $days);
            }
        }

        $this->info("Sent {$sent} booking reminder(s).");

        return self::SUCCESS;
    }

    /**
     * Send a reminder notification unless one was already sent today for this booking and recipient.
     */
    private function sendReminderIfDue($booking, $user, int $days): int
    {
        $alreadyReminded = NotificationModel::where('user_id', $user->id)
            ->where('type', 'reminder')
            ->where('data->booking_id', $booking->id)
            ->whereDate('created_at', now()->toDateString())
            ->exists();

        if ($alreadyReminded) {
            return 0;
        }

        $this->notifyReminder($booking, $user, (string) $days);

        return 1;
    }
}
