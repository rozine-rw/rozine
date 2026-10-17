<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Brick\Math\BigInteger;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `AdminLedgerProps` from the live wallet ledger. Each row names its kind as the console groups
 * them, where the money came from (the largest debit line's account) and went to (the largest
 * credit line's), the reference it moved under and its amount (the entry's debits). The opened
 * entry lists every line as a posting with its account and code, the totals and whether they
 * balance, and who started its money flow (`origin`), which is not who posted it. No posting has
 * ever been corrected, so no entry links a contra. `older` pages back from the last row shown and
 * `newest` returns to the first page.
 *
 * @phpstan-import-type Entry from \App\Application\Operations\Contracts\LedgerJournal
 * @phpstan-import-type Line from \App\Application\Operations\Contracts\LedgerJournal
 * @phpstan-import-type OpenedEntry from \App\Application\Operations\BrowseLedger
 *
 * @phpstan-type Page array{
 *     entries: list<Entry>, total: int, next_before: string|null, entry: OpenedEntry|null,
 *     search: string, limit: int, before: string|null, roles: list<string>, permissions: list<string>
 * }
 */
class StaffLedgerResource extends JsonResource
{
    /** The most entries one page shows; "see all" opens this many. */
    public const MAX = 500;

    /** How the console groups each recorded entry kind. */
    private const KINDS = [
        'deposit_credit' => 'deposit', 'business_deposit_credit' => 'deposit',
        'withdrawal_hold' => 'withdrawal', 'withdrawal_payout' => 'withdrawal', 'withdrawal_return' => 'withdrawal',
        'business_withdrawal_hold' => 'withdrawal', 'business_withdrawal_payout' => 'withdrawal', 'business_withdrawal_return' => 'withdrawal',
        'primary_hold' => 'investment', 'primary_commit' => 'investment', 'primary_release' => 'investment', 'primary_refund' => 'investment',
        'primary_issue' => 'disbursement', 'business_repayment_debit' => 'repayment',
    ];

    /** Each platform account's name and code. */
    private const SYSTEM = [
        'deposit_clearing' => ['Deposit clearing (provider)', 'DEP-CLR'], 'deposit_fee_revenue' => ['Deposit fee revenue', 'DEP-FEE'],
        'disbursement_settlement' => ['Disbursement settlement', 'DSB-SET'], 'repayment_clearing' => ['Repayment clearing', 'RPY-CLR'],
        'withdrawal_clearing' => ['Withdrawal clearing (provider)', 'WDR-CLR'],
    ];

    /** Each wallet bucket's name and code. */
    private const BUCKETS = [
        'investor_available' => ['available', 'INV-AVL'], 'investor_held' => ['held', 'INV-HLD'], 'investor_committed' => ['committed', 'INV-CMT'],
        'investor_withdrawing' => ['withdrawing', 'INV-WDR'], 'business_available' => ['available', 'BIZ-AVL'], 'business_withdrawing' => ['withdrawing', 'BIZ-WDR'],
    ];

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var Page $page */
        $page = $this->resource;
        $prefix = $request->routeIs('api.*') ? 'api.v1.' : '';
        $query = array_filter(['q' => $page['search'], 'limit' => $page['limit'] === 50 ? null : $page['limit'], 'before' => $page['before']],
            fn (string|int|null $value): bool => $value !== null && $value !== '');
        $first = array_diff_key($query, ['before' => true]);
        $link = fn (array $parameters): array => ['url' => route($prefix.'staff.ledger.index', $parameters, false), 'method' => 'get'];
        $more = $page['total'] > count($page['entries']) && $page['limit'] < self::MAX
            ? $link([...array_diff_key($first, ['limit' => true]), 'limit' => self::MAX]) : null;
        $entry = $page['entry'];

        return ['contract_version' => 'staff-ledger-v1', 'server_time' => now()->toIso8601String(),
            'viewer' => (new StaffViewerResource($page['roles']))->resolve($request),
            'nav' => (new StaffNavigationResource($page['permissions']))->resolve($request),
            'badges' => ['applications' => null, 'disbursements' => null], 'search' => $page['search'],
            'entries' => array_map(fn (array $row): array => self::row($row, $link([...$query, 'entry' => $row['id']])), $page['entries']),
            'total' => $page['total'], 'more' => $page['before'] === null ? $more : null,
            'older' => $page['next_before'] === null ? null : $link([...$first, 'before' => $page['next_before']]),
            'newest' => $page['before'] === null ? null : $link($first),
            'entry' => $entry === null ? null : [...self::row($entry, $link([...$query, 'entry' => $entry['id']])),
                'operation_id' => $entry['operation_id'] ?? $entry['id'],
                'posted_by' => ['actor' => 'System · '.str_replace('_', ' ', $entry['kind']), 'at' => $entry['at'], 'reason' => null],
                'origin' => $entry['origin'],
                'postings' => array_map(fn (array $line): array => ['account' => self::account($line), 'account_code' => self::code($line),
                    'debit' => $line['direction'] === 'debit' ? self::money($line['amount']) : null,
                    'credit' => $line['direction'] === 'credit' ? self::money($line['amount']) : null], $entry['lines']),
                'totals' => ['debit' => self::money(self::sum($entry['lines'], 'debit')), 'credit' => self::money(self::sum($entry['lines'], 'credit'))],
                'balanced' => self::sum($entry['lines'], 'debit') === self::sum($entry['lines'], 'credit'),
                'contra_of' => null, 'contra_by' => null,
                'links' => ['close' => $link($query), 'events' => ['url' => route($prefix.'staff.events.index', ['q' => $entry['operation_id'] ?? $entry['id']], false), 'method' => 'get']]]];
    }

    /**
     * One entry as a ledger row (and a Today activity item's facts).
     *
     * @param  Entry  $entry
     * @param  array{url: string, method: string}  $link
     * @return array{id: string, at: string, kind: string, from: string, to: string, reference: string, amount: array{currency: string, amount: string}, link: array{url: string, method: string}}
     */
    public static function row(array $entry, array $link): array
    {
        return ['id' => $entry['id'], 'at' => $entry['at'], 'kind' => self::KINDS[$entry['kind']] ?? 'investment',
            'from' => self::largest($entry['lines'], 'debit'), 'to' => self::largest($entry['lines'], 'credit'),
            'reference' => $entry['reference'] ?? $entry['id'], 'amount' => self::money(self::sum($entry['lines'], 'debit')), 'link' => $link];
    }

    /** @param  list<Line>  $lines */
    private static function largest(array $lines, string $direction): string
    {
        $side = array_values(array_filter($lines, fn (array $line): bool => $line['direction'] === $direction));
        usort($side, fn (array $left, array $right): int => BigInteger::of($right['amount'])->compareTo($left['amount']));

        return $side === [] ? '—' : self::account($side[0]);
    }

    /** @param  Line  $line */
    private static function account(array $line): string
    {
        return isset(self::BUCKETS[$line['account_kind']])
            ? ($line['owner_name'] ?? ucfirst((string) $line['owner'])).' · '.self::BUCKETS[$line['account_kind']][0]
            : (self::SYSTEM[$line['account_kind']][0] ?? ucfirst(str_replace('_', ' ', $line['account_kind'])));
    }

    /** @param  Line  $line */
    private static function code(array $line): string
    {
        return isset(self::BUCKETS[$line['account_kind']])
            ? self::BUCKETS[$line['account_kind']][1].'-'.mb_strtoupper(mb_substr($line['account_id'], -6))
            : (self::SYSTEM[$line['account_kind']][1] ?? mb_strtoupper($line['account_kind']));
    }

    /** @param  list<Line>  $lines */
    private static function sum(array $lines, string $direction): string
    {
        return (string) array_reduce($lines, fn (BigInteger $total, array $line): BigInteger => $line['direction'] === $direction ? $total->plus($line['amount']) : $total,
            BigInteger::zero());
    }

    /** @return array{currency: string, amount: string} */
    private static function money(string $amount): array
    {
        return ['currency' => 'RWF', 'amount' => $amount];
    }
}
