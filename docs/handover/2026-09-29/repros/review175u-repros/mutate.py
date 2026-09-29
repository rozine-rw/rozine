#!/usr/bin/env python3
"""Apply one mutation at a time in r175u, run the PR's tests, then (if they survive) the review repros; restore.
Usage (from r175u root): mutate.py all|<name>..."""
import os
import subprocess
import sys

CASH = 'app/Infrastructure/Wallet/EloquentPrimaryCommittedCash.php'
MIG = 'database/migrations/2026_09_28_195022_bind_primary_confirmation_receipts_to_commitments.php'
PR_CASH = ['tests/Feature/PrimaryCommittedCashTest.php', 'tests/Concurrency/PrimaryCommittedCashConcurrencyTest.php']
PR_MIG = ['tests/Concurrency/PrimaryCashMigrationLockTest.php', 'tests/Feature/PrimaryConfirmationReceiptTest.php',
          'tests/Feature/PrimaryReservationSchemaTest.php', 'tests/Feature/PrimaryCommandOutcomeTest.php']
OURS_CASH = ['tests/Feature/Review175uCashTest.php', 'tests/Concurrency/Review175uCashConcurrencyTest.php', 'tests/Feature/Review175rTest.php']
OURS_MIG = ['tests/Concurrency/Review175uMigrationLockTest.php', 'tests/Feature/Review175qReceiptAttackTest.php']

ISO = "if (DB::scalar(\"SELECT current_setting('transaction_isolation')\") !== 'read committed') {"
PRE = "if (LedgerEntry::query()->where('source_type', $source->type)->where('source_id', $source->id)->where('wallet_id', '!=', $wallet->walletId)->exists()) {\n            throw new WalletViolation('WALLET_POSTING_CONFLICT');\n        }\n"
LOCK = "        $walletQuery->lockForUpdate()->firstOrFail();\n"
EXISTS = "        if (! $walletQuery->exists()) {\n            throw new WalletViolation('WALLET_POSTING_WALLET_INVALID');\n        }\n"
ISOBLOCK = "        " + ISO + "\n            throw new WalletViolation('PRIMARY_CASH_ISOLATION_REQUIRED');\n        }\n"
UP_LOCK = "            DB::unprepared(<<<'SQL'\n                LOCK TABLE primary_commitments IN SHARE ROW EXCLUSIVE MODE;\n                CREATE OR REPLACE FUNCTION"
DOWN_LOCK = "            DB::unprepared(<<<'SQL'\n                LOCK TABLE primary_commitments IN SHARE ROW EXCLUSIVE MODE;\n                DO $$ BEGIN"
OLD = 'LOCK TABLE primary_reservations, primary_reservation_versions, primary_commitments IN ACCESS EXCLUSIVE MODE;'


def up_mode(mode):
    return (MIG, UP_LOCK, UP_LOCK.replace('SHARE ROW EXCLUSIVE MODE', mode))


def down_mode(mode):
    return (MIG, DOWN_LOCK, DOWN_LOCK.replace('SHARE ROW EXCLUSIVE MODE', mode))


M = {
    'C01-no-isolation-check': (CASH, ISOBLOCK, ''),
    'C02-refuse-only-serializable': (CASH, ISO, "if (DB::scalar(\"SELECT current_setting('transaction_isolation')\") === 'serializable') {"),
    'C03-read-default-isolation': (CASH, ISO, ISO.replace("'transaction_isolation'", "'default_transaction_isolation'")),
    'C04-isolation-check-after-lock': (CASH, ISOBLOCK + "        if ($source->type", "        if ($source->type"),  # re-inserted below
    'C05-no-ownership-precheck': (CASH, "        " + PRE, ''),
    'C06-precheck-after-lock': (CASH, "        " + PRE + LOCK, LOCK + "        " + PRE),
    'C07-precheck-no-source-type': (CASH, "if (LedgerEntry::query()->where('source_type', $source->type)->where('source_id', $source->id)->where('wallet_id', '!='",
                                    "if (LedgerEntry::query()->where('source_id', $source->id)->where('wallet_id', '!='"),
    'C08-no-wallet-exists-check': (CASH, EXISTS, ''),
    'C09-no-wallet-lock': (CASH, LOCK, "        $walletQuery->firstOrFail();\n"),
    'C10-exists-check-drops-party': (CASH, "$walletQuery = InvestorWallet::query()->whereKey($wallet->walletId)->where('party_id', $wallet->partyId)->where('currency', 'RWF');",
                                     "$walletQuery = InvestorWallet::query()->whereKey($wallet->walletId)->where('currency', 'RWF');"),
    'M01-no-up-lock': (MIG, UP_LOCK, UP_LOCK.replace('                LOCK TABLE primary_commitments IN SHARE ROW EXCLUSIVE MODE;\n', '')),
    'M02-up-share': up_mode('SHARE MODE'),
    'M03-up-exclusive': up_mode('EXCLUSIVE MODE'),
    'M04-up-access-exclusive': up_mode('ACCESS EXCLUSIVE MODE'),
    'M05-up-row-exclusive': up_mode('ROW EXCLUSIVE MODE'),
    'M06-up-old-list': (MIG, UP_LOCK, UP_LOCK.replace('LOCK TABLE primary_commitments IN SHARE ROW EXCLUSIVE MODE;', OLD)),
    'M07-no-down-lock': (MIG, DOWN_LOCK, DOWN_LOCK.replace('                LOCK TABLE primary_commitments IN SHARE ROW EXCLUSIVE MODE;\n', '')),
    'M08-down-share': down_mode('SHARE MODE'),
    'M09-down-row-exclusive': down_mode('ROW EXCLUSIVE MODE'),
    'M10-down-old-list': (MIG, DOWN_LOCK, DOWN_LOCK.replace('LOCK TABLE primary_commitments IN SHARE ROW EXCLUSIVE MODE;', OLD)),
    'M11-where-true': (MIG, 'WHERE commitment.id = commitment_id', 'WHERE true'),
    'M12-no-status': (MIG, "'status', 'completed', 'code'", "'code'"),
    'M13-up-lock-after-audit': (MIG, UP_LOCK, UP_LOCK.replace('                LOCK TABLE primary_commitments IN SHARE ROW EXCLUSIVE MODE;\n', '')),  # lock re-added before CREATE TRIGGER below
}


def run(tests):
    env = dict(os.environ, DB_PORT='5502', PAO_DISABLE='1')
    for t in tests:
        r = subprocess.run(['php', '-d', 'memory_limit=2G', 'vendor/bin/pest', '--no-tia', '--compact', t], env=env, capture_output=True, text=True, timeout=900)
        if r.returncode != 0:
            return 'KILLED', t.split('/')[-1]
    return 'SURVIVED', ''


def main():
    names = list(M) if sys.argv[1:] == ['all'] else sys.argv[1:]
    originals = {CASH: open(CASH).read(), MIG: open(MIG).read()}
    try:
        for name in names:
            f, old, new = M[name]
            src = originals[f]
            if src.count(old) != 1:
                print(f'{name}: PATTERN-MISSING', flush=True)
                continue
            mutated = src.replace(old, new)
            if name == 'C04-isolation-check-after-lock':
                mutated = mutated.replace(LOCK, LOCK + ISOBLOCK)
            if name == 'M13-up-lock-after-audit':
                mutated = mutated.replace('                CREATE CONSTRAINT TRIGGER', '                LOCK TABLE primary_commitments IN SHARE ROW EXCLUSIVE MODE;\n                CREATE CONSTRAINT TRIGGER')
            open(f, 'w').write(mutated)
            pr_tests, ours_tests = (PR_CASH, OURS_CASH) if f == CASH else (PR_MIG, OURS_MIG)
            pr, by = run(pr_tests)
            ours, by2 = run(ours_tests) if pr == 'SURVIVED' else ('-', '')
            print(f'{name}: PR={pr} {by} | repros={ours} {by2}', flush=True)
            open(f, 'w').write(originals[f])
    finally:
        for f, s in originals.items():
            open(f, 'w').write(s)


main()
