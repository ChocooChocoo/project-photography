<?php

namespace App\Console\Commands;

use App\Models\NotificationModel;
use Illuminate\Console\Command;

class NotificationsPruneCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:prune {--days=30 : Delete read notifications older than this many days.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete read notifications older than the retention period.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoff = now()->subDays($days);

        $deleted = NotificationModel::whereNotNull('read_at')
            ->where('created_at', '<', $cutoff)
            ->delete();

        $this->info("Pruned {$deleted} read notification(s) older than {$days} day(s).");

        return self::SUCCESS;
    }
}
