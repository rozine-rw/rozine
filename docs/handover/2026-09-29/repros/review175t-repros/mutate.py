#!/usr/bin/env python3
"""Apply one named mutation to the r175t worktree, run the shipped tests, then our probes if it survives; restore.

Usage: mutate.py <name>...|all   (run from the r175t worktree root).
"""
import os
import re
import subprocess
import sys

SM = 'app/Infrastructure/Primary/EloquentCampaignReservationSummary.php'
RS = 'app/Infrastructure/Primary/EloquentPrimaryReservations.php'

SUMMARY_T = ['tests/Feature/CampaignReservationSummaryTest.php']
SUMMARY_MINE = ['tests/Feature/Review175tSummaryTest.php', 'tests/Concurrency/Review175tSummaryConcurrencyTest.php']
RELEASE_T = ['tests/Feature/PrimaryReleaseTest.php', 'tests/Feature/PrimaryConfirmationTest.php', 'tests/Feature/PrimaryCampaignSourceTest.php',
             'tests/Feature/PrimaryCheckoutTest.php', 'tests/Feature/BusinessCampaignClosureTest.php', 'tests/Feature/PrimaryExpiryOutcomeTest.php',
             'tests/Unit/PrimaryReservationTest.php',
             'tests/Concurrency/PrimaryReleaseConcurrencyTest.php', 'tests/Concurrency/PrimaryCheckoutLockTest.php',
             'tests/Concurrency/PrimaryCampaignSourceLockTest.php', 'tests/Concurrency/PrimaryConfirmationConcurrencyTest.php']
RELEASE_MINE = ['tests/Feature/Review175nReleaseTest.php', 'tests/Feature/Review175tReleaseTest.php']

EXP_OPEN = "?ReservationRelease {\n            $campaign = $this->campaigns->lockRetained($campaignId);\n"
REL_OPEN = "ReservationRelease {\n                $campaign = $this->campaigns->lockRetained($campaignId);\n"
LOCK = "                    $reservation->window->requireOpen(now('UTC')->toDateTimeImmutable());\n                    $this->campaigns->lock($campaignId);\n"
GUARD = "        if ($previous->state === 'held' && $posting->replayed) {\n            throw new RuntimeException('RESERVATION_INTEGRITY_FAILED');\n        }\n"
CATCH = "                if ($exception->reasonCode === 'RESERVATION_EXPIRED') {\n                        throw new CommandRejection('RESERVATION_EXPIRED', 409, $previous->revision + 1,\n                            data: ['reservation_id' => $root->id, 'amount' => $root->principal]);"

M = {
    # --- increment 1: summary ---
    'S01-oldest-version': (SM, "MAX(revision)", "MIN(revision)", 's'),
    'S02-live-includes-deadline': (SM, "versions.state = 'held' AND reservations.expires_at > ?), 0)::text AS held_principal", "versions.state = 'held' AND reservations.expires_at >= ?), 0)::text AS held_principal", 's'),
    'S03-overdue-excludes-deadline': (SM, "reservations.expires_at <= ?), 0)::text AS expired_hold_units", "reservations.expires_at < ?), 0)::text AS expired_hold_units", 's'),
    'S04-returned-released-only': (SM, "FILTER (WHERE versions.state IN ('released', 'expired')), 0)::text AS returned_units", "FILTER (WHERE versions.state IN ('released')), 0)::text AS returned_units", 's'),
    'S05-returned-expired-only': (SM, "FILTER (WHERE versions.state IN ('released', 'expired')), 0)::text AS returned_principal", "FILTER (WHERE versions.state IN ('expired')), 0)::text AS returned_principal", 's'),
    'S06-investors-not-distinct': (SM, "COUNT(DISTINCT reservations.party_id)", "COUNT(reservations.party_id)", 's'),
    'S07-investors-any-state': (SM, "COUNT(DISTINCT reservations.party_id) FILTER (WHERE versions.state = 'confirmed')", "COUNT(DISTINCT reservations.party_id)", 's'),
    'S08-no-missing-version-check': (SM, "versions.state IS NULL OR ", "", 's'),
    'S09-no-commitment-check': (SM, " OR (versions.state = 'confirmed') <> (commitments.id IS NOT NULL)", "", 's'),
    'S10-integrity-ignored': (SM, "if ($totals === null || (int) $totals->invalid_roots !== 0) {", "if ($totals === null) {", 's'),
    'S11-occupied-live-only': (SM, "COALESCE(SUM(reservations.units), 0)::text AS occupied_units", "COALESCE(SUM(reservations.units) FILTER (WHERE versions.state IN ('held', 'confirmed')), 0)::text AS occupied_units", 's'),
    'S12-latest-unscoped (equivalent)': (SM, "->whereIn('primary_reservation_id', PrimaryReservationRecord::query()->where('business_campaign_id', $campaignId)->select('id'))\n", "", 's'),
    'S13-outer-unscoped': (SM, "            ->where('reservations.business_campaign_id', $campaignId)\n", "", 's'),
    'S14-inner-join-latest': (SM, "->leftJoinSub($latest", "->joinSub($latest", 's'),
    'S15-all-versions-joined': (SM, "->on('versions.primary_reservation_id', 'reservations.id')->on('versions.revision', 'latest.revision'))", "->on('versions.primary_reservation_id', 'reservations.id'))", 's'),
    'S16-instant-drops-offset': (SM, "$at->format('Y-m-d H:i:s.uP')", "$at->format('Y-m-d H:i:s.u')", 's'),
    'S17-instant-drops-micros': (SM, "$at->format('Y-m-d H:i:s.uP')", "$at->format('Y-m-d H:i:sP')", 's'),
    'S18-committed-held-too': (SM, "SUM(reservations.principal) FILTER (WHERE versions.state = 'confirmed'), 0)::text AS committed_principal", "SUM(reservations.principal) FILTER (WHERE versions.state IN ('confirmed', 'held')), 0)::text AS committed_principal", 's'),
    # --- increment 2: release fixes ---
    'R01-no-adoption-guard': (RS, GUARD, "", 'r'),
    'R02-guard-any-state': (RS, "if ($previous->state === 'held' && $posting->replayed) {", "if ($posting->replayed) {", 'r'),
    'R03-guard-inverted-state': (RS, "if ($previous->state === 'held' && $posting->replayed) {", "if ($previous->state !== 'held' && $posting->replayed) {", 'r'),
    'R04-confirm-expiry-revision-unchanged': (RS, "throw new CommandRejection('RESERVATION_EXPIRED', 409, $previous->revision + 1,\n                            data:", "throw new CommandRejection('RESERVATION_EXPIRED', 409, $previous->revision,\n                            data:", 'r'),
    'R05-confirm-expiry-no-data': (RS, "throw new CommandRejection('RESERVATION_EXPIRED', 409, $previous->revision + 1,\n                            data: ['reservation_id' => $root->id, 'amount' => $root->principal]);", "throw new CommandRejection('RESERVATION_EXPIRED', 409, $previous->revision + 1);", 'r'),
    'R06-confirm-expiry-null-revision': (RS, "throw new CommandRejection('RESERVATION_EXPIRED', 409, $previous->revision + 1,\n                            data:", "throw new CommandRejection('RESERVATION_EXPIRED', 409, null,\n                            data:", 'r'),
    'R07-catch-every-violation': (RS, "                    if ($exception->reasonCode === 'RESERVATION_EXPIRED') {\n                        throw new CommandRejection('RESERVATION_EXPIRED'", "                    if (true) {\n                        throw new CommandRejection('RESERVATION_EXPIRED'", 'r'),
    'R08-entry-check-outside-try': (RS, "                try {\n                    $reservation->window->requireOpen(now('UTC')->toDateTimeImmutable());\n", "                $reservation->window->requireOpen(now('UTC')->toDateTimeImmutable());\n                try {\n", 'r'),
    'R09-amount-from-rights-string-cast (equivalent?)': (RS, "data: ['reservation_id' => $root->id, 'amount' => $root->principal]);\n                    }\n                    throw $exception;", "data: ['reservation_id' => $root->id, 'amount' => (string) $reservation->rights->principal]);\n                    }\n                    throw $exception;", 'r'),
    # prior review mutants, re-anchored to the new code
    'M12-confirm-no-campaign-lock': (RS, LOCK, "                    $reservation->window->requireOpen(now('UTC')->toDateTimeImmutable());\n", 'r'),
    'M13-campaign-lock-after-requote': (RS, LOCK, "                    $reservation->window->requireOpen(now('UTC')->toDateTimeImmutable());\n", 'r'),
    'M20-release-expiry-revision-unchanged': (RS, "$previous->revision + ($reservation->state === 'held' ? 1 : 0)", "$previous->revision", 'r'),
    'M32-wallet-before-root-on-release': (RS, REL_OPEN, REL_OPEN + "                $this->wallets->lockForParty($partyId);\n", 'r'),
    'M33-wallet-before-root-on-expire': (RS, EXP_OPEN, EXP_OPEN + "            $this->wallets->lockForParty(PrimaryReservationRecord::query()->whereKey($reservationId)->value('party_id'));\n", 'r'),
}
M13_EXTRA = ("                    $confirmed = $reservation->confirm($at, $terms, $disclosureVersion, $disclosureSha256);\n",
             "                    $this->campaigns->lock($campaignId);\n                    $confirmed = $reservation->confirm($at, $terms, $disclosureVersion, $disclosureSha256);\n")


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
        parts.append(group[0] + ':' + (f'{m.group(2)}/{m.group(1)}' if m else text[-160:].strip().replace('\n', ' ')))
        killed = killed or out.returncode != 0
    return ('KILLED' if killed else 'SURVIVED'), ' '.join(parts)


def run(name: str) -> str:
    path, old, new, kind = M[name]
    original = open(path).read()
    if original.count(old) != 1:
        return f'{name}: anchor found {original.count(old)} times; NOT APPLIED'
    mutated = original.replace(old, new)
    if name.startswith('M13'):
        assert mutated.count(M13_EXTRA[0]) == 1
        mutated = mutated.replace(*M13_EXTRA)
    open(path, 'w').write(mutated)
    try:
        env = {**os.environ, 'DB_PORT': '5501', 'DB_HOST': '127.0.0.1'}
        shipped, mine = (SUMMARY_T, SUMMARY_MINE) if kind == 's' else (RELEASE_T, RELEASE_MINE)
        verdict, summary = pest(shipped, env)
        extra = ''
        if verdict == 'SURVIVED':
            v2, s2 = pest(mine, env)
            extra = ' | review probes: ' + ('KILLED ' if v2 == 'KILLED' else 'survived ') + s2
        return f'{name}: {verdict} [{summary}]{extra}'
    finally:
        open(path, 'w').write(original)


if __name__ == '__main__':
    names = list(M) if sys.argv[1] == 'all' else sys.argv[1:]
    for name in names:
        print(run(name), flush=True)
