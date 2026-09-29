#!/usr/bin/env python3
"""Apply one mutation at a time to EloquentPrimaryCommittedCash in r175r, run the PR's tests, then the
review repros, restore the file. Usage (from r175r root): mutate.py all|<name>..."""
import os
import subprocess
import sys

F = 'app/Infrastructure/Wallet/EloquentPrimaryCommittedCash.php'
PR = ['tests/Feature/PrimaryCommittedCashTest.php', 'tests/Concurrency/PrimaryCommittedCashConcurrencyTest.php']
OURS = ['tests/Feature/Review175rTest.php', 'tests/Concurrency/Review175rConcurrencyTest.php']

M = {
    'M01-no-transaction-guard': ("if (DB::transactionLevel() < 1) {", "if (false) {"),
    'M02-no-source-type-guard': ("if ($source->type !== 'primary_reservation') {", "if (false) {"),
    'M03-no-wallet-lock': ("->where('currency', 'RWF')->lockForUpdate()->first()", "->where('currency', 'RWF')->first()"),
    'M04-no-party-filter': ("->where('party_id', $wallet->partyId)->where('currency', 'RWF')", "->where('currency', 'RWF')"),
    'M05-denylist-release-refund': ("if ($entries->pluck('kind')->all() !== ['primary_commit', 'primary_hold']) {",
                                    "if (! $entries->pluck('kind')->contains('primary_commit') || array_intersect(['primary_release', 'primary_refund'], $entries->pluck('kind')->all()) !== []) {"),
    'M06-subset-allowlist': ("if ($entries->pluck('kind')->all() !== ['primary_commit', 'primary_hold']) {",
                             "if (array_diff(['primary_commit', 'primary_hold'], $entries->pluck('kind')->all()) !== []) {"),
    'M07-no-entry-wallet': ("$entry->wallet_id !== $wallet->walletId || ", ""),
    'M08-no-entry-origin': (" || $entry->origin_operation_id !== $source->originOperationId", ""),
    'M09-no-entry-currency': (" || $entry->currency !== 'RWF'", ""),
    'M10-no-source-type-filter': ("->where('source_type', $source->type)->where('source_id'", "->where('source_id'"),
    'M11-no-kind-order': ("->where('source_id', $source->id)->orderBy('kind')->get()", "->where('source_id', $source->id)->get()"),
    'M12-no-line-amount': ("[['credit', $amount->amount(), $credit, $wallet->walletId, 'RWF'], ['debit', $amount->amount(), $debit, $wallet->walletId, 'RWF']]",
                           "[['credit', $lines[0][1] ?? '', $credit, $wallet->walletId, 'RWF'], ['debit', $lines[1][1] ?? '', $debit, $wallet->walletId, 'RWF']]"),
    'M13-no-line-wallet': ("[['credit', $amount->amount(), $credit, $wallet->walletId, 'RWF'], ['debit', $amount->amount(), $debit, $wallet->walletId, 'RWF']]",
                           "[['credit', $amount->amount(), $credit, $lines[0][3] ?? '', 'RWF'], ['debit', $amount->amount(), $debit, $lines[1][3] ?? '', 'RWF']]"),
    'M14-no-line-accounts': ("[['credit', $amount->amount(), $credit, $wallet->walletId, 'RWF'], ['debit', $amount->amount(), $debit, $wallet->walletId, 'RWF']]",
                             "[['credit', $amount->amount(), $lines[0][2] ?? '', $wallet->walletId, 'RWF'], ['debit', $amount->amount(), $lines[1][2] ?? '', $wallet->walletId, 'RWF']]"),
    'M15-skip-lines-check': ("if ($lines !== [['credit'", "if (false && $lines !== [['credit'"),
    'M16-committed-at-from-hold': ("$amount->amount(), $commit->created_at->toDateTimeImmutable());", "$amount->amount(), $hold->created_at->toDateTimeImmutable());"),
    'M17-swap-hold-commit-ids': ("return new CommittedCash($hold->id, $commit->id,", "return new CommittedCash($commit->id, $hold->id,"),
    'M18-only-check-first-entry': ("foreach ($entries as $entry) {", "foreach ($entries->take(1) as $entry) {"),
}


def run(tests):
    env = dict(os.environ, DB_PORT='5498', PAO_DISABLE='1')
    for t in tests:
        r = subprocess.run(['php', '-d', 'memory_limit=2G', 'vendor/bin/pest', '--compact', t], env=env, capture_output=True, text=True)
        if r.returncode != 0:
            return 'KILLED', t
    return 'SURVIVED', ''


def main():
    names = list(M) if sys.argv[1:] == ['all'] else sys.argv[1:]
    original = open(F).read()
    try:
        for name in names:
            old, new = M[name]
            if original.count(old) != 1:
                print(f'{name}: PATTERN-MISSING', flush=True)
                continue
            open(F, 'w').write(original.replace(old, new))
            pr, by = run(PR)
            ours, by2 = run(OURS) if pr == 'SURVIVED' else ('-', '')
            print(f'{name}: PR={pr} {by} | repros={ours} {by2}', flush=True)
    finally:
        open(F, 'w').write(original)


main()
