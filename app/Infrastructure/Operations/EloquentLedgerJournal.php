<?php

declare(strict_types=1);

namespace App\Infrastructure\Operations;

use App\Application\Operations\Contracts\LedgerJournal;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use stdClass;

/**
 * Reads `ledger_entries` with their `ledger_lines` and `ledger_accounts`. A wallet account's owner
 * is the Investor wallet's Party (named by its first account) or the Business wallet's profile. The
 * reference comes from the entry's source record: the deposit intent's or withdrawal's provider
 * reference (sealed at rest, so it is opened here and searched only by its exact SHA-256), or the
 * Business behind a primary reservation, commitment or repayment.
 *
 * @phpstan-import-type Entry from LedgerJournal
 * @phpstan-import-type Line from LedgerJournal
 */
final readonly class EloquentLedgerJournal implements LedgerJournal
{
    /** The SQL reading one column (`%1$s`) of the entry's payment record, whose provider reference is sealed at rest. */
    private const string SEALED = <<<'SQL'
        CASE ledger_entries.source_type
            WHEN 'wallet_deposit_intent' THEN (SELECT %1$s FROM wallet_deposit_intents WHERE id = ledger_entries.source_id)
            WHEN 'business_deposit_intent' THEN (SELECT %1$s FROM business_deposit_intents WHERE id = ledger_entries.source_id)
            WHEN 'wallet_withdrawal' THEN (SELECT %1$s FROM wallet_withdrawals WHERE id = ledger_entries.source_id)
            WHEN 'business_withdrawal' THEN (SELECT %1$s FROM business_withdrawals WHERE id = ledger_entries.source_id)
        END
        SQL;

    /** The SQL naming the Business a purchase, issue or repayment moved under. */
    private const string BUSINESS = <<<'SQL'
        CASE ledger_entries.source_type
            WHEN 'primary_reservation' THEN (SELECT business_profiles.profile->>'name' FROM primary_reservations
                JOIN business_campaigns ON business_campaigns.id = primary_reservations.business_campaign_id
                JOIN business_profiles ON business_profiles.id = business_campaigns.business_id WHERE primary_reservations.id = ledger_entries.source_id)
            WHEN 'primary_commitment' THEN (SELECT business_profiles.profile->>'name' FROM primary_commitments
                JOIN primary_reservations ON primary_reservations.id = primary_commitments.primary_reservation_id
                JOIN business_campaigns ON business_campaigns.id = primary_reservations.business_campaign_id
                JOIN business_profiles ON business_profiles.id = business_campaigns.business_id WHERE primary_commitments.id = ledger_entries.source_id)
            WHEN 'business_repayment' THEN (SELECT business_profiles.profile->>'name' FROM business_repayments
                JOIN business_profiles ON business_profiles.id = business_repayments.business_id WHERE business_repayments.id = ledger_entries.source_id)
        END
        SQL;

    /** The SQL naming a wallet's owner. */
    private const string OWNER = <<<'SQL'
        COALESCE(
            (SELECT users.name FROM investor_wallets JOIN users ON users.party_id = investor_wallets.party_id
                WHERE investor_wallets.id = %1$s ORDER BY users.id LIMIT 1),
            (SELECT business_profiles.profile->>'name' FROM business_wallets
                JOIN business_profiles ON business_profiles.id = business_wallets.business_id WHERE business_wallets.id = %1$s))
        SQL;

    public function entries(?string $search, int $limit): array
    {
        $matching = DB::table('ledger_entries')->when($search !== null, function (Builder $query) use ($search): void {
            $like = '%'.addcslashes(mb_strtolower((string) $search), '%_\\').'%';
            $query->where(fn (Builder $match) => $match->whereRaw('lower(ledger_entries.id) like ?', [$like])
                ->orWhereRaw('lower(ledger_entries.kind) like ?', [$like])
                ->orWhereRaw('lower(('.self::BUSINESS.')) like ?', [$like])
                ->orWhereRaw('('.sprintf(self::SEALED, 'provider_reference_sha256').') = ?', [hash('sha256', trim((string) $search))])
                ->orWhereRaw('lower(('.sprintf(self::OWNER, 'ledger_entries.wallet_id').')) like ?', [$like]));
        });
        $rows = $this->select(clone $matching)->orderByDesc('ledger_entries.id')->limit(max(1, $limit))->get();

        return ['entries' => $this->shape($rows->all()), 'total' => $matching->count()];
    }

    public function entry(string $id): ?array
    {
        return $this->shape($this->select(DB::table('ledger_entries')->where('ledger_entries.id', $id))->get()->all())[0] ?? null;
    }

    private function select(Builder $query): Builder
    {
        return $query->select(['ledger_entries.id', 'ledger_entries.kind', 'ledger_entries.created_at', 'ledger_entries.origin_operation_id'])
            ->selectRaw('('.self::BUSINESS.') AS business')->selectRaw('('.sprintf(self::SEALED, 'provider_reference').') AS sealed_reference');
    }

    /**
     * @param  array<int, stdClass>  $rows
     * @return list<Entry>
     */
    private function shape(array $rows): array
    {
        $ids = array_map(fn (stdClass $row): string => (string) $row->id, $rows);
        $lines = DB::table('ledger_lines')->join('ledger_accounts', 'ledger_accounts.id', '=', 'ledger_lines.account_id')
            ->leftJoin('wallets', 'wallets.id', '=', 'ledger_accounts.wallet_id')
            ->whereIn('ledger_lines.entry_id', $ids)
            ->select(['ledger_lines.entry_id', 'ledger_lines.direction', 'ledger_lines.account_id', 'ledger_accounts.kind', 'wallets.owner'])
            ->selectRaw('ledger_lines.amount::text AS amount')
            ->selectRaw('('.sprintf(self::OWNER, 'ledger_accounts.wallet_id').') AS owner_name')
            ->orderBy('ledger_lines.id')->get()->groupBy('entry_id');

        $entries = [];
        foreach ($rows as $row) {
            $entries[] = ['id' => (string) $row->id, 'kind' => (string) $row->kind,
                'at' => CarbonImmutable::parse((string) $row->created_at)->utc()->toIso8601String(),
                'reference' => match (true) {
                    $row->sealed_reference !== null => Crypt::decryptString((string) $row->sealed_reference),
                    $row->business !== null => (string) $row->business,
                    default => null,
                },
                'operation_id' => $row->origin_operation_id === null ? null : (string) $row->origin_operation_id,
                'lines' => array_values(array_map($this->line(...), $lines->get((string) $row->id)?->all() ?? []))];
        }

        return $entries;
    }

    /** @return Line */
    private function line(stdClass $line): array
    {
        return ['direction' => $line->direction === 'credit' ? 'credit' : 'debit', 'amount' => (string) $line->amount,
            'account_id' => (string) $line->account_id, 'account_kind' => (string) $line->kind,
            'owner' => match ($line->owner) {
                'investor' => 'investor', 'business' => 'business', default => null
            },
            'owner_name' => $line->owner_name === null ? null : (string) $line->owner_name];
    }
}
