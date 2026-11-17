<?php

declare(strict_types=1);

use App\Application\Environment\UatMoneySimulation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * Opens this month's monthly audit for one more staging Business once the simulated Business's month
 * is sealed, so QA can send the monthly figures as that Business and check the auditor's costs and
 * drawings against them (QA #674). It picks the Business whose flash audit published most recently,
 * among those the simulation does not name, and opens at most one. It acts only while the UAT money
 * simulation is on; everywhere else it does nothing.
 */
return new class extends Migration
{
    public function up(): void
    {
        $simulation = app(UatMoneySimulation::class);
        if (! $simulation->active()) {
            return;
        }
        $candidates = DB::table('audit_reports')->join('audit_report_publications', 'audit_report_publications.audit_report_id', '=', 'audit_reports.id')
            ->where('audit_reports.kind', 'flash')->where('audit_report_publications.status', 'published')
            ->orderByDesc('audit_report_publications.published_at')->pluck('audit_reports.business_id')->unique();
        foreach ($candidates as $businessId) {
            $businessId = strtolower((string) $businessId);
            if ($simulation->covers($businessId)) {
                continue;
            }
            Artisan::call('staging:open-monthly-audit', ['business' => $businessId, '--unsimulated' => true]);
            if (str_contains(Artisan::output(), 'opened (')) {
                return;
            }
        }
    }

    public function down(): void
    {
        // An opened engagement is closed through operations, never by rolling back.
    }
};
