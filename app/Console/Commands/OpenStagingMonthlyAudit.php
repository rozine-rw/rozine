<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Application\Auditor\Contracts\AuditAssignmentStore;
use App\Application\Environment\UatMoneySimulation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

/**
 * Opens this Kigali month's monthly audit now for a Business the UAT money simulation names, so the
 * staging demo can show a Business sending its monthly figures and an auditor's visit and seal without
 * waiting for the month to end. The engagement audits the current month and goes to an
 * eligible partner exactly as the scheduler's dispatch would. It refuses for any other Business and
 * wherever the simulation is off, and opens nothing over an engagement that is still open.
 */
class OpenStagingMonthlyAudit extends Command
{
    protected $hidden = true;

    protected $signature = 'staging:open-monthly-audit {business? : One simulated Business id; every simulated Business when left out}';

    protected $description = 'Open this month\'s monthly audit now for the UAT money simulation\'s Businesses (UAT only)';

    public function handle(UatMoneySimulation $simulation, AuditAssignmentStore $store): int
    {
        if (! $simulation->active()) {
            $this->components->error('UAT_SIMULATION_OFF: the UAT money simulation is off here, so nothing was opened.');

            return self::FAILURE;
        }
        $named = (array) Config::get('app.uat_money_simulation_businesses');
        $businesses = $this->argument('business') === null ? $named : [(string) $this->argument('business')];
        $period = now('Africa/Kigali')->format('Y-m');
        $status = self::SUCCESS;
        foreach ($businesses as $businessId) {
            $businessId = strtolower(trim((string) $businessId));
            if (! $simulation->covers($businessId)) {
                $this->components->error('UAT_SIMULATION_BUSINESS_REQUIRED: '.$businessId.' is not a simulated Business, so nothing was opened.');
                $status = self::FAILURE;

                continue;
            }
            $assignment = $store->requestMonthly($businessId, $period);
            $this->line($assignment === null
                ? $businessId.': an audit engagement is still open, so no monthly audit was opened.'
                : $businessId.': monthly audit for '.$period.' opened ('.$assignment.').');
        }

        return $status;
    }
}
