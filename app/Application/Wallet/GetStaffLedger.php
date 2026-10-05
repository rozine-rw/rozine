<?php

declare(strict_types=1);

namespace App\Application\Wallet;

use App\Application\Identity\AuthorizeStaffPermission;
use App\Application\Operations\Contracts\OperationRecords;
use App\Application\Wallet\Contracts\LedgerReader;

/**
 * The staff ledger drill-down (`ledger.view`): entries newest first and one opened entry with its
 * postings and who recorded it. Read only; an entry is never corrected here.
 *
 * @phpstan-import-type LedgerRow from LedgerReader
 * @phpstan-import-type LedgerPosting from LedgerReader
 * @phpstan-import-type LedgerDetail from LedgerReader
 *
 * @phpstan-type StaffLedgerEntry array{row: LedgerRow, operation_id: string|null, postings: list<LedgerPosting>,
 *     totals: array{debit: array{currency: string, amount: string}, credit: array{currency: string, amount: string}}, balanced: bool,
 *     posted_by: array{actor: string, at: string, reason: null}}
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
            'entry' => $entry === null ? null : [...$entry, 'posted_by' => $this->postedBy($entry)]];
    }

    /**
     * The recording actor by reference, never by name; ROZINE for an entry no operation recorded.
     *
     * @param  LedgerDetail  $entry
     * @return array{actor: string, at: string, reason: null}
     */
    private function postedBy(array $entry): array
    {
        $attribution = $entry['operation_id'] === null ? null : $this->operations->attribution($entry['operation_id']);
        $actor = $attribution === null ? 'ROZINE' : match (true) {
            str_starts_with($attribution['actor_key'], 'party:') => 'PARTY-'.strtoupper(substr($attribution['actor_key'], -8)),
            str_starts_with($attribution['actor_key'], 'staff:') => 'STAFF-'.substr($attribution['actor_key'], 6),
            default => strtoupper($attribution['actor_key']),
        };

        return ['actor' => $actor, 'at' => $attribution['recorded_at'] ?? $entry['row']['at'], 'reason' => null];
    }
}
