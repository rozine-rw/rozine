#!/usr/bin/env python3
"""Apply one named mutation to 212446 in the r175v worktree, run the PR's tests (then our repros), restore the file.

Usage: mutate.py <name>...|all   (run from the r175v worktree root, cluster on 5503).
PrimaryOperationBindingTest and PrimaryExpiryOutcomeTest are excluded: they already fail at f1e99b16 (finding P2-1).
"""
import os
import re
import subprocess
import sys
import signal

signal.signal(signal.SIGTERM, lambda *a: sys.exit(143))
signal.signal(signal.SIGHUP, lambda *a: sys.exit(129))

W = 'database/migrations/2026_09_28_212446_bind_primary_confirmation_operations_to_purchases.php'
PR = ['tests/Feature/PrimaryConfirmationOperationTest.php', 'tests/Feature/PrimaryConfirmationReceiptTest.php',
      'tests/Feature/PrimaryCommandOutcomeTest.php', 'tests/Feature/PrimaryReservationSchemaTest.php',
      'tests/Feature/PrimaryConfirmationTest.php', 'tests/Feature/PrimaryCheckoutTest.php', 'tests/Feature/PrimaryReleaseTest.php',
      'tests/Feature/PostgreSqlConfigurationTest.php']
PR_LOCK = PR + ['tests/Concurrency/PrimaryCashMigrationLockTest.php', 'tests/Concurrency/PrimaryOrdinalMigrationLockTest.php']
OURS = ['tests/Feature/Review175vOperationAttackTest.php']
OURS_LOCK = OURS + ['tests/Concurrency/Review175vInstallLockTest.php']

HIST = """                DO $$
                DECLARE receipt_id varchar;
                BEGIN
                    FOR receipt_id IN SELECT id FROM command_operations WHERE result->>'code' = 'RESERVATION_CONFIRMED' LOOP
                        PERFORM check_primary_confirmation_operation(receipt_id);
                    END LOOP;
                END;
                $$;
"""
DOWN_GUARD = """                DO $$ BEGIN
                    IF EXISTS (SELECT 1 FROM command_operations WHERE result->>'code' = 'RESERVATION_CONFIRMED') THEN
                        RAISE EXCEPTION 'Retained confirmation operations require a forward migration' USING ERRCODE = '23514';
                    END IF;
                END; $$;
"""
UP_LOCK = "LOCK TABLE command_operations IN SHARE ROW EXCLUSIVE MODE;\n                CREATE OR REPLACE"

MUTATIONS = {
    'W01-no-command': (W, "                              AND operation.command = 'primary.confirm'\n", "", PR, OURS),
    'W02-no-target-type': (W, "AND operation.target_type = 'primary_reservation' AND operation.target_id = reservation.id", "AND operation.target_id = reservation.id", PR, OURS),
    'W03-no-target-id': (W, "AND operation.target_type = 'primary_reservation' AND operation.target_id = reservation.id", "AND operation.target_type = 'primary_reservation'", PR, OURS),
    'W04-no-confirmed-state': (W, "AND version.state = 'confirmed' AND version.operation_id", "AND version.operation_id", PR, OURS),
    'W05-no-version-operation': (W, "AND version.state = 'confirmed' AND version.operation_id = operation.id", "AND version.state = 'confirmed'", PR, OURS),
    'W06-no-version-reservation': (W, "                              AND version.primary_reservation_id = reservation.id\n", "", PR, OURS),
    'W07-no-status': (W, "'status', 'completed', 'code', 'RESERVATION_CONFIRMED',\n", "'code', 'RESERVATION_CONFIRMED',\n", PR, OURS),
    'W08-no-code-in-body': (W, "'status', 'completed', 'code', 'RESERVATION_CONFIRMED',\n", "'status', 'completed',\n", PR, OURS),
    'W09-no-operation-id': (W, "'operation_id', operation.id, 'revision', version.revision,", "'revision', version.revision,", PR, OURS),
    'W10-no-revision': (W, "'operation_id', operation.id, 'revision', version.revision,", "'operation_id', operation.id,", PR, OURS),
    'W11-revision-as-text': (W, "'revision', version.revision,", "'revision', version.revision::text,", PR, OURS),
    'W12-no-reservation-id': (W, "jsonb_build_object('reservation_id', reservation.id,\n", "jsonb_build_object(\n", PR, OURS),
    'W13-no-commitment-id': (W, "'commitment_id', commitment.id, 'amount'", "'amount'", PR, OURS),
    'W14-no-amount': (W, ", 'amount', reservation.principal::text))", "))", PR, OURS),
    'W15-amount-as-number': (W, "'amount', reservation.principal::text", "'amount', reservation.principal", PR, OURS),
    'W16-no-historical-audit': (W, HIST, "", PR, OURS),
    'W17-not-deferrable': (W, "AFTER INSERT ON command_operations\n                    DEFERRABLE INITIALLY DEFERRED FOR EACH ROW", "AFTER INSERT ON command_operations\n                    FOR EACH ROW", PR, OURS),
    'W18-initially-immediate': (W, "DEFERRABLE INITIALLY DEFERRED FOR EACH ROW WHEN", "DEFERRABLE INITIALLY IMMEDIATE FOR EACH ROW WHEN", PR, OURS),
    'W19-no-when-clause': (W, " WHEN (NEW.result->>'code' = 'RESERVATION_CONFIRMED')", "", PR, OURS),
    'W20-when-on-command': (W, "WHEN (NEW.result->>'code' = 'RESERVATION_CONFIRMED')", "WHEN (NEW.command = 'primary.confirm' AND NEW.result->>'code' = 'RESERVATION_CONFIRMED')", PR, OURS),
    'W21-no-down-guard': (W, DOWN_GUARD, "", PR, OURS),
    'W22-raise-notice': (W, "RAISE EXCEPTION 'Confirmation operation requires", "RAISE NOTICE 'Confirmation operation requires", PR, OURS),
    'W23-audit-wrong-code': (W, "FOR receipt_id IN SELECT id FROM command_operations WHERE result->>'code' = 'RESERVATION_CONFIRMED' LOOP",
                             "FOR receipt_id IN SELECT id FROM command_operations WHERE command = 'primary.reserve' LOOP", PR, OURS),
    'W24-no-lock': (W, UP_LOCK, "CREATE OR REPLACE", PR_LOCK, OURS_LOCK),
    'W25-lock-access-exclusive': (W, "LOCK TABLE command_operations IN SHARE ROW EXCLUSIVE MODE;\n                CREATE", "LOCK TABLE command_operations IN ACCESS EXCLUSIVE MODE;\n                CREATE", PR_LOCK, OURS_LOCK),
    'W26-lock-share': (W, "LOCK TABLE command_operations IN SHARE ROW EXCLUSIVE MODE;\n                CREATE", "LOCK TABLE command_operations IN SHARE MODE;\n                CREATE", PR_LOCK, OURS_LOCK),
    'W27-lock-also-reservations': (W, "LOCK TABLE command_operations IN SHARE ROW EXCLUSIVE MODE;\n                CREATE",
                                   "LOCK TABLE command_operations IN SHARE ROW EXCLUSIVE MODE;\n                LOCK TABLE primary_reservations IN SHARE MODE;\n                CREATE", PR_LOCK, OURS_LOCK),
    'W28-after-update': (W, "CONSTRAINT TRIGGER primary_confirmation_operation_bound AFTER INSERT", "CONSTRAINT TRIGGER primary_confirmation_operation_bound AFTER UPDATE", PR, OURS),
}


def pest(tests, env):
    killed = False
    parts = []
    for group in ('Feature', 'Concurrency'):
        files = [t for t in tests if t.startswith('tests/' + group + '/')]
        if not files:
            continue
        out = subprocess.run(['php', '-d', 'memory_limit=2G', 'vendor/bin/pest', '--no-tia', '--compact', '--stop-on-failure', *files], env=env, capture_output=True, text=True)
        text = re.sub(r'\x1b\[[0-9;]*m', '', out.stdout + out.stderr)
        parts.append(group[0] + ': ' + (' '.join(re.findall(r'Tests:\s+(.*)', text)).strip() or text[-300:].strip()))
        killed = killed or out.returncode != 0
    return killed, '; '.join(parts)


def run(name: str) -> str:
    path, old, new, pr_tests, our_tests = MUTATIONS[name]
    original = open(path).read()
    if original.count(old) != 1:
        return f'{name}: anchor found {original.count(old)} times; NOT APPLIED'
    open(path, 'w').write(original.replace(old, new))
    try:
        env = {**os.environ, 'DB_PORT': '5503', 'DB_HOST': '127.0.0.1', 'PAO_DISABLE': '1'}
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
