#!/usr/bin/env python3
"""Review #175 R1 (3bb74427..d753c794) and R2 (d753c794..494e12d1) mutation runner.
One exact-string mutation at a time, run only the PR's own tests, restore. Usage: mutate.py <worktree> <config> [ids...]"""
import os, re, subprocess, sys
W, CFG = sys.argv[1], sys.argv[2]
E = 'app/Infrastructure/Primary/EloquentCampaignReservationSummary.php'
V = 'app/Infrastructure/Wallet/EloquentPrimaryReturnedCash.php'
R = 'app/Infrastructure/Primary/EloquentPrimaryReservations.php'
T1 = ['tests/Feature/CampaignReservationSummaryTest.php', 'tests/Feature/BusinessCampaignProgressTest.php']
T2 = ['tests/Feature/PrimaryReturnedCashTest.php', 'tests/Feature/PrimaryReleaseTest.php', 'tests/Concurrency/PrimaryReturnedCashConcurrencyTest.php']
CRED = "accounts.kind = 'investor_available'\n                    AND accounts.wallet_id = entries.wallet_id AND accounts.currency = 'RWF'), 0) AS credited"
DEB = "accounts.kind = 'investor_committed'\n                    AND accounts.wallet_id = entries.wallet_id AND accounts.currency = 'RWF'), 0) AS debited"
LOCK = "        $walletQuery->lockForUpdate()->firstOrFail();\n"
OWN = "        if (LedgerEntry::query()->where('source_type', $source->type)->where('source_id', $source->id)->where('wallet_id', '!=', $wallet->walletId)->exists()) {\n            throw new WalletViolation('WALLET_POSTING_CONFLICT');\n        }\n"
READ = "        $entries = LedgerEntry::query()->where('source_type', $source->type)->where('source_id', $source->id)->orderBy('kind')->get();\n"
CALL = "        $returned = $this->returnedCash->requireReturned($wallet, $amount, $source);\n        if ($returned->returnKind !== 'primary_release' || $returned->returnEntryId !== $posting->entryId) {\n            throw new RuntimeException('RESERVATION_INTEGRITY_FAILED');\n        }\n"
M = [
 ('E1 refunds: drop kind filter', E, "->where('entries.kind', 'primary_refund')->where(", "->where(", T1),
 ('E2 refunds: drop source_type filter', E, "->where('entries.source_type', 'primary_reservation')", "", T1),
 ('E3 refunds: drop campaign scoping of the subquery', E, "            ->whereIn('entries.source_id', PrimaryReservationRecord::query()->where('business_campaign_id', $campaignId)->select('id'))\n", "", T1),
 ('E4 committed_principal keeps refunded', E, "WHERE versions.state = 'confirmed' AND refunds.id IS NULL), 0)::text AS committed_principal", "WHERE versions.state = 'confirmed'), 0)::text AS committed_principal", T1),
 ('E5 committed_units keeps refunded', E, "WHERE versions.state = 'confirmed' AND refunds.id IS NULL), 0)::text AS committed_units", "WHERE versions.state = 'confirmed'), 0)::text AS committed_units", T1),
 ('E6 investors keeps refunded', E, "WHERE versions.state = 'confirmed' AND refunds.id IS NULL) AS investors", "WHERE versions.state = 'confirmed') AS investors", T1),
 ('E7 returned_principal omits refunds', E, "OR refunds.id IS NOT NULL), 0)::text AS returned_principal", "), 0)::text AS returned_principal", T1),
 ('E8 returned_units omits refunds', E, "OR refunds.id IS NOT NULL), 0)::text AS returned_units", "), 0)::text AS returned_units", T1),
 ('E9 drop refund-on-non-confirmed check', E, "(versions.state IS DISTINCT FROM 'confirmed'\n                        OR refund_wallets", "(refund_wallets", T1),
 ('E10 drop Party/wallet check', E, "OR refund_wallets.party_id IS DISTINCT FROM reservations.party_id\n                        OR refunds.origin", "OR refunds.origin", T1),
 ('E11 drop originating-operation check', E, "OR refunds.origin_operation_id IS DISTINCT FROM reservations.origin_operation_id\n                        OR refunds.currency", "OR refunds.currency", T1),
 ('E12 drop entry currency check', E, "OR refunds.currency IS DISTINCT FROM 'RWF' OR refunds.line_count", "OR refunds.line_count", T1),
 ('E13 drop line_count check', E, "OR refunds.line_count <> 2\n", "\n", T1),
 ('E14 drop credited = principal', E, "OR refunds.credited <> reservations.principal OR refunds.debited", "OR refunds.debited", T1),
 ('E15 drop debited = principal', E, " OR refunds.debited <> reservations.principal)))", ")))", T1),
 ('E16 credited: any account kind', E, CRED, CRED.replace("accounts.kind = 'investor_available'\n                    AND ", ""), T1),
 ('E17 credited: any wallet account', E, CRED, CRED.replace("AND accounts.wallet_id = entries.wallet_id ", ""), T1),
 ('E18 credited: any account currency', E, CRED, CRED.replace(" AND accounts.currency = 'RWF'", ""), T1),
 ('E19 debited: any account kind', E, DEB, DEB.replace("accounts.kind = 'investor_committed'\n                    AND ", ""), T1),
 ('E20 debited: any wallet account', E, DEB, DEB.replace("AND accounts.wallet_id = entries.wallet_id ", ""), T1),
 ('E21 debited: any account currency', E, DEB, DEB.replace(" AND accounts.currency = 'RWF'", ""), T1),
 ('E22 line_count <> 2 -> < 2', E, "refunds.line_count <> 2", "refunds.line_count < 2", T1),
 ('E23 credited <> -> < (over-refund accepted)', E, "refunds.credited <> reservations.principal", "refunds.credited < reservations.principal", T1),
 ('E24 debited <> -> < (over-debit accepted)', E, "refunds.debited <> reservations.principal)))", "refunds.debited < reservations.principal)))", T1),
 ('V1 drop transaction requirement', V, "if (DB::transactionLevel() < 1) {", "if (false) {", T2),
 ('V2 drop isolation requirement', V, "!== 'read committed') {", "=== 'never') {", T2),
 ('V3 refuse only serializable (repeatable read accepted)', V, "!== 'read committed') {", "=== 'serializable') {", T2),
 ('V4 drop source type check', V, "if ($source->type !== 'primary_reservation') {", "if (false) {", T2),
 ('V5 wallet: drop party binding', V, "->where('party_id', $wallet->partyId)", "", T2),
 ('V6 wallet: drop RWF currency', V, "->where('currency', 'RWF');", ";", T2),
 ('V7 drop wallet existence check', V, "if (! $walletQuery->exists()) {", "if (false) {", T2),
 ('V8 drop pre-lock foreign-source check', V, OWN, "", T2),
 ('V9 drop the wallet gate (no FOR UPDATE)', V, LOCK, "", T2),
 ('V10 take the wallet gate before the foreign-source check', V, OWN + LOCK, LOCK + OWN, T2),
 ('V11 read movements before the wallet gate', V, LOCK + READ, READ + LOCK, T2),
 ('V12 drop the kind-set check', V, "if ($kinds !== ['primary_hold', 'primary_release'] && $kinds !== ['primary_commit', 'primary_hold', 'primary_refund']) {", "if (false) {", T2),
 ('V13 also accept hold + commit (unreturned)', V, "if ($kinds !== ['primary_hold', 'primary_release'] && ", "if ($kinds !== ['primary_hold', 'primary_release'] && $kinds !== ['primary_commit', 'primary_hold'] && ", T2),
 ('V14 per-entry: drop wallet check', V, "if ($entry->wallet_id !== $wallet->walletId || ", "if (", T2),
 ('V15 per-entry: drop origin check', V, " || $entry->origin_operation_id !== $source->originOperationId", "", T2),
 ('V16 per-entry: drop currency check', V, " || $entry->currency !== 'RWF') {", ") {", T2),
 ('V17 drop the line comparison', V, "if ($lines !== [['credit',", "if (false && $lines !== [['credit',", T2),
 ('V18 lines: amount not compared', V, "(string) $line->amount, $line->kind", "$amount->amount(), $line->kind", T2),
 ('V19 lines: account wallet not compared', V, "$line->kind, $line->wallet_id, $line->currency]", "$line->kind, $wallet->walletId, $line->currency]", T2),
 ('V20 lines: account currency not compared', V, "$line->wallet_id, $line->currency]", "$line->wallet_id, 'RWF']", T2),
 ('V21 hold lines expected as release lines', V, "'primary_hold' => ['investor_available', 'investor_held'],", "'primary_hold' => ['investor_held', 'investor_available'],", T2),
 ('V22 commit lines expected as refund lines', V, "'primary_commit' => ['investor_held', 'investor_committed'],", "'primary_commit' => ['investor_committed', 'investor_available'],", T2),
 ('V23 release lines expected as hold lines', V, "'primary_release' => ['investor_held', 'investor_available'],", "'primary_release' => ['investor_available', 'investor_held'],", T2),
 ('V24 refund lines expected as commit lines', V, "default => ['investor_committed', 'investor_available'],", "default => ['investor_held', 'investor_committed'],", T2),
 ('V25 return entry = first entry', V, "$returned = $entries->last();", "$returned = $entries->first();", T2),
 ('V26 returnedAt taken from the hold', V, "$amount->amount(), $returned->created_at->toDateTimeImmutable());", "$amount->amount(), $hold->created_at->toDateTimeImmutable());", T2),
 ('V27 commit id never reported', V, "$entries->firstWhere('kind', 'primary_commit')?->id", "null", T2),
 ('V28 hold id = return id', V, "new ReturnedCash($hold->id,", "new ReturnedCash($returned->id,", T2),
 ('R1 remove verification from releaseCash', R, CALL, "", T2),
 ('R2 verify but ignore the result', R, "if ($returned->returnKind !== 'primary_release' || $returned->returnEntryId !== $posting->entryId) {", "if (false) {", T2),
 ('R3 drop return-kind comparison', R, "$returned->returnKind !== 'primary_release' || ", "", T2),
 ('R4 drop return-entry comparison', R, " || $returned->returnEntryId !== $posting->entryId) {", ") {", T2),
 ('R5 verify before posting the release', R, "        $posting = $this->wallets->release($wallet, $amount, $source);\n        if ($previous->state === 'held' && $posting->replayed) {\n            throw new RuntimeException('RESERVATION_INTEGRITY_FAILED');\n        }\n\n" + CALL,
    "        $returned = $this->returnedCash->requireReturned($wallet, $amount, $source);\n        $posting = $this->wallets->release($wallet, $amount, $source);\n        if ($previous->state === 'held' && $posting->replayed) {\n            throw new RuntimeException('RESERVATION_INTEGRITY_FAILED');\n        }\n\n", T2),
 ('R6 verify only fresh releases (skip on replay)', R, "        $returned = $this->returnedCash->requireReturned($wallet, $amount, $source);\n        if ($returned->returnKind", "        if ($posting->replayed) {\n            return new ReservationRelease($root->id, $version->revision, $released, $posting);\n        }\n        $returned = $this->returnedCash->requireReturned($wallet, $amount, $source);\n        if ($returned->returnKind", T2),
]
only = sys.argv[3:]
OVERRIDE = os.environ.get('MUT_TESTS')
env = dict(os.environ, TMPDIR=os.path.join(W, '..', 'tmp'), DB_PORT='5547', PAO_DISABLE='1')
ansi = re.compile(r'\x1b\[[0-9;]*m')
for name, path, old, new, tests in M:
    if only and not any(name.split()[0] == o or (o.endswith('*') and name.startswith(o[:-1])) for o in only):
        continue
    fp = os.path.join(W, path); src = open(fp).read()
    if src.count(old) != 1:
        print((name, 'NOT-APPLIED (%d matches)' % src.count(old)), flush=True); continue
    open(fp, 'w').write(src.replace(old, new))
    try:
        p = subprocess.run(['php', '-d', 'memory_limit=2G', 'vendor/bin/pest', '-c', CFG, '--no-tia', '--compact', '--stop-on-failure', *(OVERRIDE.split('|') if OVERRIDE else tests)], cwd=W, env=env, capture_output=True, text=True, timeout=3000)
        out = ansi.sub('', p.stdout + p.stderr)
        summary = next((l.strip() for l in reversed(out.splitlines()) if l.strip().startswith('Tests:')), out.strip().splitlines()[-1] if out.strip() else '')
        failed = sorted({l.split('>')[-1].strip()[:90] for l in out.splitlines() if 'FAILED' in l})
        verdict = ('KILLED' if p.returncode else 'SURVIVED') + ' [' + summary + ']' + ((' by: ' + ' | '.join(failed[:3])) if failed else '')
    finally:
        open(fp, 'w').write(src)
    print((name, verdict), flush=True)
