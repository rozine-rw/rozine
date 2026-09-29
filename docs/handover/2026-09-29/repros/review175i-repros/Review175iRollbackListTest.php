<?php

declare(strict_types=1);

/*
 * Review repro for PR #175: tests/Feature/PostgreSqlConfigurationTest.php (and the shorter lists in
 * AuditAssignmentTest.php / BusinessExposureReservationTest.php) roll the Primary chain back through
 * 143756 only, without 161335 or 163057 first. Dropping the tables drops their triggers silently, the
 * later up() never recreates them, and nothing asserts they are back. Copy into tests/Feature/.
 *
 * FAILS on ad598a09: after the PostgreSqlConfigurationTest-shaped round trip the outcome and wallet
 * binding triggers are gone.
 */

use Illuminate\Support\Facades\DB;

it('DEFECT: the PostgreSqlConfigurationTest Primary round trip restores every Primary guard', function (): void {
    $guards = "SELECT tgname FROM pg_trigger WHERE tgname IN ('primary_reservation_outcome_bound', 'primary_version_outcome_bound',
        'primary_reservation_wallet_bound', 'ledger_primary_reservation_bound') ORDER BY tgname";
    $before = array_column(DB::select($guards), 'tgname');
    $primary = require database_path('migrations/2026_09_28_143756_create_primary_reservation_records.php');
    $primaryCapacity = require database_path('migrations/2026_09_28_151253_enforce_primary_campaign_capacity_and_closure.php');
    $primaryCommands = require database_path('migrations/2026_09_28_152823_bind_primary_evidence_to_command_actors.php');
    $primaryOrdinals = require database_path('migrations/2026_09_28_154941_enforce_primary_ordinal_exclusion.php');
    $primaryOrdinals->down();
    $primaryCommands->down();
    $primaryCapacity->down();
    $primary->down();
    $primary->up();
    $primaryCapacity->up();
    $primaryCommands->up();
    $primaryOrdinals->up();

    expect($before)->toHaveCount(4)
        ->and(array_column(DB::select($guards), 'tgname'))->toBe($before);
});
