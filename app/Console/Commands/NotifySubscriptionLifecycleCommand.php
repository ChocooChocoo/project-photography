<?php

namespace App\Console\Commands;

use App\Models\StudioPlanModel;
use App\Traits\Notifiable;
use Illuminate\Console\Command;

class NotifySubscriptionLifecycleCommand extends Command
{
    use Notifiable;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:notify-lifecycle';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notify studio owners about subscription end and grace milestones.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $sent = 0;

        StudioPlanModel::with('studio.user')
            ->whereIn('status', ['active', 'grace'])
            ->where('payment_status', 'paid')
            ->get()
            ->each(function (StudioPlanModel $subscription) use (&$sent) {
                $event = $this->eventDueToday($subscription);

                if ($event && $this->notifySubscriptionLifecycle($subscription, $event)) {
                    $sent++;
                }
            });

        $this->info("Sent {$sent} lifecycle notification(s).");

        return self::SUCCESS;
    }

    private function eventDueToday(StudioPlanModel $subscription): ?string
    {
        if ($subscription->status === 'grace') {
            foreach ([3, 1] as $days) {
                if (now()->isSameDay($subscription->graceDeadline()->subDays($days))) {
                    return "grace_{$days}";
                }
            }

            return null;
        }

        $deadline = $subscription->trial_ends_at ?? $subscription->end_date->copy()->endOfDay();
        foreach ([7, 3, 1] as $days) {
            if (now()->isSameDay($deadline->copy()->subDays($days))) {
                return "ending_{$days}";
            }
        }

        return null;
    }
}
