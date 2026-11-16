<?php

declare(strict_types=1);

namespace App\Application\Primary;

use App\Application\Business\Contracts\InvestorDealCatalogue;
use App\Application\Primary\Contracts\CampaignDisbursementProgress;
use App\Application\Primary\Contracts\HoldingPayouts;
use App\Application\Primary\Contracts\InvestorHoldings;
use App\Application\Primary\Contracts\RetainedNoteSchedules;
use App\Application\Secondary\Contracts\NoteUnitHolders;
use Brick\Math\BigDecimal;
use Brick\Math\BigInteger;
use Brick\Math\RoundingMode;
use Carbon\CarbonImmutable;
use RuntimeException;

/**
 * The Portfolio facts for the authenticated Investor (C3 v2 §2d), read under current Investor
 * authority through `GetInvestorViewer`: the cash still idle in the wallet and every Holding the
 * primary issue gave them, with the business it is in (`HoldingFigures`). Payouts are the
 * scheduled instalments still unpaid and due, net of the earnings fee and labelled as a projection;
 * what servicing has paid on to a Holding (`HoldingPayouts`) is its received return, its gain and its
 * payments made. Confirmed purchases no Holding
 * has issued from yet are listed apart and never counted among the Holdings: `confirmed` while the
 * raise is open, `funded` once its funding is locked, awaiting or with the payout in flight that issues them.
 *
 * @phpstan-import-type HeldPosition from HoldingFigures
 * @phpstan-import-type Projectable from HoldingFigures
 * @phpstan-import-type Card from HoldingFigures
 *
 * @phpstan-type Money array{currency: string, amount: string}
 * @phpstan-type Summary array{id: string, name: string, accent: string, industry: string, district: string, health: 'healthy'|'watch', value: Money,
 *     invested: Money, gain: Money, gain_pct: string, repaid_pct: int, matures_on: string, payments_made: int, payments_total: int}
 * @phpstan-type PayoutMonth array{month: string, amount: Money, bar_pct: int, payers: list<array{name: string, accent: string, amount: Money}>}
 * @phpstan-type PortfolioFacts array{identity_context_revision: int, verified: bool, idle: Money|null, holdings: list<Summary>,
 *     totals: array{businesses: int, value: Money, invested: Money, gain: Money, this_month: Money, projected_3m: Money,
 *         next_payout: array{amount: Money, month: string}|null, avg_monthly: Money},
 *     payouts: list<PayoutMonth>, industries: list<array{name: string, accent: string, amount: Money, share_pct: int}>,
 *     risk: list<array{band: string, share_pct: int}>,
 *     concentration: array{status: 'concentrated', business: string, share_pct: int}|array{status: 'balanced'}|null,
 *     commitments: list<array{id: string, deal_name: string, units: string, principal: Money, state: 'confirmed'|'funded', closing: array{stage: 'awaiting_disbursement'}|array{stage: 'in_flight', provider: 'pending'|'unknown'}|null}>,
 *     bought: list<Bought>}
 * @phpstan-type Bought array{lot_id: string, note_id: string, name: string, accent: string, industry: string, units: string, price: Money, paid: Money, bought_at: string}
 */
final class GetInvestorPortfolio
{
    public const array TABS = ['active', 'matured', 'secondary', 'saved'];

    /** @var array<string, array<string, mixed>|null> Each campaign's newest monthly update, read once per request. */
    private array $latest = [];

    public function __construct(private GetInvestorViewer $viewer, private InvestorHoldings $holdings, private InvestorDealCatalogue $catalogue,
        private CampaignDisbursementProgress $disbursements, private HoldingPayouts $paid, private NoteUnitHolders $lots,
        private RetainedNoteSchedules $schedules) {}

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
        $bought = $viewer['wallet'] === null ? [] : $this->bought($userId);
        $primary = array_reduce($positions, fn (BigInteger $sum, array $position): BigInteger => $sum->plus($position['holding']['principal']['amount']), BigInteger::zero());
        $invested = array_reduce($bought, fn (BigInteger $sum, array $lot): BigInteger => $sum->plus($lot['paid']['amount']), $primary);
        $payouts = $this->payouts([...$positions, ...$this->boughtPositions($bought)]);
        $received = HoldingFigures::received(array_merge([], ...array_map(fn (array $position): array => $position['paid'], $positions)));
        $month = now()->setTimezone(HoldingFigures::ZONE)->format('Y-m');
        $thisMonth = HoldingFigures::received(array_values(array_filter(array_merge([], ...array_map(fn (array $position): array => $position['paid'], $positions)),
            fn (array $payout): bool => CarbonImmutable::parse($payout['recorded_at'])->setTimezone(HoldingFigures::ZONE)->format('Y-m') === $month)));
        $projected = array_reduce(array_slice($payouts, 0, 3), fn (BigInteger $sum, array $month): BigInteger => $sum->plus($month['amount']['amount']), BigInteger::zero());

        return ['identity_context_revision' => $viewer['identity_context_revision'], 'verified' => $viewer['wallet'] !== null,
            'idle' => $available === null || $available['amount'] === '0' ? null : $available,
            'holdings' => array_map(fn (array $position): array => self::summary($position, $this->latestUpdate($position['holding']['campaign_id'])), $positions),
            'totals' => ['businesses' => count(array_unique([...array_map(fn (array $position): string => $position['holding']['campaign_id'], $positions),
                ...array_map(fn (array $lot): string => $lot['note_id'], $bought)])),
                'value' => HoldingFigures::money($invested->plus($received['return'])), 'invested' => HoldingFigures::money($invested),
                'gain' => HoldingFigures::money($received['return']), 'this_month' => HoldingFigures::money($thisMonth['return']),
                'projected_3m' => HoldingFigures::money($projected),
                'next_payout' => $payouts === [] ? null : ['amount' => $payouts[0]['amount'], 'month' => $payouts[0]['month']],
                'avg_monthly' => HoldingFigures::money($projected->dividedBy(3, RoundingMode::HalfUp))],
            'payouts' => $payouts, 'industries' => $this->industries($positions, $primary), 'risk' => $this->risk($positions, $primary),
            'concentration' => $this->concentration($positions, $primary),
            'commitments' => $viewer['wallet'] === null ? [] : $this->commitments($userId), 'bought' => $bought];
    }

    /**
     * One Holding as the Portfolio and the Holding page list it. Its health follows the business's
     * newest published monthly report: on watch when that report is, healthy otherwise. Its value
     * is what was invested plus the gross return received; `repaid_pct` is the share of its
     * scheduled payments made.
     *
     * @param  HeldPosition  $position
     * @param  array<string, mixed>|null  $latest  The newest published monthly update, if any.
     * @return Summary
     */
    public static function summary(array $position, ?array $latest = null): array
    {
        $holding = $position['holding'];
        $card = $position['card'];
        $schedule = $holding['schedule'];
        $received = HoldingFigures::received($position['paid']);
        $invested = BigInteger::of($holding['principal']['amount']);
        $gainPct = $invested->isZero() ? '0.0' : (string) BigDecimal::of($received['return'])->multipliedBy(100)->dividedBy($invested, 1, RoundingMode::HalfUp);

        return ['id' => $holding['id'], 'name' => $card['name'], 'accent' => $card['accent'], 'industry' => $card['industry'], 'district' => $card['district'],
            'health' => ($latest['status'] ?? null) === 'watch' ? 'watch' : 'healthy', 'value' => HoldingFigures::money($invested->plus($received['return'])),
            'invested' => $holding['principal'], 'gain' => HoldingFigures::money($received['return']), 'gain_pct' => $gainPct,
            'repaid_pct' => HoldingFigures::percent((string) $received['made'], (string) count($schedule)),
            'matures_on' => HoldingFigures::kigaliMidnight($schedule[count($schedule) - 1]['due_on']), 'payments_made' => $received['made'],
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

    /** @return list<array{id: string, deal_name: string, units: string, principal: Money, state: 'confirmed'|'funded', closing: array{stage: 'awaiting_disbursement'}|array{stage: 'in_flight', provider: 'pending'|'unknown'}|null}> */
    private function commitments(int $userId): array
    {
        return array_map(function (array $commitment): array {
            $inFlight = $commitment['funded'] ? $this->disbursements->inFlight($commitment['campaign_id']) : null;

            return ['id' => $commitment['commitment_id'], 'deal_name' => $commitment['deal_name'], 'units' => $commitment['units'],
                'principal' => $commitment['principal'], 'state' => $commitment['funded'] ? 'funded' : 'confirmed',
                'closing' => match (true) {
                    ! $commitment['funded'] => null,
                    $inFlight === null => ['stage' => 'awaiting_disbursement'],
                    default => ['stage' => 'in_flight', 'provider' => $inFlight],
                }];
        }, $this->holdings->awaitingIssue($userId));
    }

    /**
     * The campaign's Business's newest published monthly update, read once per campaign.
     *
     * @return array<string, mixed>|null
     */
    private function latestUpdate(string $campaignId): ?array
    {
        if (! array_key_exists($campaignId, $this->latest)) {
            $this->latest[$campaignId] = $this->catalogue->updates($campaignId)[0] ?? null;
        }

        return $this->latest[$campaignId];
    }

    /**
     * The units bought on the market and still held, newest first, at the price paid for them.
     *
     * @return list<Bought>
     */
    private function bought(int $userId): array
    {
        $bought = [];
        foreach ($this->lots->boughtBy($userId) as $lot) {
            $card = $this->card($lot['note_id']);
            $bought[] = ['lot_id' => $lot['lot_id'], 'note_id' => $lot['note_id'], 'name' => $card['name'], 'accent' => $card['accent'], 'industry' => $card['industry'],
                'units' => $lot['held_units'], 'price' => HoldingFigures::money(BigInteger::of($lot['price'])),
                'paid' => HoldingFigures::money(BigInteger::of($lot['price'])->multipliedBy($lot['held_units'])), 'bought_at' => $lot['settled_at']];
        }

        return $bought;
    }

    /** @return list<HeldPosition> */
    private function positions(int $userId): array
    {
        $cards = [];
        $holdings = $this->holdings->forInvestor($userId);
        $paid = $this->paid->forHoldings(array_map(fn (array $holding): string => $holding['id'], $holdings));

        return array_map(function (array $holding) use (&$cards, $paid): array {
            $cards[$holding['campaign_id']] ??= $this->card($holding['campaign_id']);

            return ['holding' => HoldingFigures::held($holding, $this->lots->soldFrom('primary_holding', $holding['id'])),
                'card' => $cards[$holding['campaign_id']], 'paid' => $paid[$holding['id']] ?? []];
        }, $holdings);
    }

    /**
     * Each bought lot as a position for the payout projection only: the note's schedule scaled to
     * the lot's units, net of the note's earnings fee, without the instalments the note has paid.
     *
     * @param  list<Bought>  $bought
     * @return list<Projectable>
     */
    private function boughtPositions(array $bought): array
    {
        $positions = [];
        foreach ($bought as $lot) {
            $note = $this->schedules->find($lot['note_id']) ?? throw new RuntimeException('HOLDING_PRESENTATION_UNAVAILABLE');
            $units = array_sum(array_map(fn ($holding): int => $holding->units, $note->holdings));
            $scale = fn (string $amount): array => HoldingFigures::money(BigInteger::of($amount)->multipliedBy($lot['units'])->dividedBy($units, RoundingMode::Down));
            $paidOn = array_merge([], ...array_values($this->paid->forHoldings(array_map(fn ($holding): string => $holding->holdingId, $note->holdings))));
            /** @var array{earnings_fee: array{rate_bps: int}} $terms */
            $terms = $note->holdings[0]->terms;
            $positions[] = ['holding' => ['schedule' => array_map(fn ($instalment): array => ['index' => $instalment->index, 'due_on' => $instalment->dueDate,
                'principal' => $scale($instalment->principal), 'return' => $scale($instalment->contractualReturn)], $note->instalments),
                'terms' => ['earnings_fee' => ['rate_bps' => $terms['earnings_fee']['rate_bps']]]],
                'card' => ['name' => $lot['name'], 'accent' => $lot['accent']],
                'paid' => array_map(fn (array $payout): array => [...$payout, 'principal' => '0', 'return' => '0', 'net' => '0'], $paidOn)];
        }

        return $positions;
    }

    /**
     * The projected instalments grouped by Kigali month, earliest first, each split by business.
     *
     * @param  list<Projectable>  $positions
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
