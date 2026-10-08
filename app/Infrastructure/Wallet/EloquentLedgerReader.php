<?php

declare(strict_types=1);

namespace App\Infrastructure\Wallet;

use App\Application\Wallet\Contracts\LedgerReader;
use App\Models\LedgerAccount;
use App\Models\LedgerEntry;
use App\Models\LedgerLine;
use Brick\Math\BigInteger;
use Illuminate\Database\Eloquent\Builder;
use RuntimeException;

/**
 * Reads the sealed journal for staff. Each entry's movement runs from its debited accounts to its
 * credited ones; a wallet account is named by its owner kind and the last eight characters of its
 * wallet, a system account as ROZINE. An unknown entry kind fails closed.
 */
final class EloquentLedgerReader implements LedgerReader
{
    private const array KINDS = ['deposit_credit' => 'deposit', 'business_deposit_credit' => 'deposit', 'primary_hold' => 'hold',
        'primary_commit' => 'investment', 'primary_release' => 'release', 'primary_refund' => 'refund', 'primary_issue' => 'issue',
        'business_repayment_debit' => 'repayment'];

    public function page(string $search, ?string $before, int $limit): array
    {
        $query = LedgerEntry::query()->when(trim($search) !== '', fn (Builder $query): Builder => $this->matching($query, $search));
        $total = (clone $query)->count();
        $entries = array_values($query->when($before !== null, fn (Builder $query): Builder => $query->where('id', '<', $before))
            ->orderByDesc('id')->limit($limit + 1)->get()->all());
        $more = count($entries) > $limit;
        $entries = array_slice($entries, 0, $limit);
        [$lines, $accounts] = $this->lines(array_map(fn (LedgerEntry $entry): string => $entry->id, $entries));

        return ['entries' => array_map(fn (LedgerEntry $entry): array => $this->row($entry, $lines[$entry->id] ?? [], $accounts), $entries),
            'total' => $total, 'next_before' => $more ? $entries[$limit - 1]->id : null];
    }

    public function entry(string $entryId): ?array
    {
        $entry = LedgerEntry::query()->whereKey($entryId)->first();
        if ($entry === null) {
            return null;
        }
        [$grouped, $accounts] = $this->lines([$entry->id]);
        $lines = $grouped[$entry->id] ?? [];
        $debit = $this->sum($lines, 'debit');
        $credit = $this->sum($lines, 'credit');

        return ['row' => $this->row($entry, $lines, $accounts), 'operation_id' => $entry->origin_operation_id,
            'postings' => array_map(fn (LedgerLine $line): array => ['account' => $this->holder($accounts[$line->account_id]).' · '.$accounts[$line->account_id]->kind,
                'account_code' => $accounts[$line->account_id]->kind, 'debit' => $line->direction === 'debit' ? self::money($line->amount) : null,
                'credit' => $line->direction === 'credit' ? self::money($line->amount) : null], $lines),
            'totals' => ['debit' => self::money((string) $debit), 'credit' => self::money((string) $credit)], 'balanced' => $debit->isEqualTo($credit)];
    }

    /** @param Builder<LedgerEntry> $query
     * @return Builder<LedgerEntry>
     */
    private function matching(Builder $query, string $search): Builder
    {
        $term = strtolower((string) preg_replace('/^(RZL|INV|BUS)-/i', '', trim($search)));
        $like = '%'.addcslashes($term, '%_\\').'%';
        $kinds = array_keys(array_filter(self::KINDS, fn (string $kind): bool => str_contains($kind, $term)));

        return $query->where(fn (Builder $query): Builder => $query->where('source_id', 'like', $like)->orWhere('id', 'like', $like)
            ->orWhere('wallet_id', 'like', $like)->orWhereIn('kind', $kinds));
    }

    /**
     * Each entry's lines in posting order, and the accounts they post to by id.
     *
     * @param  list<string>  $entryIds
     * @return array{array<string, list<LedgerLine>>, array<string, LedgerAccount>}
     */
    private function lines(array $entryIds): array
    {
        $grouped = [];
        foreach (LedgerLine::query()->whereIn('entry_id', $entryIds)->orderBy('id')->get() as $line) {
            $grouped[$line->entry_id][] = $line;
        }
        $accounts = [];
        $ids = array_unique(array_map(fn (LedgerLine $line): string => $line->account_id, array_merge([], ...array_values($grouped))));
        foreach (LedgerAccount::query()->whereIn('id', $ids)->get() as $account) {
            $accounts[(string) $account->id] = $account;
        }

        return [$grouped, $accounts];
    }

    /**
     * @param  list<LedgerLine>  $lines
     * @param  array<string, LedgerAccount>  $accounts
     * @return array{id: string, at: string, kind: string, from: string, to: string, reference: string, amount: array{currency: string, amount: string}}
     */
    private function row(LedgerEntry $entry, array $lines, array $accounts): array
    {
        $side = fn (string $direction): string => implode(' + ', array_values(array_unique(array_map(fn (LedgerLine $line): string => $this->holder($accounts[$line->account_id]),
            array_filter($lines, fn (LedgerLine $line): bool => $line->direction === $direction)))));

        return ['id' => $entry->id, 'at' => $entry->created_at?->toIso8601String() ?? '',
            'kind' => self::KINDS[$entry->kind] ?? throw new RuntimeException('LEDGER_KIND_UNKNOWN'), 'from' => $side('debit'), 'to' => $side('credit'),
            'reference' => 'RZL-'.strtoupper(substr($entry->source_id, -10)), 'amount' => self::money((string) $this->sum($lines, 'debit'))];
    }

    private function holder(LedgerAccount $account): string
    {
        if ($account->wallet_id === null) {
            return 'ROZINE';
        }

        return ($account->getAttribute('wallet_owner') === 'business' ? 'BUS-' : 'INV-').strtoupper(substr($account->wallet_id, -8));
    }

    /** @param list<LedgerLine> $lines */
    private function sum(array $lines, string $direction): BigInteger
    {
        $total = BigInteger::zero();
        foreach ($lines as $line) {
            if ($line->direction === $direction) {
                $total = $total->plus($line->amount);
            }
        }

        return $total;
    }

    /** @return array{currency: string, amount: string} */
    private static function money(string $amount): array
    {
        return ['currency' => 'RWF', 'amount' => $amount];
    }
}
