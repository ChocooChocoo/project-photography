<?php

namespace App\Console\Commands;

use App\Models\StudioOwner\BookingAssignedPhotographerModel;
use App\Traits\Notifiable;
use Illuminate\Console\Command;

class CheckAssignmentDeadlinesCommand extends Command
{
    use Notifiable;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'assignments:check-deadlines';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Warn photographers whose assignment response deadline has passed.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $assignments = BookingAssignedPhotographerModel::where('status', 'assigned')
            ->whereNotNull('response_deadline')
            ->where('response_deadline', '<', now())
            ->with(['photographer'])
            ->get();

        foreach ($assignments as $assignment) {
            if ($assignment->photographer) {
                $this->notifyAssignmentDeadlineWarning($assignment, $assignment->photographer);
            }
        }

        $this->info("Sent deadline warning(s) for {$assignments->count()} assignment(s).");

        return self::SUCCESS;
    }
}
