<?php

declare(strict_types=1);

use App\Application\Environment\UatMoneySimulation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

/**
 * Opens the staging demo Business's monthly audit for this month as the deploy runs, so its owner can
 * send the monthly figures and an auditor can visit and seal the report the same day, without anyone
 * running a command on the box (Erastus, 16-17 Nov 2026: no SSH asks). It acts only while the UAT
 * money simulation is on, and only for the Businesses it names; everywhere else it does nothing.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! app(UatMoneySimulation::class)->active()) {
            return;
        }
        Artisan::call('staging:open-monthly-audit');
    }

    public function down(): void
    {
        // An opened engagement is closed through operations, never by rolling back.
    }
};
