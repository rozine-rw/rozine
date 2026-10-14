<?php

declare(strict_types=1);

namespace App\Application\Operations;

use App\Application\Identity\AuthorizeStaffPermission;
use App\Application\Operations\Contracts\ReconciliationStore;
use Carbon\CarbonImmutable;

/**
 * Reconciliation for Treasury and Compliance (`reconciliation.view`, MVP-ADMIN-SCR-04, AC-05): the
 * close of the last Kigali business day, each provider clearing account as of that close, and every
 * break open now. A day with a break recorded by its close cannot close; otherwise it reconciled at
 * its close.
 *
 * @phpstan-import-type Account from ReconciliationStore
 *
 * @phpstan-type OpenBreak array{
 *     id: string, account_id: 'deposits'|'withdrawals', flow: 'investor'|'business', kind: 'conflict'|'reversed'|'mismatch'|'key_conflict',
 *     reference: string, amount: string, opened_at: string, party_id: string|null, business_id: string|null, age_days: int
 * }
 * @phpstan-type Reconciliation array{
 *     business_date: string, as_of: CarbonImmutable, closed: bool, accounts: list<Account>, breaks: list<OpenBreak>
 * }
 */
final readonly class BrowseReconciliation
{
    private const string ZONE = 'Africa/Kigali';

    public function __construct(private ReconciliationStore $store, private AuthorizeStaffPermission $staff) {}

    /** @return Reconciliation */
    public function handle(int $actorId): array
    {
        $this->staff->check($actorId, 'reconciliation.view');
        $now = CarbonImmutable::now(self::ZONE);
        $asOf = $now->subDay()->endOfDay();
        $breaks = $this->store->breaks($now);
        $opened = fn (array $break): CarbonImmutable => CarbonImmutable::parse($break['opened_at'])->setTimezone(self::ZONE);

        return ['business_date' => $asOf->format('Y-m-d'), 'as_of' => $asOf, 'accounts' => $this->store->accounts($asOf),
            'closed' => array_filter($breaks, fn (array $break): bool => $opened($break)->lessThanOrEqualTo($asOf)) === [],
            'breaks' => array_map(fn (array $break): array => [...$break, 'opened_at' => $opened($break)->toIso8601String(),
                'age_days' => (int) $opened($break)->startOfDay()->diffInDays($now->startOfDay())], $breaks)];
    }
}
