<?php

declare(strict_types=1);

namespace App\Application\Operations;

use App\Application\Identity\AuthorizeStaffPermission;
use App\Application\Operations\Contracts\LedgerJournal;
use App\Domain\Operations\CommandRejection;

/**
 * The Admin Ledger for Treasury, Compliance and superadmin (`ledger.view`): every wallet ledger
 * posting, newest first, searched on the server, and one entry opened to its lines. Nothing here
 * can be posted, edited or reversed.
 *
 * @phpstan-import-type Entry from LedgerJournal
 */
final readonly class BrowseLedger
{
    public function __construct(private LedgerJournal $journal, private AuthorizeStaffPermission $staff) {}

    /** @return array{entries: list<Entry>, total: int, entry: Entry|null} */
    public function handle(int $actorId, ?string $search, int $limit, ?string $entryId): array
    {
        $this->staff->check($actorId, 'ledger.view');
        $entry = $entryId === null ? null : $this->journal->entry($entryId);
        if ($entryId !== null && $entry === null) {
            throw new CommandRejection('LEDGER_ENTRY_NOT_FOUND', 404);
        }

        return [...$this->journal->entries($search, $limit), 'entry' => $entry];
    }

    /**
     * The newest postings, for the Operations Center's activity feed.
     *
     * @return list<Entry>
     */
    public function latest(int $actorId, int $limit): array
    {
        $this->staff->check($actorId, 'ledger.view');

        return $this->journal->entries(null, $limit)['entries'];
    }
}
