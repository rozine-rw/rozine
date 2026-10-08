<?php

declare(strict_types=1);

namespace App\Application\Wallet;

use App\Application\Identity\AuthorizeStaffPermission;
use App\Application\Operations\Contracts\OperationRecords;
use App\Application\Wallet\Contracts\LedgerReader;

/**
 * The staff ledger drill-down (`ledger.view`): entries newest first and one opened entry with its
 * postings and its origin. Read only; an entry is never corrected here.
 *
 * An entry records only the operation its money flow started in, not the one that posted it: a
 * confirmation, release, refund or issue keeps its reservation's origin. So the origin's actor and
 * time are reported as the origin, never as who posted the entry; the entry's own `at` is when it
 * was posted.
 *
 * @phpstan-import-type LedgerRow from LedgerReader
 * @phpstan-import-type LedgerPosting from LedgerReader
 * @phpstan-import-type LedgerDetail from LedgerReader
 *
 * @phpstan-type StaffLedgerEntry array{row: LedgerRow, operation_id: string|null, postings: list<LedgerPosting>,
 *     totals: array{debit: array{currency: string, amount: string}, credit: array{currency: string, amount: string}}, balanced: bool,
 *     origin: array{actor: string, at: string, reason: null}|null}
 */
final class GetStaffLedger
{
    public const int LIMIT = 50;

    public function __construct(private AuthorizeStaffPermission $staff, private LedgerReader $ledger, private OperationRecords $operations) {}

    /**
     * @param  array{search?: string|null, before?: string|null, entry?: string|null}  $query
     * @return array{permissions: list<string>, search: string, entries: list<LedgerRow>, total: int, next_before: string|null,
     *     entry: StaffLedgerEntry|null}
     */
    public function page(int $userId, array $query): array
    {
        $this->staff->check($userId, 'ledger.view');
        $search = trim($query['search'] ?? '');
        $page = $this->ledger->page($search, $query['before'] ?? null, self::LIMIT);
        $entry = ($query['entry'] ?? null) === null ? null : $this->ledger->entry($query['entry']);

        return ['permissions' => $this->staff->permissions($userId), 'search' => $search, ...$page,
            'entry' => $entry === null ? null : [...$entry, 'origin' => $this->origin($entry)]];
    }

    /**
     * The actor and time of the entry's origin operation, by reference and never by name; null when
     * the entry carries no operation or the journal holds none for it.
     *
     * @param  LedgerDetail  $entry
     * @return array{actor: string, at: string, reason: null}|null
     */
    private function origin(array $entry): ?array
    {
        $attribution = $entry['operation_id'] === null ? null : $this->operations->attribution($entry['operation_id']);
        if ($attribution === null) {
            return null;
        }
        $actor = match (true) {
            str_starts_with($attribution['actor_key'], 'party:') => 'PARTY-'.strtoupper(substr($attribution['actor_key'], -8)),
            str_starts_with($attribution['actor_key'], 'staff:') => 'STAFF-'.substr($attribution['actor_key'], 6),
            default => strtoupper($attribution['actor_key']),
        };

        return ['actor' => $actor, 'at' => $attribution['recorded_at'], 'reason' => null];
    }
}
