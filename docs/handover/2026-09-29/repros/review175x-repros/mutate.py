#!/usr/bin/env python3
"""One mutation at a time in r175x: run the PR's focused tests, then (if it survives) all
Primary Feature tests, then the review repros; restore. Usage (from r175x root): mutate.py all|<name>..."""
import glob
import os
import subprocess
import sys

R = 'app/Infrastructure/Primary/EloquentPrimaryReservations.php'
S = 'app/Infrastructure/Business/EloquentPrimaryCampaignSource.php'
F = 'database/factories/PrimaryCommitmentFactory.php'
PR = ['tests/Feature/PrimaryFundingCandidateTest.php', 'tests/Feature/PrimaryBusinessConnectionTest.php',
      'tests/Concurrency/PrimaryFundingCandidateConcurrencyTest.php', 'tests/Concurrency/PrimaryCampaignSourceLockTest.php']
BROAD = sorted(set(glob.glob('tests/Feature/Primary*Test.php')) - set(PR))
LOG = '../review175x-repros/mutations.log'
OURS = ['tests/Feature/Review175xTest.php', 'tests/Concurrency/Review175xConcurrencyTest.php']

M = {
    'C01-no-txn-guard': (R, """    public function lockFundingCandidate(string $campaignId): PrimaryFundingCandidate
    {
        if (DB::transactionLevel() === 0) {""", """    public function lockFundingCandidate(string $campaignId): PrimaryFundingCandidate
    {
        if (false) {"""),
    'C02-accept-nonconfirmed': (R, """                if ($reservation->state !== 'confirmed') {
                    throw new CommandRejection('CAMPAIGN_NOT_FULLY_COMMITTED');""", """                if (false) {
                    throw new CommandRejection('CAMPAIGN_NOT_FULLY_COMMITTED');"""),
    'C03-no-commitment-version-bind': (R, "if ($commitment === null || $commitment->primary_reservation_version_id !== $version->id\n", "if ($commitment === null\n"),
    'C04-no-commitment-op-bind': (R, "|| $commitment->operation_id !== $version->operation_id || ", "|| "),
    'C05-no-confirmed-at-bind': (R, " || ! $commitment->confirmed_at->equalTo($version->created_at)) {", ") {"),
    'C06-no-count-check': (R, "if (! $ordinals->count->isEqualTo($campaign['units']) || ", "if ("),
    'C07-no-principal-check': (R, " || ! $principal->isEqualTo($campaign['principal'])) {\n                throw new CommandRejection('CAMPAIGN_NOT_FULLY_COMMITTED');", ") {\n                throw new CommandRejection('CAMPAIGN_NOT_FULLY_COMMITTED');"),
    'C08-no-wallet-sort': (R, "->unique()->sort()->values();", "->unique()->values();"),
    'C09-no-deadline-recheck': (R, "            if (now('UTC')->gte($campaign['expires_at'])) {\n                throw new CommandRejection('CAMPAIGN_CLOSED');", "            if (false) {\n                throw new CommandRejection('CAMPAIGN_CLOSED');"),
    'C10-deadline-gt': (R, "            if (now('UTC')->gte($campaign['expires_at'])) {\n                throw new CommandRejection('CAMPAIGN_CLOSED');", "            if (now('UTC')->gt($campaign['expires_at'])) {\n                throw new CommandRejection('CAMPAIGN_CLOSED');"),
    'C11-lockRetained': (R, "        return DB::transaction(function () use ($campaignId): PrimaryFundingCandidate {\n            $campaign = $this->campaigns->lock($campaignId);", "        return DB::transaction(function () use ($campaignId): PrimaryFundingCandidate {\n            $campaign = $this->campaigns->lockRetained($campaignId);"),
    'C12-roots-unlocked': (R, "$roots = PrimaryReservationRecord::query()->where('business_campaign_id', $campaignId)->orderBy('id')->lockForUpdate()->get();", "$roots = PrimaryReservationRecord::query()->where('business_campaign_id', $campaignId)->orderBy('id')->get();"),
    'C13-commitments-unlocked': (R, "->orderBy('id')->lockForUpdate()->get()->keyBy('primary_reservation_id');", "->orderBy('id')->get()->keyBy('primary_reservation_id');"),
    'C14-cash-first-root-only': (R, """            foreach ($roots as $root) {
                $purchases[] = [""", """            foreach ($roots as $i => $root) {
                if ($i > 0) { $purchases[] = [...$retained[$root->id], 'reservation_id' => $root->id, 'party_id' => $root->party_id, 'cash' => null]; continue; }
                $purchases[] = ["""),
    'C15-connections-after-wallets': (R, """            $this->campaigns->rejectKnownConnections($campaign['business_id'], array_values($partyIds->all()));
            $wallets = [];
            foreach ($partyIds as $partyId) {
                $wallets[$partyId] = $this->wallets->lockForParty($partyId);
            }""", """            $wallets = [];
            foreach ($partyIds as $partyId) {
                $wallets[$partyId] = $this->wallets->lockForParty($partyId);
            }
            $this->campaigns->rejectKnownConnections($campaign['business_id'], array_values($partyIds->all()));"""),
    'E01-no-reserve-gate': (R, "                $this->campaigns->rejectKnownConnections($campaign['business_id'], [$partyId]);\n                $quantity", "                $quantity"),
    'E02-no-confirm-gate': (R, "                    $this->campaigns->rejectKnownConnections($campaign['business_id'], [$partyId]);\n                    $terms = $admit", "                    $terms = $admit"),
    'E03-no-funding-gate': (R, "            $this->campaigns->rejectKnownConnections($campaign['business_id'], array_values($partyIds->all()));\n", ""),
    'E04-confirm-gate-after-admit': (R, "                    $this->campaigns->rejectKnownConnections($campaign['business_id'], [$partyId]);\n                    $terms = $admit($reservation->rights, $campaign);", "                    $terms = $admit($reservation->rights, $campaign);\n                    $this->campaigns->rejectKnownConnections($campaign['business_id'], [$partyId]);"),
    'E05-no-lowercase': (S, "array_intersect(array_map(strtolower(...), $partyIds), ", "array_intersect($partyIds, "),
    'E06-no-entity-party': (S, "[$business->entity_party_id, ...array_column($terms['people'], 'party_id')]", "[...array_column($terms['people'], 'party_id')]"),
    'E07-no-mandate-people': (S, "[$business->entity_party_id, ...array_column($terms['people'], 'party_id')]", "[$business->entity_party_id]"),
    'E08-no-status': (S, "if ($terms['status'] !== 'active' || ", "if ("),
    'E09-no-effective': (S, "$terms['effective_at'] > $now\n", "false\n"),
    'E10-effective-gte': (S, "$terms['effective_at'] > $now\n", "$terms['effective_at'] >= $now\n"),
    'E11-expires-lt': (S, "$terms['expires_at'] <= $now))", "$terms['expires_at'] < $now))"),
    'E12-no-expiry': (S, "|| ($terms['expires_at'] !== null && $terms['expires_at'] <= $now))", ")"),
    'E13-no-business-lock': (S, "$business = BusinessProfile::query()->whereKey($businessId)->lockForUpdate()->first()", "$business = BusinessProfile::query()->whereKey($businessId)->first()"),
    'E14-no-txn-guard': (S, """    public function rejectKnownConnections(string $businessId, array $partyIds): void
    {
        if (DB::transactionLevel() === 0) {""", """    public function rejectKnownConnections(string $businessId, array $partyIds): void
    {
        if (false) {"""),
    'E15-missing-mandate-passes': (S, "?? throw new CommandRejection('MANDATE_REQUIRED', 403);\n        $terms", "?? null;\n        if ($mandate === null) { return; }\n        $terms"),
    'F01-revert-factory': (F, "'created_at' => fn () => now()->startOfSecond()];", "'created_at' => now()->startOfSecond()];"),
}


def run(tests, pgsql=False):
    env = dict(os.environ, DB_PORT='5505', PAO_DISABLE='1')
    feature = [t for t in tests if t.startswith('tests/Feature')]
    conc = [t for t in tests if t.startswith('tests/Concurrency')]
    for group, cfg in ((feature, []), (conc, ['-c', 'phpunit.pgsql.xml'])):
        if not group:
            continue
        r = subprocess.run(['php', '-d', 'memory_limit=2G', 'vendor/bin/pest', '--no-tia', '--compact', '--stop-on-failure', *cfg, *group], env=env, capture_output=True, text=True, timeout=900)
        if r.returncode != 0:
            failed = [line for line in (r.stdout + r.stderr).splitlines() if 'FAILED' in line or '⨯' in line][:1]
            return 'KILLED', ' '.join(group) if not failed else failed[0].strip()[:140]
    return 'SURVIVED', ''


def main():
    names = list(M) if sys.argv[1:] == ['all'] else sys.argv[1:]
    done = open(LOG).read() if os.path.exists(LOG) else ''
    for name in names:
        if f'{name}:' in done:
            continue
        path, old, new = M[name]
        original = open(path).read()
        if original.count(old) != 1:
            print(f'{name}: PATTERN-MISSING ({original.count(old)})', flush=True)
            continue
        try:
            open(path, 'w').write(original.replace(old, new))
            pr, by = run(PR + (['tests/Feature/PrimaryReservationSchemaTest.php'] if path == F else []))
            broad, by2 = run(BROAD) if pr == 'SURVIVED' else ('-', '')
            ours, by3 = run(OURS) if broad == 'SURVIVED' else ('-', '')
            with open(LOG, 'a') as log:
                log.write(f'{name}: PR={pr} {by} | broad={broad} {by2} | repros={ours} {by3}\n')
        finally:
            open(path, 'w').write(original)


main()
