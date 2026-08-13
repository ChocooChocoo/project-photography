<?php

namespace App\Console\Commands;

use App\Services\BookingCancellationRecoveryService;
use Illuminate\Console\Command;

class EscalatePhotographerCancellationsCommand extends Command
{
    protected $signature = 'bookings:escalate-photographer-cancellations';

    protected $description = 'Queue refunds for expired photographer cancellation recoveries';

    public function handle(BookingCancellationRecoveryService $service): int
    {
        $count = $service->escalateExpired();
        $this->info("Escalated {$count} photographer cancellation(s).");

        return self::SUCCESS;
    }
}
