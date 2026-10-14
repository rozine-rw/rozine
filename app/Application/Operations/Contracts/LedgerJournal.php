<?php

declare(strict_types=1);

namespace App\Application\Operations\Contracts;

/**
 * Every posting in the wallet ledger, newest first, for the Admin Ledger (MVP-ADMIN ledger
 * drill-down). Reads only and never locks; the caller checks the staff permission.
 *
 * An entry carries its recorded kind, its balanced lines and what the lines name: each wallet
 * account's owner (the Investor's account name or the Business's name), and the reference the
 * money moved under (the provider reference of a deposit or withdrawal, or the Business of a
 * purchase, issue or repayment). A search matches an owner, the reference, the kind or the entry
 * id; `limit` caps the page while `total` counts every match.
 *
 * @phpstan-type Line array{
 *     direction: 'debit'|'credit', amount: string, account_id: string, account_kind: string,
 *     owner: 'investor'|'business'|null, owner_name: string|null
 * }
 * @phpstan-type Entry array{
 *     id: string, kind: string, at: string, reference: string|null, operation_id: string|null, lines: list<Line>
 * }
 */
interface LedgerJournal
{
    /** @return array{entries: list<Entry>, total: int} */
    public function entries(?string $search, int $limit): array;

    /** @return Entry|null */
    public function entry(string $id): ?array;
}
