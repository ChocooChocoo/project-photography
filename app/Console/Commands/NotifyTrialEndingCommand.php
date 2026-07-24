<?php

namespace App\Console\Commands;

use App\Models\NotificationModel;
use App\Models\StudioPlanModel;
use App\Traits\Notifiable;
use Illuminate\Console\Command;

class NotifyTrialEndingCommand extends Command
{
    use Notifiable;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:notify-trial-ending';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notify studio owners whose free trial subscription is ending within 7 days.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $studioPlans = StudioPlanModel::whereNotNull('trial_ends_at')
            ->where('status', 'active')
            ->with('studio.user')
            ->get()
            ->filter->isTrialEndingSoon();

        $notified = 0;

        foreach ($studioPlans as $studioPlan) {
            $studio = $studioPlan->studio;
            if (!$studio || !$studio->user) {
                continue;
            }

            $alreadyNotifiedToday = NotificationModel::where('user_id', $studio->user->id)
                ->where('type', 'trial_ending')
                ->whereDate('created_at', now()->toDateString())
                ->exists();

            if ($alreadyNotifiedToday) {
                continue;
            }

            $daysLeft = max(0, now()->diffInDays($studioPlan->trial_ends_at, false));
            $this->notifyTrialEnding($studio, $studio->user, $daysLeft);
            $notified++;
        }

        $this->info("Notified {$notified} owner(s) about an ending free trial.");

        return self::SUCCESS;
    }
}
