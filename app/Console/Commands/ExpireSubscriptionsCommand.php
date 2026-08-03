<?php

namespace App\Console\Commands;

use App\Models\StudioPlanModel;
use Illuminate\Console\Command;

class ExpireSubscriptionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark ended studio trials and paid subscriptions as expired.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $expiredTrials = StudioPlanModel::query()
            ->where('status', 'active')
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<=', now())
            ->update(['status' => 'expired']);

        $expiredPaid = StudioPlanModel::query()
            ->where('status', 'active')
            ->whereNull('trial_ends_at')
            ->whereDate('end_date', '<', now()->toDateString())
            ->update(['status' => 'expired']);

        $expired = $expiredTrials + $expiredPaid;
        $this->info("Expired {$expired} subscription(s).");

        return self::SUCCESS;
    }
}
