<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Application\Auditor\Contracts\AuditSourceFactsStore;
use App\Application\Environment\EnvironmentIsolation;
use App\Application\Environment\UatMoneySimulation;
use Illuminate\Console\Command;

/**
 * Records the on-site capture a staging demo cannot make from a desk (Erastus, 17 Nov 2026): for
 * each accepted audit of a Business the UAT money simulation names, a synthetic check-in at the
 * Business's verified premises and the required storefront and inventory photos, as the accepted
 * Auditor's isolated source facts. The scheduler runs it every minute, so a newly accepted audit is
 * ready to visit without anyone running a command on the box. It does nothing wherever the
 * simulation is off or fixtures are not allowed, never touches any other Business, and never
 * replaces facts already recorded.
 */
class RecordStagingAuditSources extends Command
{
    protected $hidden = true;

    protected $signature = 'staging:record-audit-sources';

    protected $description = 'Record synthetic check-in and photos for the UAT money simulation\'s accepted audits (UAT only)';

    public function handle(UatMoneySimulation $simulation, EnvironmentIsolation $isolation, AuditSourceFactsStore $store): int
    {
        if (! $simulation->active() || ! $isolation->canSeed()) {
            return self::SUCCESS;
        }
        foreach ($store->recordSimulated() as $assignmentId => $snapshotId) {
            $this->line($assignmentId.': check-in and photos recorded ('.$snapshotId.').');
        }

        return self::SUCCESS;
    }
}
