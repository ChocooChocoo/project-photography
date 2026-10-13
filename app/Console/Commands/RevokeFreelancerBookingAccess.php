<?php

namespace App\Console\Commands;

use App\Services\FreelancerBookingAccess;
use Illuminate\Console\Command;

class RevokeFreelancerBookingAccess extends Command
{
    protected $signature = 'bookings:revoke-freelancer-access';

    protected $description = 'Revoke expired booking-scoped freelancer access';

    public function handle(FreelancerBookingAccess $access): int
    {
        $this->info('Revoked '.$access->revokeExpired().' temporary grants.');

        return self::SUCCESS;
    }
}
