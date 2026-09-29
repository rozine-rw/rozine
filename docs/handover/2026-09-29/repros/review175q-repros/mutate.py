#!/usr/bin/env python3
"""Apply one named mutation to the r175q worktree, run the PR's tests (then our repros), restore the file.

Usage: mutate.py <name>...|all   (run from the r175q worktree root). Prints KILLED/SURVIVED per mutation,
first by the PR's own tests, then by the review repros.
"""
import os
import re
import subprocess
import sys

K = 'database/migrations/2026_09_28_195022_bind_primary_confirmation_receipts_to_commitments.php'
O = 'database/migrations/2026_09_28_154941_enforce_primary_ordinal_exclusion.php'
K_PR = ['tests/Feature/PrimaryConfirmationReceiptTest.php', 'tests/Feature/PrimaryCommandOutcomeTest.php',
        'tests/Feature/PrimaryOperationBindingTest.php', 'tests/Feature/PrimaryConfirmationTest.php',
        'tests/Feature/PrimaryCheckoutTest.php', 'tests/Feature/PostgreSqlConfigurationTest.php',
        'tests/Feature/PrimaryReservationSchemaTest.php', 'tests/Concurrency/PrimaryConfirmationConcurrencyTest.php']
K_OURS = ['tests/Feature/Review175qReceiptAttackTest.php', 'tests/Feature/Review175kConfirmationTest.php --filter=K5']
O_PR = ['tests/Concurrency/PrimaryOrdinalMigrationLockTest.php', 'tests/Feature/PrimaryOrdinalPersistenceTest.php']
O_OURS = ['tests/Concurrency/Review175oOrdinalInstallDeadlockTest.php', 'tests/Concurrency/Review175qInstallDeadlockTest.php --filter=154941']
D_OURS = ['tests/Concurrency/Review175qInstallDeadlockTest.php --filter=195022']

TEXT_COMPARE = """operation.result->>'status' = 'completed' AND operation.result->>'code' = 'RESERVATION_CONFIRMED'
                          AND operation.result->>'operation_id' = commitment.operation_id AND operation.result->>'revision' = version.revision::text
                          AND operation.result->'data'->>'reservation_id' = reservation.id AND operation.result->'data'->>'commitment_id' = commitment.id
                          AND operation.result->'data'->>'amount' = reservation.principal::text"""
CONTAINS = """operation.result @> jsonb_build_object(
                              'status', 'completed', 'code', 'RESERVATION_CONFIRMED',
                              'operation_id', commitment.operation_id, 'revision', version.revision,
                              'data', jsonb_build_object('reservation_id', reservation.id,
                                  'commitment_id', commitment.id, 'amount', reservation.principal::text))"""
HIST = """                DO $$
                DECLARE retained_id varchar;
                BEGIN
                    FOR retained_id IN SELECT id FROM primary_commitments LOOP
                        PERFORM check_primary_confirmation_receipt(retained_id);
                    END LOOP;
                END;
                $$;
"""
DOWN_GUARD = """                DO $$ BEGIN
                    IF EXISTS (SELECT 1 FROM primary_commitments) THEN
                        RAISE EXCEPTION 'Retained Primary confirmation receipts require a forward migration' USING ERRCODE = '23514';
                    END IF;
                END; $$;
"""
UP_LOCK = """            DB::unprepared(<<<'SQL'
                LOCK TABLE primary_reservations, primary_reservation_versions, primary_commitments IN ACCESS EXCLUSIVE MODE;
                CREATE OR REPLACE FUNCTION check"""

MUTATIONS = {
    'K01-no-status': (K, "'status', 'completed', 'code'", "'code'", K_PR, K_OURS),
    'K02-no-code': (K, "'status', 'completed', 'code', 'RESERVATION_CONFIRMED',", "'status', 'completed',", K_PR, K_OURS),
    'K03-no-operation-id': (K, "'operation_id', commitment.operation_id, 'revision'", "'revision'", K_PR, K_OURS),
    'K04-no-revision': (K, "'operation_id', commitment.operation_id, 'revision', version.revision,", "'operation_id', commitment.operation_id,", K_PR, K_OURS),
    'K05-no-reservation-id': (K, "jsonb_build_object('reservation_id', reservation.id,\n                                  'commitment_id'", "jsonb_build_object(\n                                  'commitment_id'", K_PR, K_OURS),
    'K06-no-commitment-id': (K, "'commitment_id', commitment.id, 'amount'", "'amount'", K_PR, K_OURS),
    'K07-no-amount': (K, ", 'amount', reservation.principal::text))", "))", K_PR, K_OURS),
    'K08-amount-as-number': (K, "'amount', reservation.principal::text", "'amount', reservation.principal", K_PR, K_OURS),
    'K09-any-commitment': (K, "WHERE commitment.id = commitment_id", "WHERE true", K_PR, K_OURS),
    'K10-text-compare-no-json-types': (K, CONTAINS, TEXT_COMPARE, K_PR, K_OURS),
    'K11-no-historical-audit': (K, HIST, "", K_PR, K_OURS),
    'K12-not-deferrable': (K, "AFTER INSERT ON primary_commitments\n                    DEFERRABLE INITIALLY DEFERRED FOR EACH ROW", "AFTER INSERT ON primary_commitments\n                    FOR EACH ROW", K_PR, K_OURS),
    'K13-initially-immediate': (K, "DEFERRABLE INITIALLY DEFERRED FOR EACH ROW EXECUTE FUNCTION require_primary_confirmation_receipt", "DEFERRABLE INITIALLY IMMEDIATE FOR EACH ROW EXECUTE FUNCTION require_primary_confirmation_receipt", K_PR, K_OURS),
    'K14-after-update-only': (K, "CONSTRAINT TRIGGER primary_commitment_receipt_bound AFTER INSERT", "CONSTRAINT TRIGGER primary_commitment_receipt_bound AFTER UPDATE", K_PR, K_OURS),
    'K15-no-down-guard': (K, DOWN_GUARD, "", K_PR, K_OURS),
    'K16-raise-is-notice': (K, "RAISE EXCEPTION 'Primary confirmation receipt must identify", "RAISE NOTICE 'Primary confirmation receipt must identify", K_PR, K_OURS),
    'K17-no-up-lock': (K, UP_LOCK, "            DB::unprepared(<<<'SQL'\n                CREATE OR REPLACE FUNCTION check", K_PR, K_OURS + D_OURS),
    # 154941 campaign lock (ea00d9a3)
    'O01-campaign-share': (O, "LOCK TABLE business_campaigns IN ACCESS SHARE MODE", "LOCK TABLE business_campaigns IN SHARE MODE", O_PR, O_OURS),
    'O02-campaign-access-exclusive': (O, "LOCK TABLE business_campaigns IN ACCESS SHARE MODE", "LOCK TABLE business_campaigns IN ACCESS EXCLUSIVE MODE", O_PR, O_OURS),
    'O03-original-combined-lock': (O, "            DB::statement('LOCK TABLE business_profiles, primary_reservations IN ACCESS EXCLUSIVE MODE');\n            DB::statement('LOCK TABLE business_campaigns IN ACCESS SHARE MODE');\n",
                                   "            DB::statement('LOCK TABLE business_profiles, business_campaigns, primary_reservations IN ACCESS EXCLUSIVE MODE');\n", O_PR, O_OURS),
    'O04-campaign-lock-dropped': (O, "            DB::statement('LOCK TABLE business_campaigns IN ACCESS SHARE MODE');\n", "", O_PR, O_OURS),
    'O05-campaign-row-share': (O, "LOCK TABLE business_campaigns IN ACCESS SHARE MODE", "LOCK TABLE business_campaigns IN ROW SHARE MODE", O_PR, O_OURS),
    # candidate fix for the 195022 install deadlock (expected: our probe PASSES, PR tests still pass)
    'FIX-195022-commitments-only': (K, "LOCK TABLE primary_reservations, primary_reservation_versions, primary_commitments IN ACCESS EXCLUSIVE MODE;\n                CREATE OR REPLACE",
                                    "LOCK TABLE primary_commitments IN SHARE ROW EXCLUSIVE MODE;\n                CREATE OR REPLACE", K_PR, D_OURS),
    'FIX2-195022-business-first': (K, "LOCK TABLE primary_reservations, primary_reservation_versions, primary_commitments IN ACCESS EXCLUSIVE MODE;\n                CREATE OR REPLACE",
                                   "LOCK TABLE business_profiles, primary_reservations, primary_reservation_versions, primary_commitments IN ACCESS EXCLUSIVE MODE;\n                CREATE OR REPLACE", K_PR, D_OURS),
}


def pest(tests, env):
    """Feature (RefreshDatabase) and Concurrency (DatabaseTruncation) files run in separate processes."""
    killed = False
    parts = []
    for group in ('Feature', 'Concurrency'):
        entries = [t for t in tests if t.startswith('tests/' + group + '/')]
        if not entries:
            continue
        for filt in sorted({e.split(' ', 1)[1] if ' ' in e else '' for e in entries}):
            files = [e.split(' ', 1)[0] for e in entries if (e.split(' ', 1)[1] if ' ' in e else '') == filt]
            args = ['php', '-d', 'memory_limit=2G', 'vendor/bin/pest', '--compact', *files] + ([filt] if filt else [])
            out = subprocess.run(args, env=env, capture_output=True, text=True)
            text = re.sub(r'\x1b\[[0-9;]*m', '', out.stdout + out.stderr)
            parts.append(group[0] + (filt and '(' + filt.split('=')[1] + ')') + ': ' + (' '.join(re.findall(r'Tests:\s+(.*)', text)).strip() or text[-300:].strip()))
            killed = killed or out.returncode != 0
    return killed, '; '.join(parts)


def run(name: str) -> str:
    path, old, new, pr_tests, our_tests = MUTATIONS[name]
    original = open(path).read()
    if original.count(old) != 1:
        return f'{name}: anchor found {original.count(old)} times; NOT APPLIED'
    open(path, 'w').write(original.replace(old, new))
    try:
        env = {**os.environ, 'DB_PORT': '5497', 'DB_HOST': '127.0.0.1', 'PAO_DISABLE': '1'}
        pr_killed, pr_summary = pest(pr_tests, env)
        ours_killed, ours_summary = pest(our_tests, env)
        return (f'{name}: PR={"KILLED" if pr_killed else "SURVIVED"} OURS={"KILLED" if ours_killed else "SURVIVED"}'
                f' [PR {pr_summary}] [OURS {ours_summary}]')
    finally:
        open(path, 'w').write(original)


if __name__ == '__main__':
    names = list(MUTATIONS) if sys.argv[1] == 'all' else sys.argv[1:]
    for name in names:
        print(run(name), flush=True)
