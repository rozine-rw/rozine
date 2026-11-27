<?php

declare(strict_types=1);

namespace App\Application\Operations;

use App\Application\Identity\AuthorizeStaffPermission;
use App\Application\Operations\Contracts\ReconciliationStore;
use App\Domain\Operations\ReconciliationBalances;
use Carbon\CarbonImmutable;

/**
 * Reconciliation for Treasury and Compliance (`reconciliation.view`, MVP-ADMIN-SCR-04, AC-05): the
 * close of the last Kigali business day, each provider clearing account as of that close, and every
 * break open now. A complete set of equal cutoff balances and no unresolved cutoff break is
 * required to reconcile; absence of provider-event exceptions alone proves nothing.
 *
 * @phpstan-import-type Account from ReconciliationStore
 *
 * @phpstan-type OpenBreak array{
 *     id: string, account_id: 'deposits'|'withdrawals', flow: 'investor'|'business'|'clearing', kind: 'conflict'|'reversed'|'mismatch'|'key_conflict'|'balance_variance',
 *     reference: string, amount: string, opened_at: string, party_id: string|null, business_id: string|null, age_days: int, cutoff_at?: string
 * }
 * @phpstan-type Reconciliation array{
 *     business_date: string, as_of: CarbonImmutable, complete: bool, closed: bool, accounts: list<Account>, breaks: list<OpenBreak>
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
        $accounts = $this->store->accounts($asOf);
        $breaks = $this->store->breaks($now);
        $opened = fn (array $break): CarbonImmutable => CarbonImmutable::parse($break['opened_at'])->setTimezone(self::ZONE);

        return ['business_date' => $asOf->format('Y-m-d'), 'as_of' => $asOf, 'accounts' => $accounts, 'complete' => ReconciliationBalances::complete($accounts),
            'closed' => ReconciliationBalances::balanced($accounts) && array_filter($breaks,
                fn (array $break): bool => CarbonImmutable::parse($break['cutoff_at'] ?? $break['opened_at'])->lessThanOrEqualTo($asOf)) === [],
            'breaks' => array_map(fn (array $break): array => [...$break, 'opened_at' => $opened($break)->toIso8601String(),
                'age_days' => (int) $opened($break)->startOfDay()->diffInDays($now->startOfDay())], $breaks)];
    }
}
