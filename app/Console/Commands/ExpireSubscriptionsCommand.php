<?php

namespace App\Console\Commands;

use App\Models\StudioPlanModel;
use App\Traits\Notifiable;
use Illuminate\Console\Command;

class ExpireSubscriptionsCommand extends Command
{
    use Notifiable;

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
    protected $description = 'Move ended studio subscriptions through grace and expiry.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $movedToGrace = 0;
        $expired = 0;

        StudioPlanModel::query()
            ->where('status', 'active')
            ->where('payment_status', 'paid')
            ->get()
            ->filter(fn (StudioPlanModel $subscription) => ! $subscription->isActive())
            ->each(function (StudioPlanModel $subscription) use (&$movedToGrace, &$expired) {
                $graceDeadline = $subscription->graceDeadline();

                if (now()->gte($graceDeadline)) {
                    $subscription->update([
                        'status' => 'expired',
                        'grace_ends_at' => $graceDeadline,
                    ]);
                    $expired++;
                    $this->notifySubscriptionLifecycle($subscription->fresh(), 'expired');

                    return;
                }

                $subscription->update([
                    'status' => 'grace',
                    'grace_ends_at' => $graceDeadline,
                ]);
                $movedToGrace++;
                $this->notifySubscriptionLifecycle($subscription->fresh(), 'grace_entered');
            });

        StudioPlanModel::query()
            ->where('status', 'grace')
            ->where('grace_ends_at', '<=', now())
            ->get()
            ->each(function (StudioPlanModel $subscription) use (&$expired) {
                $subscription->update(['status' => 'expired']);
                $expired++;
                $this->notifySubscriptionLifecycle($subscription->fresh(), 'expired');
            });

        $this->info("Moved {$movedToGrace} subscription(s) to grace; expired {$expired} subscription(s).");

        return self::SUCCESS;
    }
}
