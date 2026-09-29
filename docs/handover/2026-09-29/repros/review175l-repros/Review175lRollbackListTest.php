<?php

declare(strict_types=1);

/*
 * Review repro for PR #175 c8f30fcb. Copy into tests/Feature/.
 *
 * tests/Feature/AuditReportPersistenceTest.php:188-257 ("rolls the unused report schema back and
 * reapplies it ...") rolls the Primary chain back through 143756, which drops primary_reservations and
 * primary_reservation_versions, but its list stops at 163057: it never takes 175455 (or 165949) down
 * and never brings 175455 back up. Dropping primary_reservation_versions silently drops
 * primary_version_cash_bound and primary_expiry_outcome_bound; only ledger_primary_terminal_bound
 * (on ledger_entries, which this test keeps) survives. The test asserts nothing about triggers.
 *
 * The first test replays that exact list. FAILS on c8f30fcb (two 175455 triggers missing afterwards).
 * The second is a PROBE for the three lists this range fixed; it PASSES.
 */

use Illuminate\Support\Facades\DB;

function r175lGuards(): array
{
    return DB::select("SELECT tgname, pg_get_triggerdef(t.oid) AS definition FROM pg_trigger t JOIN pg_class c ON c.oid = t.tgrelid
        WHERE NOT t.tgisinternal AND c.relname IN ('primary_reservations', 'primary_reservation_versions', 'primary_commitments', 'ledger_entries') ORDER BY tgname");
}

function r175lMigration(string $name): object
{
    return require database_path('migrations/'.$name.'.php');
}

it('DEFECT: the AuditReportPersistenceTest round trip restores every Primary and ledger guard', function (): void {
    $before = r175lGuards();
    $names = ['2026_09_28_163057_require_completed_primary_command_outcomes', '2026_09_28_161335_bind_primary_reservations_to_wallet_holds',
        '2026_09_28_154941_enforce_primary_ordinal_exclusion', '2026_09_28_152823_bind_primary_evidence_to_command_actors',
        '2026_09_28_151253_enforce_primary_campaign_capacity_and_closure', '2026_09_28_143756_create_primary_reservation_records'];
    foreach ($names as $name) {
        r175lMigration($name)->down();
    }
    foreach (array_reverse($names) as $name) {
        r175lMigration($name)->up();
    }
    expect(array_column($before, 'tgname'))->toContain('primary_version_cash_bound', 'primary_expiry_outcome_bound', 'ledger_primary_terminal_bound')
        ->and(array_column(r175lGuards(), 'tgname'))->toBe(array_column($before, 'tgname'));
});

it('PROBE: a full chain round trip including 165949 and 175455 restores the same guards', function (): void {
    $before = r175lGuards();
    $names = ['2026_09_28_175455_bind_primary_terminal_versions_to_cash_movements', '2026_09_28_165949_reject_unbound_primary_commitment_sources',
        '2026_09_28_163057_require_completed_primary_command_outcomes', '2026_09_28_161335_bind_primary_reservations_to_wallet_holds',
        '2026_09_28_154941_enforce_primary_ordinal_exclusion', '2026_09_28_152823_bind_primary_evidence_to_command_actors',
        '2026_09_28_151253_enforce_primary_campaign_capacity_and_closure', '2026_09_28_143756_create_primary_reservation_records'];
    foreach ($names as $name) {
        r175lMigration($name)->down();
    }
    foreach (array_reverse($names) as $name) {
        r175lMigration($name)->up();
    }
    expect(r175lGuards())->toEqual($before);
});
