<?php

declare(strict_types=1);

namespace App\Application\Primary;

use App\Application\Business\Contracts\InvestorDealCatalogue;
use App\Application\Primary\Contracts\InvestorHoldings;
use Brick\Math\BigInteger;
use Brick\Math\RoundingMode;
use RuntimeException;

/**
 * The Portfolio facts for the authenticated Investor (C3 v2 §2d), read under current Investor
 * authority through `GetInvestorViewer`: the cash still idle in the wallet and every Holding the
 * primary issue gave them, with the business it is in (`HoldingFigures`). Payouts are the
 * scheduled instalments still due, net of the earnings fee and labelled as a projection; nothing
 * has been received until servicing pays it, so every gain is zero.
 *
 * @phpstan-import-type HeldPosition from HoldingFigures
 * @phpstan-import-type Card from HoldingFigures
 *
 * @phpstan-type Money array{currency: string, amount: string}
 * @phpstan-type Summary array{id: string, name: string, accent: string, industry: string, district: string, health: 'healthy', value: Money,
 *     invested: Money, gain: Money, gain_pct: string, repaid_pct: int, matures_on: string, payments_made: int, payments_total: int}
 * @phpstan-type PayoutMonth array{month: string, amount: Money, bar_pct: int, payers: list<array{name: string, accent: string, amount: Money}>}
 * @phpstan-type PortfolioFacts array{identity_context_revision: int, verified: bool, idle: Money|null, holdings: list<Summary>,
 *     totals: array{businesses: int, value: Money, invested: Money, gain: Money, this_month: Money, projected_3m: Money,
 *         next_payout: array{amount: Money, month: string}|null, avg_monthly: Money},
 *     payouts: list<PayoutMonth>, industries: list<array{name: string, accent: string, amount: Money, share_pct: int}>,
 *     risk: list<array{band: string, share_pct: int}>,
 *     concentration: array{status: 'concentrated', business: string, share_pct: int}|array{status: 'balanced'}|null}
 */
final class GetInvestorPortfolio
{
    public const array TABS = ['active', 'matured', 'secondary', 'saved'];

    public function __construct(private GetInvestorViewer $viewer, private InvestorHoldings $holdings, private InvestorDealCatalogue $catalogue) {}

    /**
     * Idle cash is the available wallet bucket as the ledger reports it, or null when there is
     * none; a person still being verified has no wallet, and so no Holdings, yet.
     *
     * @return PortfolioFacts
     */
    public function handle(int $userId, ?int $contextRevision): array
    {
        $viewer = $this->viewer->handle($userId, $contextRevision);
        /** @var Money|null $available */
        $available = $viewer['wallet']['wallet']['breakdown']['available'] ?? null;
        $positions = $viewer['wallet'] === null ? [] : $this->positions($userId);
        $zero = HoldingFigures::money('0');
        $invested = array_reduce($positions, fn (BigInteger $sum, array $position): BigInteger => $sum->plus($position['holding']['principal']['amount']), BigInteger::zero());
        $payouts = $this->payouts($positions);
        $projected = array_reduce(array_slice($payouts, 0, 3), fn (BigInteger $sum, array $month): BigInteger => $sum->plus($month['amount']['amount']), BigInteger::zero());

        return ['identity_context_revision' => $viewer['identity_context_revision'], 'verified' => $viewer['wallet'] !== null,
            'idle' => $available === null || $available['amount'] === '0' ? null : $available,
            'holdings' => array_map(fn (array $position): array => self::summary($position), $positions),
            'totals' => ['businesses' => count(array_unique(array_map(fn (array $position): string => $position['holding']['campaign_id'], $positions))),
                'value' => HoldingFigures::money($invested), 'invested' => HoldingFigures::money($invested), 'gain' => $zero, 'this_month' => $zero,
                'projected_3m' => HoldingFigures::money($projected),
                'next_payout' => $payouts === [] ? null : ['amount' => $payouts[0]['amount'], 'month' => $payouts[0]['month']],
                'avg_monthly' => HoldingFigures::money($projected->dividedBy(3, RoundingMode::HalfUp))],
            'payouts' => $payouts, 'industries' => $this->industries($positions, $invested), 'risk' => $this->risk($positions, $invested),
            'concentration' => $this->concentration($positions, $invested)];
    }

    /**
     * One Holding as the Portfolio and the Holding page list it.
     *
     * @param  HeldPosition  $position
     * @return Summary
     */
    public static function summary(array $position): array
    {
        $holding = $position['holding'];
        $card = $position['card'];
        $schedule = $holding['schedule'];

        return ['id' => $holding['id'], 'name' => $card['name'], 'accent' => $card['accent'], 'industry' => $card['industry'], 'district' => $card['district'],
            'health' => 'healthy', 'value' => $holding['principal'], 'invested' => $holding['principal'], 'gain' => HoldingFigures::money('0'), 'gain_pct' => '0.0',
            'repaid_pct' => 0, 'matures_on' => HoldingFigures::kigaliMidnight($schedule[count($schedule) - 1]['due_on']), 'payments_made' => 0,
            'payments_total' => count($schedule)];
    }

    /**
     * The business card of a held campaign. An issued campaign was published with a rating, so a
     * missing card is an integrity failure, never an unnamed Holding.
     *
     * @return Card
     */
    public function card(string $campaignId): array
    {
        /** @var Card|null $card */
        $card = $this->catalogue->card($campaignId);

        return $card ?? throw new RuntimeException('HOLDING_PRESENTATION_UNAVAILABLE');
    }

    /** @return list<HeldPosition> */
    private function positions(int $userId): array
    {
        $cards = [];

        return array_map(function (array $holding) use (&$cards): array {
            $cards[$holding['campaign_id']] ??= $this->card($holding['campaign_id']);

            return ['holding' => $holding, 'card' => $cards[$holding['campaign_id']]];
        }, $this->holdings->forInvestor($userId));
    }

    /**
     * The projected instalments grouped by Kigali month, earliest first, each split by business.
     *
     * @param  list<HeldPosition>  $positions
     * @return list<PayoutMonth>
     */
    private function payouts(array $positions): array
    {
        $months = [];
        foreach (HoldingFigures::upcoming($positions, now()) as $instalment) {
            $month = substr($instalment['due_on'], 0, 7).'-01';
            $months[$month] ??= ['total' => BigInteger::zero(), 'payers' => []];
            $months[$month]['total'] = $months[$month]['total']->plus($instalment['amount']['amount']);
            $payer = $months[$month]['payers'][$instalment['name']] ?? ['accent' => $instalment['accent'], 'amount' => BigInteger::zero()];
            $months[$month]['payers'][$instalment['name']] = ['accent' => $payer['accent'], 'amount' => $payer['amount']->plus($instalment['amount']['amount'])];
        }
        $tallest = array_reduce($months, fn (BigInteger $max, array $month): BigInteger => $max->isLessThan($month['total']) ? $month['total'] : $max, BigInteger::zero());
        $rows = [];
        foreach ($months as $month => $row) {
            $payers = [];
            foreach ($row['payers'] as $name => $payer) {
                $payers[] = ['name' => (string) $name, 'accent' => $payer['accent'], 'amount' => HoldingFigures::money($payer['amount'])];
            }
            $rows[] = ['month' => HoldingFigures::kigaliMidnight($month), 'amount' => HoldingFigures::money($row['total']),
                'bar_pct' => HoldingFigures::percent($row['total'], $tallest), 'payers' => $payers];
        }

        return $rows;
    }

    /**
     * Invested capital by industry, largest first, coloured by its largest business.
     *
     * @param  list<HeldPosition>  $positions
     * @return list<array{name: string, accent: string, amount: Money, share_pct: int}>
     */
    private function industries(array $positions, BigInteger $invested): array
    {
        $industries = [];
        foreach ($this->byBusiness($positions) as $business) {
            $industry = $industries[$business['industry']] ?? ['accent' => $business['accent'], 'amount' => BigInteger::zero()];
            $industries[$business['industry']] = ['accent' => $industry['accent'], 'amount' => $industry['amount']->plus($business['amount'])];
        }
        uasort($industries, fn (array $left, array $right): int => $right['amount']->compareTo($left['amount']));
        $rows = [];
        foreach ($industries as $name => $industry) {
            $rows[] = ['name' => (string) $name, 'accent' => $industry['accent'], 'amount' => HoldingFigures::money($industry['amount']),
                'share_pct' => HoldingFigures::percent($industry['amount'], $invested)];
        }

        return $rows;
    }

    /**
     * Invested capital by published rating band, largest first.
     *
     * @param  list<HeldPosition>  $positions
     * @return list<array{band: string, share_pct: int}>
     */
    private function risk(array $positions, BigInteger $invested): array
    {
        $bands = [];
        foreach ($positions as $position) {
            $band = $position['card']['rating']['band'];
            $bands[$band] = ($bands[$band] ?? BigInteger::zero())->plus($position['holding']['principal']['amount']);
        }
        uasort($bands, fn (BigInteger $left, BigInteger $right): int => $right->compareTo($left));

        return array_map(fn (string $band, BigInteger $amount): array => ['band' => $band, 'share_pct' => HoldingFigures::percent($amount, $invested)],
            array_map(strval(...), array_keys($bands)), array_values($bands));
    }

    /**
     * @param  list<HeldPosition>  $positions
     * @return array{status: 'concentrated', business: string, share_pct: int}|array{status: 'balanced'}|null
     */
    private function concentration(array $positions, BigInteger $invested): ?array
    {
        $largest = $this->byBusiness($positions)[0] ?? null;
        if ($largest === null) {
            return null;
        }
        $share = HoldingFigures::percent($largest['amount'], $invested);

        return $share >= HoldingFigures::CONCENTRATED_PCT ? ['status' => 'concentrated', 'business' => $largest['name'], 'share_pct' => $share] : ['status' => 'balanced'];
    }

    /**
     * Invested capital per held business, largest first.
     *
     * @param  list<HeldPosition>  $positions
     * @return list<array{name: string, accent: string, industry: string, amount: BigInteger}>
     */
    private function byBusiness(array $positions): array
    {
        $businesses = [];
        foreach ($positions as $position) {
            $id = $position['holding']['campaign_id'];
            $businesses[$id] ??= ['name' => $position['card']['name'], 'accent' => $position['card']['accent'], 'industry' => $position['card']['industry'], 'amount' => BigInteger::zero()];
            $businesses[$id]['amount'] = $businesses[$id]['amount']->plus($position['holding']['principal']['amount']);
        }
        $businesses = array_values($businesses);
        usort($businesses, fn (array $left, array $right): int => $right['amount']->compareTo($left['amount']) ?: strcmp($left['name'], $right['name']));

        return $businesses;
    }
}
