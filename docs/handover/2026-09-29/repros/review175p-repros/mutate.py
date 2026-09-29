#!/usr/bin/env python3
"""Apply one named mutation to the r175p worktree, run the PR's tests (and optionally the review repros), restore.

Usage: mutate.py <name>...|all   (run from the r175p worktree root). Prints KILLED/SURVIVED per suite.
"""
import os
import re
import subprocess
import sys

STORE = 'app/Infrastructure/Business/EloquentBusinessCampaignStore.php'
PORT = 'app/Infrastructure/Primary/EloquentCampaignCommitments.php'
PR = ['tests/Feature/PrimaryCampaignClosureGateTest.php', 'tests/Feature/BusinessCampaignClosureTest.php',
      'tests/Feature/BusinessCampaignClosureTransportTest.php', 'tests/Concurrency/PrimaryConfirmationConcurrencyTest.php']
REVIEW = ['tests/Feature/Review175pClosureGateTest.php', 'tests/Concurrency/Review175pRaceTest.php']

GUARD = """        if ($this->commitments->anyForCampaign($campaign->id)) {
            throw new CommandRejection('CAMPAIGN_SETTLEMENT_REQUIRED', revision: 1,
                data: ['campaign_id' => $campaign->id, 'business_id' => $campaign->business_id]);
        }
"""
CLOSED = """        if ($phase === 'cancelled' && $recordedAt->gte($campaign->expires_at)) {
            throw new CommandRejection('CAMPAIGN_CLOSED', revision: 1, data: ['campaign_id' => $campaign->id, 'business_id' => $campaign->business_id]);
        }
"""
SAVE = "            'phase' => $phase, 'actor_user_id' => $userId, 'closed_at' => $closedAt, 'payload' => $payload, 'sha256' => $this->hash($payload)])->save();\n"
LOOP = "        foreach ($due as $candidate) {\n"
CANCEL = "        return $this->authority->handle($userId, $contextRevision, $businessId, 'application.sign', null,\n            function (array $business, array $identity) use ($userId, $contextRevision, $campaignId, $expectedRevision, $reason, $requestId): array {\n"
PORTQ = """        return PrimaryCommitment::query()->whereIn('primary_reservation_id',
            PrimaryReservationRecord::query()->where('business_campaign_id', $campaignId)->select('id'))->exists();"""

MUTATIONS = {
    'G1-guard-removed': (STORE, GUARD, ''),
    'G2-guard-cancel-only': (STORE, "        if ($this->commitments->anyForCampaign($campaign->id)) {", "        if ($phase === 'cancelled' && $this->commitments->anyForCampaign($campaign->id)) {"),
    'G3-guard-expiry-only': (STORE, "        if ($this->commitments->anyForCampaign($campaign->id)) {", "        if ($phase === 'expired' && $this->commitments->anyForCampaign($campaign->id)) {"),
    'G4-guard-after-save': (STORE, GUARD + "        $closedAt", "        $closedAt", SAVE, SAVE + GUARD),
    'G5-guard-before-deadline-check': (STORE, CLOSED + GUARD, GUARD + CLOSED),
    'G6-guard-code-renamed': (STORE, "throw new CommandRejection('CAMPAIGN_SETTLEMENT_REQUIRED', revision: 1,", "throw new CommandRejection('CAMPAIGN_CLOSED', revision: 1,"),
    'G7-guard-revision-2': (STORE, "'CAMPAIGN_SETTLEMENT_REQUIRED', revision: 1,", "'CAMPAIGN_SETTLEMENT_REQUIRED', revision: 2,"),
    'G8-guard-no-data': (STORE, "            throw new CommandRejection('CAMPAIGN_SETTLEMENT_REQUIRED', revision: 1,\n                data: ['campaign_id' => $campaign->id, 'business_id' => $campaign->business_id]);", "            throw new CommandRejection('CAMPAIGN_SETTLEMENT_REQUIRED', revision: 1);"),
    'G9-guard-runtime-exception': (STORE, "            throw new CommandRejection('CAMPAIGN_SETTLEMENT_REQUIRED', revision: 1,\n                data: ['campaign_id' => $campaign->id, 'business_id' => $campaign->business_id]);", "            throw new RuntimeException('CAMPAIGN_SETTLEMENT_REQUIRED');"),
    'C1-can-cancel-ignores-commitments': (STORE, " && ! $this->commitments->anyForCampaign($campaign->id)\n", "\n"),
    'T1-cancel-toctou-prelock-check': (STORE, CANCEL + "                $partyId", "__SKIP__"),
    'T2-expiry-toctou-prelock-check': (STORE, GUARD, '', LOOP, LOOP + "            if ($this->commitments->anyForCampaign($candidate->id)) {\n                throw new CommandRejection('CAMPAIGN_SETTLEMENT_REQUIRED', revision: 1, data: ['campaign_id' => $candidate->id, 'business_id' => $candidate->business_id]);\n            }\n"),
    'T3-cancel-check-before-business-lock': (STORE, GUARD, '', CANCEL, "        if ($this->commitments->anyForCampaign($campaignId)) {\n            throw new CommandRejection('CAMPAIGN_SETTLEMENT_REQUIRED', revision: 1, data: ['campaign_id' => $campaignId, 'business_id' => $businessId]);\n        }\n" + CANCEL),
    'T2b-expiry-toctou-only': (STORE, "        if ($this->commitments->anyForCampaign($campaign->id)) {", "        if ($phase === 'cancelled' && $this->commitments->anyForCampaign($campaign->id)) {", LOOP, LOOP + "            if ($this->commitments->anyForCampaign($candidate->id)) {\n                throw new CommandRejection('CAMPAIGN_SETTLEMENT_REQUIRED', revision: 1, data: ['campaign_id' => $candidate->id, 'business_id' => $candidate->business_id]);\n            }\n"),
    'T3b-cancel-toctou-only': (STORE, "        if ($this->commitments->anyForCampaign($campaign->id)) {", "        if ($phase === 'expired' && $this->commitments->anyForCampaign($campaign->id)) {", CANCEL, "        if ($this->commitments->anyForCampaign($campaignId)) {\n            throw new CommandRejection('CAMPAIGN_SETTLEMENT_REQUIRED', revision: 1, data: ['campaign_id' => $campaignId, 'business_id' => $businessId]);\n        }\n" + CANCEL),
    'P1-port-always-false': (PORT, PORTQ, "        return false;"),
    'P2-port-ignores-campaign': (PORT, "PrimaryReservationRecord::query()->where('business_campaign_id', $campaignId)->select('id')", "PrimaryReservationRecord::query()->select('id')"),
    'P3-port-any-reservation': (PORT, PORTQ, "        return PrimaryReservationRecord::query()->where('business_campaign_id', $campaignId)->exists();"),
    'P4-port-wrong-key': (PORT, "whereIn('primary_reservation_id',", "whereIn('primary_reservation_version_id',"),
    'P5-port-doesnt-exist': (PORT, "->select('id'))->exists();", "->select('id'))->doesntExist();"),
}


def pest(tests, env, extra=()):
    killed = False
    parts = []
    for group in ('Feature', 'Concurrency'):
        files = [t for t in tests if t.startswith('tests/' + group + '/')]
        if not files:
            continue
        out = subprocess.run(['php', '-d', 'memory_limit=2G', 'vendor/bin/pest', '--compact', *extra, *files], env=env, capture_output=True, text=True)
        text = re.sub(r'\x1b\[[0-9;]*m', '', out.stdout + out.stderr)
        parts.append(group[0] + ': ' + (' '.join(re.findall(r'Tests:\s+(.*)', text)).strip() or text[-300:].strip()))
        killed = killed or out.returncode != 0
    return ('KILLED' if killed else 'SURVIVED'), '; '.join(parts)


def run(name: str) -> str:
    spec = MUTATIONS[name]
    path, pairs = spec[0], list(zip(spec[1::2], spec[2::2]))
    if any(new == '__SKIP__' for _, new in pairs):
        return f'{name}: skipped'
    original = open(path).read()
    mutated = original
    for old, new in pairs:
        if mutated.count(old) != 1:
            return f'{name}: anchor found {mutated.count(old)} times; NOT APPLIED'
        mutated = mutated.replace(old, new)
    open(path, 'w').write(mutated)
    try:
        env = {**os.environ, 'DB_PORT': '5496', 'DB_HOST': '127.0.0.1', 'PAO_DISABLE': '1'}
        pr = pest(PR, env)
        review = pest([t for t in REVIEW if os.path.exists(t)], env, ['--filter=/^(?!.*(P2-1|P3-1)).*$/'])
        return f'{name}: PR {pr[0]} [{pr[1]}] | +review {review[0]} [{review[1]}]'
    finally:
        open(path, 'w').write(original)


if __name__ == '__main__':
    names = [n for n in MUTATIONS if not n.startswith('T1')] if sys.argv[1] == 'all' else sys.argv[1:]
    for name in names:
        print(run(name), flush=True)
