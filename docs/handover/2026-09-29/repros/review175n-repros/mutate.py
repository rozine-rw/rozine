#!/usr/bin/env python3
"""Apply one named mutation to the r175n worktree, run the shipped tests, then our probes if it survives; restore.

Usage: mutate.py <name>|all   (run from the r175n worktree root).
"""
import os
import re
import subprocess
import sys

CK = 'app/Infrastructure/Primary/EloquentPrimaryCheckout.php'
RS = 'app/Infrastructure/Primary/EloquentPrimaryReservations.php'
SRC = 'app/Infrastructure/Business/EloquentPrimaryCampaignSource.php'
DOM = 'app/Domain/Primary/PrimaryReservation.php'

T = ['tests/Feature/PrimaryReleaseTest.php', 'tests/Feature/PrimaryConfirmationTest.php', 'tests/Feature/PrimaryCampaignSourceTest.php',
     'tests/Feature/PrimaryCheckoutTest.php', 'tests/Feature/BusinessCampaignClosureTest.php', 'tests/Feature/PrimaryExpiryOutcomeTest.php',
     'tests/Unit/PrimaryReservationTest.php',
     'tests/Concurrency/PrimaryReleaseConcurrencyTest.php', 'tests/Concurrency/PrimaryCheckoutLockTest.php',
     'tests/Concurrency/PrimaryCampaignSourceLockTest.php', 'tests/Concurrency/PrimaryConfirmationConcurrencyTest.php']
MINE = ['tests/Feature/Review175nReleaseTest.php', 'tests/Concurrency/Review175nReleaseRaceTest.php']

REL_OPEN = "ReservationRelease {\n                $campaign = $this->campaigns->lockRetained($campaignId);\n"
EXP_OPEN = "?ReservationRelease {\n            $campaign = $this->campaigns->lockRetained($campaignId);\n"

MUTATIONS = {
    'M01-no-expiry-after-rejection': (CK, "            $this->reservations->expire($root->business_campaign_id, $root->id, $result['operation_id']);\n", ""),
    'M02-expire-on-any-rejection': (CK, "$result['status'] === 'rejected' && $result['code'] === 'RESERVATION_EXPIRED'", "$result['status'] === 'rejected'"),
    'M03-expire-code-only (equivalent)': (CK, "$result['status'] === 'rejected' && $result['code'] === 'RESERVATION_EXPIRED'", "$result['code'] === 'RESERVATION_EXPIRED'"),
    'M04-actor-expiry-as-system': (CK, "$root->id, $result['operation_id']);", "$root->id);"),
    'M05-release-path-no-expiry': (CK, "                $this->expireRejected($root, $result);\n\n                return $result;\n            });\n    }\n\n    public function findRelease", "                return $result;\n            });\n    }\n\n    public function findRelease"),
    'M06-confirm-path-no-expiry': (CK, "                $this->expireRejected($root, $result);\n\n                return $result;\n            });\n    }\n\n    public function findConfirmation", "                return $result;\n            });\n    }\n\n    public function findConfirmation"),
    'M07-system-expire-before-journal': (CK, "                $root = $this->reservationTarget($campaignId, $reservationId, $partyId);\n                $result = $this->journal->execute('party:'.$partyId, $userId, 'primary.release'",
                                          "                $root = $this->reservationTarget($campaignId, $reservationId, $partyId);\n                $this->reservations->expire($root->business_campaign_id, $root->id);\n                $result = $this->journal->execute('party:'.$partyId, $userId, 'primary.release'"),
    'M08-cash-inside-rejected-savepoint-only': (CK, "            $this->reservations->expire($root->business_campaign_id, $root->id, $result['operation_id']);\n", ""),
    'M09-release-open-only': (RS, REL_OPEN, REL_OPEN.replace('lockRetained', 'lock')),
    'M10-expire-open-only': (RS, EXP_OPEN, EXP_OPEN.replace('lockRetained', 'lock')),
    'M11-confirm-first-lock-open-only': (RS, "ReservationConfirmation {\n                $campaign = $this->campaigns->lockRetained($campaignId);", "ReservationConfirmation {\n                $campaign = $this->campaigns->lock($campaignId);"),
    'M12-confirm-no-open-check': (RS, "requireOpen(now('UTC')->toDateTimeImmutable());\n                $this->campaigns->lock($campaignId);\n", "requireOpen(now('UTC')->toDateTimeImmutable());\n"),
    'M13-requote-before-open-check': (RS, "requireOpen(now('UTC')->toDateTimeImmutable());\n                $this->campaigns->lock($campaignId);\n", "requireOpen(now('UTC')->toDateTimeImmutable());\n"),
    'M14-source-open-check-off': (SRC, "if ($requireOpen && (", "if (false && ("),
    'M15-retained-requires-open': (SRC, "return $this->lockedInput($campaignId, false);", "return $this->lockedInput($campaignId, true);"),
    'M16-no-closure-fallback': (SRC, "$exposure['principal'] ?? ($closure['principal_released'] ?? null)", "$exposure['principal'] ?? null"),
    'M17-fallback-without-closure': (SRC, "$exposure['principal'] ?? ($closure['principal_released'] ?? null)", "$exposure['principal'] ?? $campaign->principal"),
    'M18-release-no-revision-check': (RS, "if ($previous->revision !== $expectedRevision) {\n                    throw new CommandRejection('VERSION_CONFLICT', 409, $previous->revision);\n                }\n                $at = now",
                                      "if (false) {\n                    throw new CommandRejection('VERSION_CONFLICT', 409, $previous->revision);\n                }\n                $at = now"),
    'M19-expired-treated-as-released': (RS, "if ($released->state === 'expired') {", "if (false) {"),
    'M20-expired-receipt-revision-unchanged': (RS, "$previous->revision + ($reservation->state === 'held' ? 1 : 0)", "$previous->revision"),
    'M21-release-no-party-filter': (RS, REL_OPEN + "                $root = PrimaryReservationRecord::query()->where('business_campaign_id', $campaign['id'])->where('party_id', $partyId)",
                                    REL_OPEN + "                $root = PrimaryReservationRecord::query()->where('business_campaign_id', $campaign['id'])"),
    'M22-expire-before-due': (RS, "if ($reservation->state !== 'held' || ! $reservation->window->isExpired($at)) {", "if ($reservation->state !== 'held') {"),
    'M23-expire-any-state': (RS, "if ($reservation->state !== 'held' || ! $reservation->window->isExpired($at)) {", "if (! $reservation->window->isExpired($at)) {"),
    'M24-always-append-terminal': (RS, "$version = $previous->state === 'held'\n            ? $this->appendVersion($root, $released, $operationId, $previous, $at) : $previous;", "$version = $this->appendVersion($root, $released, $operationId, $previous, $at);"),
    'M25-release-wrong-origin': (RS, "new PostingSource('primary_reservation', $root->id, $root->origin_operation_id));\n\n        return new ReservationRelease", "new PostingSource('primary_reservation', $root->id, $operationId ?? $root->origin_operation_id));\n\n        return new ReservationRelease"),
    'M26-domain-confirmed-release-noop': (DOM, "        if ($this->state === 'confirmed') {\n            throw new PrimaryViolation('RESERVATION_NOT_HELD');\n        }\n        if ($this->state !== 'held') {", "        if ($this->state !== 'held') {"),
    'M27-no-business-lock-first': (CK, "            $this->campaigns->lockBusiness($campaignId);\n\n            return $this->authority->handle", "            return $this->authority->handle"),
    'M28-findRelease-no-target-check': (CK, "'primary.release', $requestId,\n                function (string $type, string $id) use ($root): void {\n                    if ($type !== 'primary_reservation' || $id !== $root->id) {",
                                        "'primary.release', $requestId,\n                function (string $type, string $id) use ($root): void {\n                    if (false) {"),
    'M29-expire-no-transaction-guard': (RS, "?string $operationId = null): ?ReservationRelease\n    {\n        if (DB::transactionLevel() === 0) {", "?string $operationId = null): ?ReservationRelease\n    {\n        if (false) {"),
    'M30-release-no-transaction-guard': (RS, "int $expectedRevision): ReservationRelease\n    {\n        if (DB::transactionLevel() === 0) {", "int $expectedRevision): ReservationRelease\n    {\n        if (false) {"),
    'M31-confirm-no-early-expiry-check': (RS, "                $reservation->window->requireOpen(now('UTC')->toDateTimeImmutable());\n                $this->campaigns->lock($campaignId);\n", "                $this->campaigns->lock($campaignId);\n"),
    'M32-wallet-before-root-on-release': (RS, REL_OPEN, REL_OPEN + "                $this->wallets->lockForParty($partyId);\n"),
    'M33-wallet-before-root-on-expire': (RS, EXP_OPEN, EXP_OPEN + "                $this->wallets->lockForParty(PrimaryReservationRecord::query()->whereKey($reservationId)->value('party_id'));\n"),
    'M34-release-skips-replay-check': (RS, "        $posting = $this->wallets->release($this->wallets->lockForParty($root->party_id), WalletMoney::of($root->principal),", "        $posting = $this->wallets->release($this->wallets->lockForParty($root->party_id), WalletMoney::of((string) ($root->principal - 5000)),"),
}
# M13 differs from M12: re-insert the open check only before the confirm (so requote on a closed campaign passes).
MUTATIONS['M13-requote-before-open-check'] = (RS, "requireOpen(now('UTC')->toDateTimeImmutable());\n                $this->campaigns->lock($campaignId);\n                $terms = $admit(",
    "requireOpen(now('UTC')->toDateTimeImmutable());\n                $terms = $admit(")
M13_EXTRA = ("                $confirmed = $reservation->confirm($at, $terms, $disclosureVersion, $disclosureSha256);\n",
             "                $this->campaigns->lock($campaignId);\n                $confirmed = $reservation->confirm($at, $terms, $disclosureVersion, $disclosureSha256);\n")
# M08: move the cash return inside the rejected savepoint instead of after it.
M08_EXTRA = ("                if ($released->state === 'expired') {\n                    throw",
             "                if ($released->state === 'expired') {\n                    $this->releaseCash($root, $released, $previous, $operationId, $at);\n                    throw")


def pest(tests, env):
    killed = False
    parts = []
    for group in ('Unit', 'Feature', 'Concurrency'):
        files = [t for t in tests if t.startswith('tests/' + group + '/')]
        if not files:
            continue
        out = subprocess.run(['php', '-d', 'memory_limit=2G', 'vendor/bin/pest', '-c', 'phpunit.pgsql.xml', '--no-tia', *files], env=env, capture_output=True, text=True)
        text = re.sub(r'\x1b\[[0-9;]*m', '', out.stdout + out.stderr)
        m = re.search(r'"tests":(\d+),"passed":(\d+)', text)
        t = re.findall(r'Tests:\s+(.*)', text)
        parts.append(group[0] + ':' + (f'{m.group(2)}/{m.group(1)}' if m else (t[-1].strip() if t else text[-160:].strip().replace('\n', ' '))))
        killed = killed or out.returncode != 0
    return ('KILLED' if killed else 'SURVIVED'), ' '.join(parts)


def run(name: str) -> str:
    path, old, new = MUTATIONS[name]
    original = open(path).read()
    if original.count(old) != 1:
        return f'{name}: anchor found {original.count(old)} times; NOT APPLIED'
    mutated = original.replace(old, new)
    if name.startswith('M13'):
        assert mutated.count(M13_EXTRA[0]) == 1
        mutated = mutated.replace(*M13_EXTRA)
    if name.startswith('M08'):
        rs = open(RS).read()
        assert rs.count(M08_EXTRA[0]) == 1
        open(RS, 'w').write(rs.replace(*M08_EXTRA))
    open(path, 'w').write(mutated)
    try:
        env = {**os.environ, 'DB_PORT': '5494', 'DB_HOST': '127.0.0.1', 'TMPDIR': os.environ.get('TMPDIR', '/tmp/')}
        verdict, summary = pest(T, env)
        extra = ''
        if verdict == 'SURVIVED':
            v2, s2 = pest(MINE, env)
            extra = ' | review probes: ' + ('KILLED ' if v2 == 'KILLED' else 'survived ') + s2
        return f'{name}: {verdict} [{summary}]{extra}'
    finally:
        open(path, 'w').write(original)
        if name.startswith('M08'):
            open(RS, 'w').write(rs)


if __name__ == '__main__':
    names = list(MUTATIONS) if sys.argv[1] == 'all' else sys.argv[1:]
    for name in names:
        print(run(name), flush=True)
