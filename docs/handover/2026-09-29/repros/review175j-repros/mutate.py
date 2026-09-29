#!/usr/bin/env python3
"""Apply one named mutation to the r175j worktree, run its tests, and restore the file.

Usage: mutate.py <name>   (run from the r175j worktree root)
"""
import os
import re
import subprocess
import sys

CHECKOUT = 'app/Infrastructure/Primary/EloquentPrimaryCheckout.php'
SOURCE = 'app/Infrastructure/Business/EloquentPrimaryCampaignSource.php'
MIGRATION = 'database/migrations/2026_09_28_165949_reject_unbound_primary_commitment_sources.php'
ANCHOR = 'database/migrations/2026_09_28_140000_bind_primary_postings_to_their_source_anchor.php'

LOCK_TESTS = ['tests/Concurrency/PrimaryCheckoutLockTest.php', 'tests/Concurrency/Review175jCheckoutRaceTest.php', 'tests/Feature/PrimaryCheckoutTest.php']
SOURCE_TESTS = ['tests/Feature/PrimaryCommitmentSourceTest.php', 'tests/Feature/PrimaryHoldBindingTest.php',
                'tests/Concurrency/PrimaryReservationFundingConcurrencyTest.php', 'tests/Feature/Review175jCheckoutBoundaryTest.php']

MUTATIONS = {
    'M1-no-business-lock': (CHECKOUT, "            $this->campaigns->lockBusiness($campaignId);\n\n", "\n", LOCK_TESTS),
    'M2-business-after-authority': (CHECKOUT,
        "            $this->campaigns->lockBusiness($campaignId);\n\n            return $this->authority->handle($userId, 'investor', null, $contextRevision, $operation);",
        "            return $this->authority->handle($userId, 'investor', null, $contextRevision, function (array $identity) use ($campaignId, $operation): array {\n                $this->campaigns->lockBusiness($campaignId);\n\n                return $operation($identity);\n            });",
        LOCK_TESTS),
    'M3-business-not-for-update': (SOURCE, "BusinessProfile::query()->whereKey($candidate->business_id)->lockForUpdate()->firstOrFail();",
        "BusinessProfile::query()->whereKey($candidate->business_id)->firstOrFail();", LOCK_TESTS + ['tests/Feature/PrimaryCampaignSourceTest.php', 'tests/Concurrency/PrimaryCampaignSourceLockTest.php']),
    'M4-lookup-any-campaign': (CHECKOUT, "if ($type !== 'campaign' || $id !== $campaignId) {", "if ($type !== 'campaign') {", ['tests/Feature/PrimaryCheckoutTest.php']),
    'M5-lookup-no-target-check': (CHECKOUT, "if ($type !== 'campaign' || $id !== $campaignId) {", "if (false) {", ['tests/Feature/PrimaryCheckoutTest.php']),
    'M6-source-lock-skips-business': (SOURCE, "        $this->lockBusiness($campaignId);\n        $campaign =", "        $campaign =",
        ['tests/Feature/PrimaryCampaignSourceTest.php', 'tests/Concurrency/PrimaryReservationFundingConcurrencyTest.php', 'tests/Concurrency/PrimaryReservationConcurrencyTest.php', 'tests/Concurrency/PrimaryCampaignSourceLockTest.php']),
    'M7-constraint-wrong-literal': (MIGRATION, "CHECK (source_type <> 'primary_commitment');", "CHECK (source_type <> 'primary_commitment_x');", SOURCE_TESTS),
    'M8-down-no-guard': (MIGRATION, "IF EXISTS (SELECT 1 FROM ledger_entries WHERE kind <> 'deposit_credit') THEN", "IF false THEN",
        ['tests/Feature/PrimaryCommitmentSourceTest.php', 'tests/Feature/WalletSchemaTest.php']),
    'M9-down-no-table-lock': (MIGRATION, "                LOCK TABLE ledger_entries IN ACCESS EXCLUSIVE MODE;\n                DO $$", "                DO $$",
        ['tests/Feature/PrimaryCommitmentSourceTest.php', 'tests/Feature/WalletSchemaTest.php']),
    'M10-up-no-table-lock': (MIGRATION, "                LOCK TABLE ledger_entries IN ACCESS EXCLUSIVE MODE;\n                ALTER TABLE", "                ALTER TABLE",
        ['tests/Feature/PrimaryCommitmentSourceTest.php', 'tests/Feature/WalletSchemaTest.php']),
    'M11-up-not-valid': (MIGRATION, "CHECK (source_type <> 'primary_commitment');", "CHECK (source_type <> 'primary_commitment') NOT VALID;",
        ['tests/Feature/PrimaryCommitmentSourceTest.php']),
    'M12-anchor-amount-unchecked': (ANCHOR, "IF anchor_amount IS NULL OR anchor_amount <> moved THEN", "IF anchor_amount IS NULL THEN",
        ['tests/Concurrency/WalletIndependentReviewTest.php']),
    'M13-down-guard-commitment-only': (MIGRATION, "IF EXISTS (SELECT 1 FROM ledger_entries WHERE kind <> 'deposit_credit') THEN",
        "IF EXISTS (SELECT 1 FROM ledger_entries WHERE source_type = 'primary_commitment') THEN",
        ['tests/Feature/PrimaryCommitmentSourceTest.php']),
}


def main() -> int:
    name = sys.argv[1]
    path, old, new, tests = MUTATIONS[name]
    original = open(path).read()
    if original.count(old) != 1:
        print(f'{name}: anchor text found {original.count(old)} times; not applied')
        return 2
    open(path, 'w').write(original.replace(old, new))
    try:
        env = {**os.environ, 'DB_PORT': '5489', 'DB_HOST': '127.0.0.1', 'PAO_DISABLE': '1'}
        run = subprocess.run(['php', '-d', 'memory_limit=2G', 'vendor/bin/pest', '--compact', *tests], env=env, capture_output=True, text=True)
        out = re.sub(r'\x1b\[[0-9;]*m', '', run.stdout + run.stderr)
        summary = [line.strip() for line in out.splitlines() if 'Tests:' in line]
        failed = sorted({re.sub(r'\s+', ' ', line.strip())[:140] for line in out.splitlines() if 'FAILED' in line})
        verdict = 'KILLED' if run.returncode != 0 else 'SURVIVED'
        print(f'{name}: {verdict} | {summary[-1] if summary else "no summary"}')
        for line in failed:
            print('   ', line)
    finally:
        open(path, 'w').write(original)
    return 0


if __name__ == '__main__':
    sys.exit(main())
