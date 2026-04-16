<?php

namespace App\Console\Commands;

use App\Services\ProcurementWorkflowService;
use Illuminate\Console\Command;

class EscalateOverdueProcurementRequestsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'procurement:escalate-overdue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Escalate overdue procurement approvals to the studio owner.';

    /**
     * Execute the console command.
     */
    public function handle(ProcurementWorkflowService $procurementWorkflowService): int
    {
        $count = $procurementWorkflowService->escalateOverdueRequests();

        $this->info("Escalated {$count} overdue procurement request(s).");

        return self::SUCCESS;
    }
}
