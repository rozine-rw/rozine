#!/usr/bin/env python3
"""Apply one named mutation to the r175o worktree, run its tests, restore the file.

Usage: mutate.py <name>...|all   (run from the r175o worktree root). Prints KILLED/SURVIVED per mutation.
PR-only mutations run only the PR's own tests; review repros are listed separately where noted.
"""
import os
import re
import subprocess
import sys

RES = 'app/Infrastructure/Primary/EloquentPrimaryReservations.php'
CHK = 'app/Infrastructure/Primary/EloquentPrimaryCheckout.php'
W = 'database/migrations/2026_09_28_161335_bind_primary_reservations_to_wallet_holds.php'
M = 'database/migrations/2026_09_28_175455_bind_primary_terminal_versions_to_cash_movements.php'
ARP = 'tests/Feature/AuditReportPersistenceTest.php'
AA = 'tests/Feature/AuditAssignmentTest.php'
BE = 'tests/Feature/BusinessExposureReservationTest.php'
PG = 'tests/Feature/PostgreSqlConfigurationTest.php'
LOCKT = ['tests/Concurrency/PrimaryCashMigrationLockTest.php']
CONF = ['tests/Feature/PrimaryConfirmationTest.php', 'tests/Feature/PrimaryCheckoutTest.php',
        'tests/Concurrency/PrimaryConfirmationConcurrencyTest.php']
EXP = ['tests/Feature/PrimaryExpiryOutcomeTest.php', 'tests/Feature/PrimaryTerminalCashBindingTest.php']

ANCHOR = "    expect(DB::select($primaryGuardQuery))->toEqual($primaryGuards)\n"


def drift(php: str) -> str:
    return "    " + php + "\n" + ANCHOR


HELPER = drift("""$d = DB::selectOne("SELECT pg_get_functiondef('check_primary_terminal_cash(varchar)'::regprocedure) AS d")->d; $m = str_replace('DESC LIMIT 1', 'ASC LIMIT 1', $d); if ($m === $d) { throw new LogicException('no drift'); } DB::unprepared($m);""")
LEDGER = drift("""$d = DB::selectOne("SELECT pg_get_functiondef('ledger_entry_balance_check(varchar)'::regprocedure) AS d")->d; $m = str_replace('line_count < 2', 'line_count < 1', $d); if ($m === $d) { throw new LogicException('no drift'); } DB::unprepared($m);""")
TRIGFN = drift("""$d = DB::selectOne("SELECT pg_get_functiondef('require_primary_hold_binding()'::regprocedure) AS d")->d; $m = preg_replace('/BEGIN/', "BEGIN\\n    -- drift", $d, 1); DB::unprepared($m);""")
CONSTR = drift("""DB::statement("ALTER TABLE ledger_entries DROP CONSTRAINT primary_commitment_source_unavailable, ADD CONSTRAINT primary_commitment_source_unavailable CHECK (source_type <> 'primary_commitment' OR source_id IS NULL)");""")

MUTATIONS = {
    # 175l P3-1: the campaign lock removal and its regression
    'L1-161335-campaign-lock-back': (W, "LOCK TABLE business_profiles, primary_reservations,\n                    investor_wallets", "LOCK TABLE business_profiles, business_campaigns, primary_reservations,\n                    investor_wallets", LOCKT),
    'L2-175455-campaign-lock-back': (M, "LOCK TABLE business_profiles, primary_reservations,\n                    primary_reservation_versions, primary_commitments", "LOCK TABLE business_profiles, business_campaigns, primary_reservations,\n                    primary_reservation_versions, primary_commitments", LOCKT),
    'L3-161335-no-up-lock': (W, "                LOCK TABLE business_profiles, primary_reservations,\n                    investor_wallets, ledger_accounts, ledger_entries, ledger_lines IN ACCESS EXCLUSIVE MODE;\n", "", LOCKT),
    'L4-175455-no-up-lock': (M, "                LOCK TABLE business_profiles, primary_reservations,\n                    primary_reservation_versions, primary_commitments, investor_wallets,\n                    ledger_accounts, ledger_entries, ledger_lines IN ACCESS EXCLUSIVE MODE;\n", "", LOCKT),
    'L5-175455-business-lock-dropped': (M, "LOCK TABLE business_profiles, primary_reservations,\n                    primary_reservation_versions", "LOCK TABLE primary_reservations,\n                    primary_reservation_versions", LOCKT),
    # K2
    'K2-guard-removed': (RES, "                if ($posting->replayed) {\n                    throw new RuntimeException('RESERVATION_INTEGRITY_FAILED');\n                }\n", "", CONF),
    # surviving test gaps from 175k
    'M06-requote-on-version-only': (RES, "if ($terms->disclosureSha256 !== $reservation->terms->disclosureSha256) {", "if ($terms->disclosureVersion !== $reservation->terms->disclosureVersion) {", CONF),
    'M18-fp-no-disclosure-version': (CHK, "                        'disclosure_version' => $disclosureVersion, 'disclosure_sha256' => $disclosureSha256],", "                        'disclosure_sha256' => $disclosureSha256],", CONF),
    'M19-fp-no-disclosure-sha': (CHK, "                        'disclosure_version' => $disclosureVersion, 'disclosure_sha256' => $disclosureSha256],", "                        'disclosure_version' => $disclosureVersion],", CONF),
    # expiry status
    'E1-expiry-any-status': (M, "result->>'status' = 'rejected' AND result->>'code'", "result->>'code'", EXP),
    'E2-expiry-status-caseless': (M, "result->>'status' = 'rejected' AND result->>'code'", "lower(result->>'status') = 'rejected' AND result->>'code'", EXP),
    'E3-expiry-status-or-completed': (M, "result->>'status' = 'rejected' AND result->>'code'", "result->>'status' IN ('rejected', 'completed') AND result->>'code'", EXP),
    'E4-expiry-status-or-pending': (M, "result->>'status' = 'rejected' AND result->>'code'", "result->>'status' IN ('rejected', 'pending') AND result->>'code'", EXP),
    # 175l P3-2 / P3-3: round trips
    'R1-arp-no-175455-up': (ARP, "    $primaryTerminalCash->up();\n" + ANCHOR, ANCHOR, [ARP]),
    'R2-arp-no-165949-up': (ARP, "    $primarySourceGuard->up();\n    $primaryTerminalCash->up();\n", "    $primaryTerminalCash->up();\n", [ARP]),
    'R3-arp-no-161335-up': (ARP, "    $walletBindings->up();\n    $outcomes->up();\n", "    $outcomes->up();\n", [ARP]),
    'R4-pg-no-anchor-up': (PG, "    $postingAnchors->up();\n    $primary->up();", "    $primary->up();", [PG]),
    'R5-pg-no-ledger-seal-up': (PG, "    $ledgerSeal->up();\n", "", [PG]),
    'B1-arp-helper-body-drift': (ARP, ANCHOR, HELPER, [ARP]),
    'B2-arp-ledger-balance-drift': (ARP, ANCHOR, LEDGER, [ARP]),
    'B3-arp-trigger-fn-drift': (ARP, ANCHOR, TRIGFN, [ARP]),
    'B4-arp-constraint-drift': (ARP, ANCHOR, CONSTR, [ARP]),
    'B5-aa-helper-body-drift': (AA, ANCHOR, HELPER, [AA]),
    'B6-be-ledger-balance-drift': (BE, ANCHOR, LEDGER, [BE]),
    'B7-pg-constraint-drift': (PG, ANCHOR, CONSTR, [PG]),
    'B8-aa-trigger-fn-drift': (AA, ANCHOR, TRIGFN, [AA]),
}


def pest(tests, env):
    """Feature (RefreshDatabase) and Concurrency (DatabaseTruncation) files run in separate processes."""
    killed = False
    parts = []
    for group in ('Feature', 'Concurrency'):
        files = [t for t in tests if t.startswith('tests/' + group + '/')]
        if not files:
            continue
        out = subprocess.run(['php', '-d', 'memory_limit=2G', 'vendor/bin/pest', '--compact', *files], env=env, capture_output=True, text=True)
        text = re.sub(r'\x1b\[[0-9;]*m', '', out.stdout + out.stderr)
        parts.append(group[0] + ': ' + (' '.join(re.findall(r'Tests:\s+(.*)', text)).strip() or text[-300:].strip()))
        killed = killed or out.returncode != 0
    return ('KILLED' if killed else 'SURVIVED'), '; '.join(parts)


def run(name: str) -> str:
    path, old, new, tests = MUTATIONS[name]
    original = open(path).read()
    if original.count(old) != 1:
        return f'{name}: anchor found {original.count(old)} times; NOT APPLIED'
    open(path, 'w').write(original.replace(old, new))
    try:
        env = {**os.environ, 'DB_PORT': '5495', 'DB_HOST': '127.0.0.1', 'PAO_DISABLE': '1'}
        verdict, summary = pest([t for t in tests if os.path.exists(t)], env)
        return f'{name}: {verdict} [{summary.strip()}]'
    finally:
        open(path, 'w').write(original)


if __name__ == '__main__':
    names = list(MUTATIONS) if sys.argv[1] == 'all' else sys.argv[1:]
    for name in names:
        print(run(name), flush=True)
