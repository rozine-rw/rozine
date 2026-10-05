<?php

declare(strict_types=1);

namespace App\Application\Wallet\Contracts;

/**
 * The staff ledger drill-down over the sealed wallet journal. Read only: it takes no lock, never
 * corrects or replays an entry, and names a wallet only by its reference, never its owner.
 *
 * @phpstan-type LedgerRow array{id: string, at: string, kind: string, from: string, to: string, reference: string,
 *     amount: array{currency: string, amount: string}}
 * @phpstan-type LedgerPosting array{account: string, account_code: string, debit: array{currency: string, amount: string}|null,
 *     credit: array{currency: string, amount: string}|null}
 * @phpstan-type LedgerDetail array{row: LedgerRow, operation_id: string|null, postings: list<LedgerPosting>,
 *     totals: array{debit: array{currency: string, amount: string}, credit: array{currency: string, amount: string}}, balanced: bool}
 */
interface LedgerReader
{
    /**
     * Entries newest first, matching a reference, wallet reference, entry id or kind when searched.
     *
     * @return array{entries: list<LedgerRow>, total: int, next_before: string|null}
     */
    public function page(string $search, ?string $before, int $limit): array;

    /** @return LedgerDetail|null */
    public function entry(string $entryId): ?array;
}
