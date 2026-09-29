#!/usr/bin/env python3
"""Apply one named mutation to the r175k worktree, run the PR's own tests (and optionally our repros), restore.

Usage: mutate.py <name|all> [--with-repros]   (run from the r175k worktree root; DB on 127.0.0.1:5490)
"""
import os
import subprocess
import sys

RES = 'app/Infrastructure/Primary/EloquentPrimaryReservations.php'
CHK = 'app/Infrastructure/Primary/EloquentPrimaryCheckout.php'
DOM = 'app/Domain/Primary/PrimaryReservation.php'
MIG = 'database/migrations/2026_09_28_143756_create_primary_reservation_records.php'

PR_TESTS = ['tests/Feature/PrimaryConfirmationTest.php', 'tests/Concurrency/PrimaryConfirmationConcurrencyTest.php', 'tests/Unit/PrimaryReservationTest.php']
REPROS = ['tests/Feature/Review175kConfirmationTest.php', 'tests/Concurrency/Review175kConfirmationRaceTest.php']

M = {
    'M01-no-expected-revision': (RES, "if ($previous->revision !== $expectedRevision) {", "if (false) {"),
    'M02-no-held-state-guard': (RES, "                if ($reservation->state !== 'held') {\n                    throw new CommandRejection('RESERVATION_NOT_HELD', 409, $previous->revision);",
                                "                if (false) {\n                    throw new CommandRejection('RESERVATION_NOT_HELD', 409, $previous->revision);"),
    'M03-no-pre-admission-deadline': (RES, "                $reservation->window->requireOpen(now('UTC')->toDateTimeImmutable());\n", ""),
    'M04-no-campaign-terms-check': (RES, "                if ($terms->policyVersion !== $campaign['policy_version'] || $terms->ratePercent !== $campaign['rate_pct'] || $terms->termMonths !== $campaign['term_months']) {\n                    throw new PrimaryViolation('INVALID_PRIMARY_TERMS');\n                }\n                $terms->requireRights",
                                    "                if (false) {\n                    throw new PrimaryViolation('INVALID_PRIMARY_TERMS');\n                }\n                $terms->requireRights"),
    'M05-no-requireRights-at-confirm': (RES, "                $terms->requireRights($reservation->rights);\n                $at =", "                $at ="),
    'M06-requote-on-version-only': (RES, "if ($terms->disclosureSha256 !== $reservation->terms->disclosureSha256) {", "if ($terms->disclosureVersion !== $reservation->terms->disclosureVersion) {"),
    'M07-never-requote': (RES, "if ($terms->disclosureSha256 !== $reservation->terms->disclosureSha256) {", "if (false) {"),
    'M08-ignore-caller-ack': (RES, "$reservation->confirm($at, $terms, $disclosureVersion, $disclosureSha256);", "$reservation->confirm($at, $terms, $terms->disclosureVersion, $terms->disclosureSha256);"),
    'M09-no-cash-commit': (RES, "                $posting = $this->wallets->commit($this->wallets->lockForParty($partyId), WalletMoney::of($root->principal),\n                    new PostingSource('primary_reservation', $root->id, $root->origin_operation_id));",
                           "                $posting = null;"),
    'M10-root-not-for-update': (RES, "->whereKey($reservationId)->lockForUpdate()->first() ?? throw new CommandRejection('RESERVATION_NOT_FOUND', 404);",
                                "->whereKey($reservationId)->first() ?? throw new CommandRejection('RESERVATION_NOT_FOUND', 404);"),
    'M11-root-no-party-scope': (RES, "$root = PrimaryReservationRecord::query()->where('business_campaign_id', $campaign['id'])->where('party_id', $partyId)",
                                "$root = PrimaryReservationRecord::query()->where('business_campaign_id', $campaign['id'])"),
    'M12-no-outer-txn-guard': (RES, "        if (DB::transactionLevel() === 0) {\n            throw new CommandRejection('PRIMARY_TRANSACTION_REQUIRED');\n        }\n        try {\n            return DB::transaction(function () use ($campaignId, $reservationId",
                               "        try {\n            return DB::transaction(function () use ($campaignId, $reservationId"),
    'M13-replay-no-state-match': (RES, "if ($version->state !== $reservation->state || $version->revision", "if ($version->revision"),
    'M14-replay-no-digest': (RES, "                    || ! hash_equals($version->sha256, $this->digest($payload))\n", ""),
    'M15-replay-no-payload-equality': (RES, "\n                    || $this->json->encode($payload) !== $this->json->encode($this->versionPayload($root, $reservation, $version->operation_id, $previous, $at))) {", ") {"),
    'M16-replay-no-chronology': (RES, "if ($reservation->state !== 'held' || $at < $previous->created_at->toDateTimeImmutable()) {", "if ($reservation->state !== 'held') {"),
    'M17-fp-no-expected-revision': (CHK, "'expected_revision' => $expectedRevision,\n", "\n"),
    'M18-fp-no-disclosure-version': (CHK, "                        'disclosure_version' => $disclosureVersion, 'disclosure_sha256' => $disclosureSha256],", "                        'disclosure_sha256' => $disclosureSha256],"),
    'M19-fp-no-disclosure-sha': (CHK, "                        'disclosure_version' => $disclosureVersion, 'disclosure_sha256' => $disclosureSha256],", "                        'disclosure_version' => $disclosureVersion],"),
    'M20-target-no-party-scope': (CHK, "->where('business_campaign_id', $campaignId)->where('party_id', $partyId)->first()", "->where('business_campaign_id', $campaignId)->first()"),
    'M21-target-no-campaign-scope': (CHK, "->whereKey($reservationId)->where('business_campaign_id', $campaignId)->where('party_id', $partyId)->first()", "->whereKey($reservationId)->where('party_id', $partyId)->first()"),
    'M22-lookup-no-target-check': (CHK, "if ($type !== 'primary_reservation' || $id !== $root->id) {", "if ($type !== 'primary_reservation') {"),
    'M23-dom-confirm-no-ack': (DOM, "        $this->terms->requireAcknowledged($currentTerms, $acknowledgedVersion, $acknowledgedSha256);\n", ""),
    'M24-dom-confirm-no-deadline': (DOM, "    public function confirm(DateTimeImmutable $at, PrimaryTerms $currentTerms, string $acknowledgedVersion, string $acknowledgedSha256): self\n    {\n        $this->window->requireOpen($at);\n",
                                    "    public function confirm(DateTimeImmutable $at, PrimaryTerms $currentTerms, string $acknowledgedVersion, string $acknowledgedSha256): self\n    {\n"),
    'M25-dom-requote-no-deadline': (DOM, "    public function requote(DateTimeImmutable $at, PrimaryTerms $terms): self\n    {\n        $this->window->requireOpen($at);\n",
                                    "    public function requote(DateTimeImmutable $at, PrimaryTerms $terms): self\n    {\n"),
    'M26-db-confirmed-needs-no-commitment': (MIG, "ELSIF NEW.state = 'confirmed' AND NOT EXISTS (SELECT 1 FROM primary_commitments WHERE primary_reservation_version_id = NEW.id) THEN",
                                             "ELSIF false THEN"),
    'M27-db-commitment-any-state': (MIG, "OR confirmation.state <> 'confirmed'\n", "\n"),
}


def run(tests):
    env = {**os.environ, 'DB_PORT': '5490', 'DB_HOST': '127.0.0.1', 'PAO_DISABLE': '1'}
    proc = subprocess.run(['php', '-d', 'memory_limit=2G', 'vendor/bin/pest', '--compact', *tests], env=env, capture_output=True, text=True)
    fails = [l.strip()[:150] for l in (proc.stdout + proc.stderr).splitlines() if 'FAILED' in l or '⨯' in l]
    return proc.returncode, fails


def one(name, with_repros):
    path, old, new = M[name]
    original = open(path).read()
    if original.count(old) != 1:
        print(f'{name}: anchor found {original.count(old)} times; NOT APPLIED', flush=True)
        return
    open(path, 'w').write(original.replace(old, new))
    try:
        code, fails = run(PR_TESTS)
        verdict = 'KILLED' if code != 0 else 'SURVIVED'
        extra = ''
        if code == 0 and with_repros:
            rcode, rfails = run(REPROS)
            extra = f' | repros: {"KILLED" if any("control" in f for f in rfails) else "survived"} {rfails[:4]}'
        print(f'{name}: {verdict} {fails[:3]}{extra}', flush=True)
    finally:
        open(path, 'w').write(original)


if __name__ == '__main__':
    names = list(M) if sys.argv[1] == 'all' else [sys.argv[1]]
    for n in names:
        one(n, '--with-repros' in sys.argv)
