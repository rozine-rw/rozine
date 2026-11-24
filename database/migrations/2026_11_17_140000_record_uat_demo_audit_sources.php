<?php

declare(strict_types=1);

use App\Application\Environment\UatMoneySimulation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

/**
 * Records the synthetic check-in and photos for the staging demo's accepted audits once on deploy,
 * so the visit can start before the scheduler's next minute (Erastus, 17 Nov 2026: no SSH asks). It
 * acts only while the UAT money simulation is on; the scheduler keeps it current afterwards.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (app(UatMoneySimulation::class)->active()) {
            Artisan::call('staging:record-audit-sources');
        }
    }

    public function down(): void
    {
        // Source snapshots are immutable history; nothing is rolled back.
    }
};
