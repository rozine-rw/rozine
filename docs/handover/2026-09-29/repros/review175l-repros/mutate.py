#!/usr/bin/env python3
"""Apply one named mutation to the r175l worktree, run its tests, restore the file.

Usage: mutate.py <name>|all   (run from the r175l worktree root). Prints KILLED/SURVIVED per mutation.
"""
import os
import re
import subprocess
import sys

M = 'database/migrations/2026_09_28_175455_bind_primary_terminal_versions_to_cash_movements.php'
O = 'database/migrations/2026_09_28_163057_require_completed_primary_command_outcomes.php'
RF = 'database/factories/PrimaryReservationRecordFactory.php'
VF = 'database/factories/PrimaryReservationVersionFactory.php'
CF = 'database/factories/BusinessCampaignFactory.php'
PG = 'tests/Feature/PostgreSqlConfigurationTest.php'
AA = 'tests/Feature/AuditAssignmentTest.php'
BE = 'tests/Feature/BusinessExposureReservationTest.php'

T = ['tests/Feature/PrimaryTerminalCashBindingTest.php', 'tests/Feature/PrimaryExpiryOutcomeTest.php',
     'tests/Concurrency/PrimaryTerminalCashConcurrencyTest.php', 'tests/Feature/WalletPostingsTest.php',
     'tests/Feature/PrimaryHoldBindingTest.php', 'tests/Feature/PrimaryReservationSchemaTest.php',
     'tests/Feature/WalletSchemaTest.php', 'tests/Concurrency/WalletPostingsConcurrencyTest.php',
     'tests/Feature/PrimaryOperationBindingTest.php', 'tests/Feature/PrimaryCommandOutcomeTest.php']
MINE = ['tests/Feature/Review175lTerminalCashProbeTest.php', 'tests/Concurrency/Review175lSplitEvidenceRaceTest.php']
DEADLOCK = ['tests/Concurrency/Review175lTerminalCashInstallDeadlockTest.php']
FACT = ['tests/Feature/PrimaryReservationSchemaTest.php', 'tests/Feature/PrimaryTerminalCashBindingTest.php',
        'tests/Feature/Review175iFactoryClockTest.php']

MUTATIONS = {
    'A1-held-cash-allowed': (M, "                        OR (latest_state = 'held' AND (committed OR released))\n", "", T),
    'A2-confirmed-without-commit': (M, "(latest_state = 'confirmed' AND (NOT committed OR released))", "(latest_state = 'confirmed' AND released)", T),
    'A3-confirmed-release-clause': (M, "(latest_state = 'confirmed' AND (NOT committed OR released))", "(latest_state = 'confirmed' AND NOT committed)", T),
    'A4-terminal-without-release': (M, "(latest_state IN ('released', 'expired') AND (NOT released OR committed))", "(latest_state IN ('released', 'expired') AND committed)", T),
    'A5-terminal-commit-clause': (M, "(latest_state IN ('released', 'expired') AND (NOT released OR committed))", "(latest_state IN ('released', 'expired') AND NOT released)", T),
    'A6-expired-unbound': (M, "(latest_state IN ('released', 'expired') AND", "(latest_state IN ('released') AND", T),
    'A7-null-root-allowed': (M, "IF latest_state IS NULL\n                        OR (latest_state = 'held'", "IF (latest_state = 'held'", T),
    'A8-reads-first-revision': (M, "ORDER BY revision DESC LIMIT 1", "ORDER BY revision ASC LIMIT 1", T),
    'A9-ledger-trigger-off': (M, "WHEN (NEW.source_type = 'primary_reservation')\n                    EXECUTE FUNCTION require_primary_terminal_cash();", "WHEN (false)\n                    EXECUTE FUNCTION require_primary_terminal_cash();", T),
    'A10-version-trigger-off': (M, "DEFERRABLE INITIALLY DEFERRED FOR EACH ROW EXECUTE FUNCTION require_primary_terminal_cash();", "DEFERRABLE INITIALLY DEFERRED FOR EACH ROW WHEN (false) EXECUTE FUNCTION require_primary_terminal_cash();", T),
    'A11-version-trigger-immediate': (M, "DEFERRABLE INITIALLY DEFERRED FOR EACH ROW EXECUTE FUNCTION require_primary_terminal_cash();", "DEFERRABLE INITIALLY IMMEDIATE FOR EACH ROW EXECUTE FUNCTION require_primary_terminal_cash();", T),
    'A12-no-terminal-audit': (M, "PERFORM check_primary_terminal_cash(retained_id);", "NULL;", T),
    'A13-audit-roots-only': (M, "SELECT id FROM primary_reservations\n                        UNION SELECT source_id FROM ledger_entries WHERE source_type = 'primary_reservation'", "SELECT id FROM primary_reservations", T),
    'A14-expiry-any-rejection': (M, " AND result->>'code' = 'RESERVATION_EXPIRED') THEN", ") THEN", T),
    'A15-expiry-any-status': (M, "result->>'status' = 'rejected' AND result->>'code'", "result->>'code'", T),
    'A16-no-expiry-audit': (M, "PERFORM check_primary_expiry_outcome(retained_operation);", "NULL;", T),
    'A17-expiry-trigger-wrong-state': (M, "WHEN (NEW.state = 'expired')", "WHEN (NEW.state = 'released')", T),
    'A18-null-system-expiry-refused': (M, "IF operation_id IS NOT NULL AND NOT EXISTS", "IF NOT EXISTS", T),
    'A19-down-no-guard': (M, "IF EXISTS (SELECT 1 FROM primary_reservations)\n                        OR EXISTS (SELECT 1 FROM ledger_entries WHERE source_type = 'primary_reservation') THEN", "IF false THEN", T),
    'A20-down-guard-ledger-only': (M, "IF EXISTS (SELECT 1 FROM primary_reservations)\n                        OR EXISTS", "IF EXISTS", T),
    'A21-down-no-lock': (M, "                LOCK TABLE primary_reservations, primary_reservation_versions, ledger_entries IN ACCESS EXCLUSIVE MODE;\n", "", T),
    'A22-up-no-lock': (M, "                LOCK TABLE business_profiles, business_campaigns, primary_reservations,\n                    primary_reservation_versions, primary_commitments, investor_wallets,\n                    ledger_accounts, ledger_entries, ledger_lines IN ACCESS EXCLUSIVE MODE;\n", "", T),
    'A23-up-no-campaign-lock (fix)': (M, "LOCK TABLE business_profiles, business_campaigns, primary_reservations,", "LOCK TABLE business_profiles, primary_reservations,", T),
    'B1-163057-journal-lock-back': (O, "LOCK TABLE primary_reservations, primary_reservation_versions IN ACCESS EXCLUSIVE MODE;\n                DO $$\n                BEGIN", "LOCK TABLE primary_reservations, primary_reservation_versions, command_operations IN ACCESS EXCLUSIVE MODE;\n                DO $$\n                BEGIN", ['tests/Concurrency/PrimaryOutcomeMigrationLockTest.php', 'tests/Concurrency/Review175iMigrationLockOrderTest.php']),
    'B2-163057-share-first': (O, "LOCK TABLE primary_reservations, primary_reservation_versions IN ACCESS EXCLUSIVE MODE;\n                DO $$\n                BEGIN", "LOCK TABLE command_operations IN SHARE MODE; LOCK TABLE primary_reservations, primary_reservation_versions IN ACCESS EXCLUSIVE MODE;\n                DO $$\n                BEGIN", ['tests/Concurrency/PrimaryOutcomeMigrationLockTest.php']),
    'C1-record-factory-eager-clock': (RF, "'created_at' => fn () => now()->startOfSecond(),", "'created_at' => now()->startOfSecond(),", FACT),
    'C2-version-factory-eager-clock': (VF, "'created_at' => fn () => now()->startOfSecond()];", "'created_at' => now()->startOfSecond()];", FACT),
    'A15b-expiry-any-status (with probe)': (M, "result->>'status' = 'rejected' AND result->>'code'", "result->>'code'", ['tests/Feature/Review175lExpiryStatusProbeTest.php']),
    'C3-campaign-factory-own-clock': (CF, "'expires_at' => fn (array $a) => CarbonImmutable::parse($a['live_at'])->addDays(30),", "'expires_at' => now()->startOfSecond()->addDays(30),", FACT),
    'D1-pgconfig-no-175455-up': (PG, "    $primaryTerminalCash->up();\n    expect(DB::select($primaryGuardQuery))", "    expect(DB::select($primaryGuardQuery))", [PG]),
    'D2-pgconfig-no-165949-up': (PG, "    $primarySourceGuard->up();\n    $primaryTerminalCash->up();", "    $primaryTerminalCash->up();", [PG]),
    'D3-pgconfig-no-anchor-up (function body only)': (PG, "    $postingAnchors->up();\n    $primary->up();", "    $primary->up();", [PG]),
    'D4-audit-assignment-no-175455-up': (AA, "    $primaryTerminalCash->up();\n    expect(DB::select($primaryGuardQuery))", "    expect(DB::select($primaryGuardQuery))", [AA]),
    'D5-exposure-no-175455-up': (BE, "    $primaryTerminalCash->up();\n    expect(DB::select($primaryGuardQuery))", "    expect(DB::select($primaryGuardQuery))", [BE]),
}


def pest(tests, env):
    """Feature (RefreshDatabase) and Concurrency (DatabaseTruncation) files must run in separate processes."""
    killed = False
    parts = []
    for group in (['Feature'], ['Concurrency']):
        files = [t for t in tests if t.startswith('tests/' + group[0] + '/')]
        if not files:
            continue
        out = subprocess.run(['php', '-d', 'memory_limit=2G', 'vendor/bin/pest', '--compact', *files], env=env, capture_output=True, text=True)
        text = re.sub(r'\x1b\[[0-9;]*m', '', out.stdout + out.stderr)
        parts.append(group[0][0] + ': ' + (' '.join(re.findall(r'Tests:\s+(.*)', text)).strip() or text[-200:].strip()))
        killed = killed or out.returncode != 0
    return ('KILLED' if killed else 'SURVIVED'), '; '.join(parts)


def run(name: str) -> str:
    path, old, new, tests = MUTATIONS[name]
    original = open(path).read()
    if original.count(old) != 1:
        return f'{name}: anchor found {original.count(old)} times; NOT APPLIED'
    open(path, 'w').write(original.replace(old, new))
    try:
        env = {**os.environ, 'DB_PORT': '5492', 'DB_HOST': '127.0.0.1', 'PAO_DISABLE': '1'}
        tests = [t for t in tests if os.path.exists(t)]
        mine = [t for t in MINE if os.path.exists(t)] if path == M else []
        verdict, summary = pest(tests, env)
        extra = ''
        if mine and verdict == 'SURVIVED':
            v2, s2 = pest(mine, env)
            extra = ' | review probes: ' + ('KILLED ' if v2 == 'KILLED' else 'survived ') + s2
        return f'{name}: {verdict} [{summary.strip()}]{extra}'
    finally:
        open(path, 'w').write(original)


if __name__ == '__main__':
    names = list(MUTATIONS) if sys.argv[1] == 'all' else sys.argv[1:]
    for name in names:
        print(run(name), flush=True)
