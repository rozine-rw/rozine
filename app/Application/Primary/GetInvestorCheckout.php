<?php

declare(strict_types=1);

namespace App\Application\Primary;

use App\Application\Business\Contracts\InvestorDealCatalogue;
use App\Application\Primary\Contracts\InvestorCheckoutFacts;
use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Application\Primary\Contracts\RiskAcknowledgements;
use App\Domain\Operations\CommandRejection;
use App\Domain\Primary\EarningsFeeBand;
use App\Domain\Primary\PrimaryDisclosure;
use App\Domain\Primary\UnitRights;
use Brick\Math\BigInteger;
use Brick\Math\RoundingMode;

/**
 * The live checkout for one deal (MVP-INVESTOR-SCR-03, C3 v2 §2c), read under current Investor
 * authority. Before a reservation the quote is indicative: the flat return on the chosen units and
 * the fee at the INV-R04 band this checkout would lock. Once units are held it is the exact quote
 * the reservation retains; without one named, the Investor's newest held reservation on this deal
 * is shown, so a reload after a lost answer still finds it. Capacity follows what is left to fill and the 50% limit (INV-R01).
 * Nothing here admits a purchase: reserve and confirm decide under the checkout's own locks.
 *
 * @phpstan-type CheckoutPage array{identity_context_revision: int, eligible: bool, deal: array<string, mixed>,
 *     quote: array<string, mixed>, quick_picks: list<array{units: string, amount: array{currency: string, amount: string}}>,
 *     wallet: array{available: array{currency: string, amount: string}, sufficient: bool, revision: int},
 *     disclosure: array{version: string, sha256: string, points: list<string>}, reservation: array<string, mixed>|null,
 *     risks: array{version: string, statements_sha256: string, statements: list<array{key: string, text: string}>, acknowledged: bool}|null,
 *     allowed_actions: list<string>}
 */
final class GetInvestorCheckout
{
    private const array QUICK_PICKS = [1, 2, 5, 10];

    public function __construct(private GetInvestorViewer $viewer, private InvestorDealCatalogue $catalogue, private PrimaryCheckout $checkout,
        private InvestorCheckoutFacts $facts, private RiskAcknowledgements $risks) {}

    /** @return CheckoutPage */
    public function handle(int $userId, ?int $contextRevision, string $campaignId, ?int $units, ?string $reservationId): array
    {
        $viewer = $this->viewer->handle($userId, $contextRevision);
        $deal = $this->catalogue->deal($campaignId) ?? throw new CommandRejection('DEAL_NOT_FOUND', 404);
        $context = $viewer['identity_context_revision'];
        $wallet = $viewer['wallet'];
        $eligible = $wallet !== null && $wallet['wallet']['status'] === 'active';
        $facts = $wallet === null ? ['active_capital' => '0', 'campaign_units' => '0', 'held_reservation_id' => null] : $this->facts->forInvestor($userId, $campaignId);
        $reservationId ??= $facts['held_reservation_id'];
        $reservation = $wallet === null || $reservationId === null ? null : $this->reservation($userId, $context, $campaignId, $reservationId);
        $capacity = self::capacity($deal, $facts['campaign_units'], $eligible);
        $max = (int) $capacity['max_units'];
        $chosen = $reservation === null ? max(1, min($units ?? 1, max($max, 1))) : (int) $reservation['units'];
        $quote = [...($reservation === null ? $this->indicative($deal, $chosen, $facts['active_capital']) : $this->reserved($deal, $reservation)), 'capacity' => $capacity];
        $available = $wallet === null ? '0' : (string) $wallet['wallet']['breakdown']['available']['amount'];
        $risks = $wallet === null ? null : $this->risks->read($userId, $context);
        $allowed = match (true) {
            $reservation !== null => ['primary.confirm', 'primary.release'],
            $eligible && $max > 0 && $risks !== null && $risks['acknowledged'] => ['primary.reserve'],
            default => [],
        };

        return ['identity_context_revision' => $context, 'eligible' => $eligible, 'deal' => $deal, 'quote' => $quote,
            'quick_picks' => array_values(array_map(fn (int $pick): array => ['units' => (string) $pick, 'amount' => self::money($pick * 5000)],
                array_filter(self::QUICK_PICKS, fn (int $pick): bool => $pick <= max($max, 1)))),
            'wallet' => ['available' => ['currency' => 'RWF', 'amount' => $available],
                'sufficient' => BigInteger::of($available)->isGreaterThanOrEqualTo($quote['amount']['amount']), 'revision' => $wallet === null ? 0 : (int) $wallet['wallet']['revision']],
            'disclosure' => ['version' => PrimaryDisclosure::VERSION, 'sha256' => $reservation === null ? '' : (string) $reservation['disclosure_sha256'],
                'points' => PrimaryDisclosure::points((string) $deal['name'], $chosen, (int) $deal['term_months'], (string) $deal['rate_pct'])],
            'reservation' => $reservation, 'risks' => $risks, 'allowed_actions' => $allowed];
    }

    /**
     * The Party's own reservation on this campaign while it is still held; any other state, or a
     * reservation that is not theirs, leaves the checkout at its quote.
     *
     * @return array<string, mixed>|null
     */
    private function reservation(int $userId, int $context, string $campaignId, string $reservationId): ?array
    {
        try {
            $facts = $this->checkout->reservationFacts($userId, $context, $campaignId, $reservationId);
        } catch (CommandRejection $exception) {
            return $exception->status === 404 ? null : throw $exception;
        }

        return $facts['state'] === 'held' && now('UTC')->lt($facts['expires_at']) ? $facts : null;
    }

    /**
     * The most units this Party may take in the deal now: what is left to fill while the raise is
     * live, bounded by the 50% limit less the units it already holds or committed (INV-R01). The
     * cart (`ManageInvestorCart`, `GetInvestorCart`) bounds its lines by the same rule.
     *
     * @param  array<string, mixed>  $deal
     * @return array{max_units: string, binding: string, remaining: array{raise: array{currency: string, amount: string}}}
     */
    public static function capacity(array $deal, string $campaignUnits, bool $eligible): array
    {
        $open = $deal['lifecycle'] === 'live';
        $available = $open ? BigInteger::of((string) $deal['units']['available']) : BigInteger::zero();
        $cap = BigInteger::max(BigInteger::zero(), BigInteger::of((string) $deal['units']['total'])->quotient(2)->minus($campaignUnits));
        $binding = ! $eligible ? 'restriction' : ($cap->isLessThanOrEqualTo($available) ? 'raise_cap' : 'availability');

        return ['max_units' => $eligible ? (string) BigInteger::min($available, $cap) : '0', 'binding' => $binding,
            'remaining' => ['raise' => self::money($cap->multipliedBy(UnitRights::UNIT_PRINCIPAL))]];
    }

    /**
     * The flat return on these units and the fee at the band this checkout would lock.
     *
     * @param  array<string, mixed>  $deal
     * @return array<string, mixed>
     */
    private function indicative(array $deal, int $units, string $activeCapital): array
    {
        $amount = BigInteger::of(UnitRights::UNIT_PRINCIPAL)->multipliedBy($units);
        $return = self::flatReturn($deal, $amount);
        $fee = EarningsFeeBand::forActiveCapital($amount->plus($activeCapital));
        $payoutFee = $return->multipliedBy($fee['rate_bps'])->dividedBy(10000, RoundingMode::HalfUp);

        return self::quote('indicative', $deal, (string) $units, $amount, $return, $payoutFee, $fee, null);
    }

    /**
     * The flat return on a principal at the deal's one-decimal rate. On one unit it is a whole
     * number of RWF (RWF 5,000 at tenths of a percent), so the cart's return grows by exactly this
     * much per unit.
     *
     * @param  array<string, mixed>  $deal
     */
    public static function flatReturn(array $deal, BigInteger $amount): BigInteger
    {
        return $amount->multipliedBy(str_replace('.', '', (string) $deal['rate_pct']))->dividedBy(1000, RoundingMode::HalfUp);
    }

    /**
     * @param  array<string, mixed>  $deal
     * @param  array<string, mixed>  $reservation
     * @return array<string, mixed>
     */
    private function reserved(array $deal, array $reservation): array
    {
        /** @var array{total_return: array{amount: string}} $rights */
        $rights = $reservation['rights'];
        /** @var array{payout_fee: array{amount: string}, earnings_fee: array{tier: string, rate_bps: int, basis: string, policy_version: string}} $terms */
        $terms = $reservation['terms'];

        return self::quote('reserved', $deal, (string) $reservation['units'], BigInteger::of((string) $reservation['principal']['amount']),
            BigInteger::of($rights['total_return']['amount']), BigInteger::of($terms['payout_fee']['amount']), $terms['earnings_fee'], $rights);
    }

    /**
     * @param  array<string, mixed>  $deal
     * @param  array{tier: string, rate_bps: int, basis: string, policy_version: string}  $fee
     * @param  array<string, mixed>|null  $rights
     * @return array<string, mixed>
     */
    private static function quote(string $basis, array $deal, string $units, BigInteger $amount, BigInteger $return, BigInteger $payoutFee, array $fee, ?array $rights): array
    {
        return ['basis' => $basis, 'units' => $units, 'unit_price' => self::money(UnitRights::UNIT_PRINCIPAL), 'amount' => self::money($amount),
            'rate_pct' => $deal['rate_pct'], 'term_months' => $deal['term_months'], 'expected_return' => self::money($return),
            'payout_fee' => self::money($payoutFee), 'earnings_fee' => $fee, 'maturity_value' => self::money($amount->plus($return)->minus($payoutFee)),
            'maturity_date' => null, 'rights' => $rights, 'revision' => 1];
    }

    /** @return array{currency: string, amount: string} */
    private static function money(BigInteger|int|string $amount): array
    {
        return ['currency' => 'RWF', 'amount' => (string) $amount];
    }
}
