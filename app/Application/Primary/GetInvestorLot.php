<?php

declare(strict_types=1);

namespace App\Application\Primary;

use App\Application\Primary\Contracts\HoldingPayouts;
use App\Domain\Operations\CommandRejection;
use Brick\Math\BigInteger;
use Carbon\CarbonImmutable;

/**
 * One lot of note units the authenticated Investor bought on the market and still holds (QA #663):
 * the price paid and what it cost, what servicing has paid on to it since the purchase
 * (`HoldingPayouts`, lot type `secondary_transfer`), and the note's schedule scaled to the units
 * held. An instalment the lot was paid is `paid`; one that fell due before the purchase went to the
 * seller (`before_purchase`), as did one the note paid early before the purchase; the rest are `upcoming`, each at what it will pay net of the earnings fee.
 *
 * @phpstan-import-type Money from GetInvestorPortfolio
 *
 * @phpstan-type LotInstalment array{index: int, due_on: string, expected: Money, status: 'paid'|'before_purchase'|'upcoming', paid_on: string|null, net: Money|null}
 * @phpstan-type Lot array{lot_id: string, note_id: string, name: string, accent: string, industry: string, units: string, price: Money, paid: Money, bought_at: string,
 *     received: Money, payments_made: int, payments_total: int, next_payment: array{amount: Money, due_on: string}|null, matures_on: string, repaid: bool, schedule: list<LotInstalment>}
 * @phpstan-type LotFacts array{identity_context_revision: int, lot: Lot}
 */
final class GetInvestorLot
{
    public function __construct(private GetInvestorViewer $viewer, private GetInvestorPortfolio $portfolio, private HoldingPayouts $paid) {}

    /**
     * Refuses `LOT_NOT_FOUND` (404) for an unknown lot, another Party's, or one wholly resold.
     *
     * @return LotFacts
     */
    public function handle(int $userId, ?int $contextRevision, string $lotId): array
    {
        $viewer = $this->viewer->handle($userId, $contextRevision);
        $lots = $viewer['wallet'] === null ? [] : $this->portfolio->bought($userId);
        $lot = array_values(array_filter($lots, fn (array $lot): bool => $lot['lot_id'] === $lotId))[0] ?? throw new CommandRejection('LOT_NOT_FOUND', 404);
        $position = $this->portfolio->boughtPosition($lot);
        $feeBps = $position['holding']['terms']['earnings_fee']['rate_bps'];
        $payouts = $this->paid->forLots('secondary_transfer', [$lot['lot_id']])[$lot['lot_id']] ?? [];
        $paidRows = [];
        foreach ($payouts as $payout) {
            foreach ($payout['instalments'] as $place => $index) {
                $paidRows[$index] = ['paid_on' => CarbonImmutable::parse($payout['recorded_at'])->setTimezone(HoldingFigures::ZONE)->format('Y-m-d'),
                    'net' => HoldingFigures::money($place === 0 ? $payout['net'] : '0')];
            }
        }
        $boughtOn = CarbonImmutable::parse($lot['bought_at'])->setTimezone(HoldingFigures::ZONE)->format('Y-m-d');
        $notePaid = array_fill_keys([...array_keys($paidRows), ...array_merge([], ...array_map(fn (array $payout): array => $payout['instalments'], $position['paid']))], true);
        $schedule = array_map(fn (array $row): array => ['index' => $row['index'], 'due_on' => $row['due_on'], 'expected' => HoldingFigures::net($row, $feeBps),
            'status' => isset($paidRows[$row['index']]) ? 'paid' : (isset($notePaid[$row['index']]) || $row['due_on'] < $boughtOn ? 'before_purchase' : 'upcoming'),
            'paid_on' => $paidRows[$row['index']]['paid_on'] ?? null, 'net' => $paidRows[$row['index']]['net'] ?? null], $position['holding']['schedule']);
        $next = array_values(array_filter($schedule, fn (array $row): bool => $row['status'] === 'upcoming'))[0] ?? null;
        $received = array_reduce($payouts, fn (BigInteger $sum, array $payout): BigInteger => $sum->plus($payout['net']), BigInteger::zero());

        return ['identity_context_revision' => $viewer['identity_context_revision'], 'lot' => [...$lot, 'received' => HoldingFigures::money($received),
            'payments_made' => count($paidRows), 'payments_total' => count($schedule),
            'next_payment' => $next === null ? null : ['amount' => $next['expected'], 'due_on' => HoldingFigures::kigaliMidnight($next['due_on'])],
            'matures_on' => HoldingFigures::kigaliMidnight($schedule[count($schedule) - 1]['due_on']), 'repaid' => count($notePaid) >= count($schedule), 'schedule' => $schedule]];
    }
}
