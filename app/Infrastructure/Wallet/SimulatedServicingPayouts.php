<?php

declare(strict_types=1);

namespace App\Infrastructure\Wallet;

use App\Application\Business\Contracts\ServicingPayouts;
use App\Application\Environment\UatMoneySimulation;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Application\Primary\Contracts\RetainedNoteSchedules;
use App\Application\Primary\RetainedHoldingSchedule;
use App\Application\Secondary\Contracts\NoteUnitHolders;
use App\Application\Wallet\Contracts\WalletPostings;
use App\Models\BusinessRepayment;
use App\Models\LedgerEntry;
use App\Models\LedgerLine;
use Brick\Math\BigInteger;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;
use RuntimeException;

/**
 * Investor payouts on UAT while its money simulation is on (Erastus, 14 Nov 2026), until the
 * servicing domain (S4-C) replaces it. A repayment pays whole instalments, so its principal and
 * return are shared over the note's units as they are held when it is paid on (SEC-R07: a sold unit
 * pays its buyer), each lot by its units with the largest remainder going first, ties to the earlier
 * lot. The earnings fee is the rate the units' original Holding locked at checkout (INV-R04) on the
 * lot's return, half up (FEE-R01); principal is never charged. Each lot is one `servicing_payouts` row
 * and one `servicing_payout` entry: repayment clearing pays the gross, the holder's available balance
 * gets the net and `earnings_fee_revenue` the fee. Wallets lock in Party id order.
 */
final readonly class SimulatedServicingPayouts implements ServicingPayouts
{
    public const string POLICY_VERSION = 'uat-simulation-v1';

    public function __construct(private RetainedNoteSchedules $schedules, private NoteUnitHolders $holders, private WalletPostings $wallets,
        private CanonicalJson $json, private UatMoneySimulation $simulation) {}

    public function payOut(string $repaymentId): int
    {
        $this->assertSimulating();

        return DB::transaction(function () use ($repaymentId): int {
            $repayment = BusinessRepayment::query()->find($repaymentId);
            if ($repayment === null) {
                return 0;
            }
            DB::select('SELECT pg_advisory_xact_lock(hashtextextended(?, 0))', ['servicing-note:'.$repayment->note_id]);
            if (DB::table('servicing_payouts')->where('repayment_id', $repayment->id)->exists()) {
                return 0;
            }
            // Campaign IDs are stored in upper case; the repayment carries the lower-case form.
            $campaignId = (string) (DB::table('business_campaigns')->whereRaw('lower(id) = ?', [$repayment->note_id])->value('id')
                ?? throw new RuntimeException('SERVICING_NOTE_NOT_ISSUED'));
            $schedule = $this->schedules->find($campaignId) ?? throw new RuntimeException('SERVICING_NOTE_NOT_ISSUED');
            /** @var list<int> $indexes */
            $indexes = $repayment->payload['instalment_indexes'];
            $principal = BigInteger::zero();
            $return = BigInteger::zero();
            foreach ($schedule->instalments as $instalment) {
                if (in_array($instalment->index, $indexes, true)) {
                    $principal = $principal->plus($instalment->principal);
                    $return = $return->plus($instalment->contractualReturn);
                }
            }
            $lots = $this->holders->lots($campaignId);
            $units = array_map(fn (array $lot): BigInteger => BigInteger::of($lot['units']), $lots);
            $principals = self::share($principal, $units);
            $returns = self::share($return, $units);

            $locked = [];
            $parties = array_values(array_unique(array_map(fn (array $lot): string => $lot['party_id'], $lots)));
            sort($parties);
            foreach ($parties as $party) {
                $locked[$party] = $this->wallets->lockForParty($party);
            }
            $paid = 0;
            foreach ($lots as $position => $lot) {
                $rate = $this->rate($schedule->holdings, (int) $lot['ordinals'][0]['first']);
                $fee = $returns[$position]->multipliedBy($rate)->dividedBy(10000, RoundingMode::HalfUp);
                // A lot whose share comes to nothing is refused by `servicing_payout_net`, so the sweep retries it loudly.
                $net = $principals[$position]->plus($returns[$position])->minus($fee);
                $this->post($repayment, $lot, $locked[$lot['party_id']]->walletId, $indexes, $principals[$position], $returns[$position], $fee, $net, $rate);
                $paid++;
            }
            DB::statement('SET CONSTRAINTS ledger_entries_balanced, ledger_lines_entry_balanced, ledger_entries_servicing_payout_bound, ledger_lines_entry_servicing_payout_bound, servicing_payouts_posted IMMEDIATE');
            DB::statement('SET CONSTRAINTS ledger_entries_balanced, ledger_lines_entry_balanced, ledger_entries_servicing_payout_bound, ledger_lines_entry_servicing_payout_bound, servicing_payouts_posted DEFERRED');

            return $paid;
        });
    }

    public function unpaid(int $limit): array
    {
        $this->assertSimulating();

        return array_values(BusinessRepayment::query()->whereNotExists(fn ($query) => $query->selectRaw('1')->from('servicing_payouts')
            ->whereColumn('servicing_payouts.repayment_id', 'business_repayments.id'))->orderBy('id')->limit($limit)->pluck('id')
            ->map(fn (mixed $id): string => (string) $id)->all());
    }

    /**
     * Shares an amount over the lots by units: each gets its floor, then the largest remainders one
     * RWF each, ties to the earlier lot.
     *
     * @param  list<BigInteger>  $units
     * @return list<BigInteger>
     */
    private static function share(BigInteger $amount, array $units): array
    {
        $total = array_reduce($units, fn (BigInteger $sum, BigInteger $lot): BigInteger => $sum->plus($lot), BigInteger::zero());
        $shares = [];
        $remainders = [];
        $left = $amount;
        foreach ($units as $position => $lot) {
            [$share, $remainder] = $amount->multipliedBy($lot)->quotientAndRemainder($total);
            $shares[$position] = $share;
            $remainders[$position] = $remainder;
            $left = $left->minus($share);
        }
        $order = array_keys($remainders);
        usort($order, fn (int $left, int $right): int => $remainders[$right]->compareTo($remainders[$left]) ?: $left <=> $right);
        foreach (array_slice($order, 0, $left->toInt()) as $position) {
            $shares[$position] = $shares[$position]->plus(1);
        }

        return array_values($shares);
    }

    /**
     * The earnings fee rate the Holding these units were issued in locked at checkout.
     *
     * @param  list<RetainedHoldingSchedule>  $holdings
     */
    private function rate(array $holdings, int $ordinal): int
    {
        foreach ($holdings as $holding) {
            foreach ($holding->ordinals as $range) {
                if ($ordinal >= $range['first'] && $ordinal <= $range['last']) {
                    /** @var array{earnings_fee?: array{rate_bps?: int}} $terms */
                    $terms = $holding->terms;

                    return (int) ($terms['earnings_fee']['rate_bps'] ?? 0);
                }
            }
        }

        throw new RuntimeException('SERVICING_LOT_NOT_ISSUED');
    }

    /**
     * @param  array{lot_type: string, lot_id: string, party_id: string, units: string}  $lot
     * @param  list<int>  $indexes
     */
    private function post(BusinessRepayment $repayment, array $lot, string $walletId, array $indexes, BigInteger $principal, BigInteger $return,
        BigInteger $fee, BigInteger $net, int $rate): void
    {
        $recordedAt = now('UTC')->toImmutable()->startOfSecond();
        $payoutId = strtolower((string) Str::ulid());
        DB::table('servicing_payouts')->insert(['id' => $payoutId, 'repayment_id' => $repayment->id, 'note_id' => $repayment->note_id,
            'lot_type' => $lot['lot_type'], 'lot_id' => $lot['lot_id'], 'party_id' => $lot['party_id'], 'wallet_id' => $walletId, 'units' => $lot['units'],
            'instalment_indexes' => json_encode($indexes, JSON_THROW_ON_ERROR), 'principal' => (string) $principal, 'contractual_return' => (string) $return,
            'fee' => (string) $fee, 'net' => (string) $net, 'rate_bps' => $rate, 'policy_version' => self::POLICY_VERSION, 'created_at' => $recordedAt]);
        $lines = [['repayment_clearing', 'debit', $principal->plus($return)], ['investor_available', 'credit', $net]];
        if ($fee->isPositive()) {
            $lines[] = ['earnings_fee_revenue', 'credit', $fee];
        }
        $entry = new LedgerEntry;
        $entry->id = strtolower((string) Str::ulid());
        $payload = ['entry_id' => $entry->id, 'wallet_id' => $walletId, 'kind' => 'servicing_payout', 'source_type' => 'servicing_payout',
            'source_id' => $payoutId, 'origin_operation_id' => $repayment->operation_id, 'cause_type' => 'business_repayment', 'cause_id' => $repayment->id,
            'lines' => array_map(fn (array $line): array => ['account' => $line[0], 'direction' => $line[1], 'amount' => (string) $line[2]], $lines),
            'recorded_at' => $recordedAt->toIso8601String()];
        $entry->forceFill(['wallet_id' => $walletId, 'kind' => 'servicing_payout', 'source_type' => 'servicing_payout', 'source_id' => $payoutId,
            'origin_operation_id' => $repayment->operation_id, 'cause_type' => 'business_repayment', 'cause_id' => $repayment->id, 'currency' => 'RWF',
            'payload' => $payload, 'sha256' => hash('sha256', $this->json->encode($payload)), 'created_at' => $recordedAt])->save();
        foreach ($lines as [$account, $direction, $amount]) {
            (new LedgerLine)->forceFill(['entry_id' => $entry->id, 'account_id' => $this->account($account, $walletId),
                'direction' => $direction, 'amount' => (string) $amount, 'created_at' => $recordedAt])->save();
        }
    }

    private function account(string $kind, string $walletId): string
    {
        if ($kind !== 'investor_available') {
            DB::insert("INSERT INTO ledger_accounts (id, wallet_id, kind, currency, created_at) VALUES (?, NULL, ?, 'RWF', now())
                ON CONFLICT (kind) WHERE wallet_id IS NULL DO NOTHING", [strtolower((string) Str::ulid()), $kind]);

            return (string) DB::table('ledger_accounts')->whereNull('wallet_id')->where('kind', $kind)->value('id');
        }
        DB::insert("INSERT INTO ledger_accounts (id, wallet_id, kind, currency, created_at) VALUES (?, ?, ?, 'RWF', now())
            ON CONFLICT (wallet_id, kind) WHERE wallet_id IS NOT NULL DO NOTHING", [strtolower((string) Str::ulid()), $walletId, $kind]);

        return (string) DB::table('ledger_accounts')->where('wallet_id', $walletId)->where('kind', $kind)->value('id');
    }

    private function assertSimulating(): void
    {
        if (! $this->simulation->active()) {
            throw new LogicException('UAT_SIMULATION_ONLY: simulated payouts run only on UAT with their money simulation on.');
        }
    }
}
