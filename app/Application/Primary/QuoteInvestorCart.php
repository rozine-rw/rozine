<?php

declare(strict_types=1);

namespace App\Application\Primary;

use App\Application\Business\Contracts\InvestorDealCatalogue;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Application\Primary\Contracts\HeldNoteCapital;
use App\Application\Primary\Contracts\InvestorCartQuotes;
use App\Application\Primary\Contracts\InvestorCheckoutFacts;
use App\Application\Primary\Contracts\RiskAcknowledgements;
use App\Domain\Operations\CommandRejection;
use App\Domain\Primary\EarningsFeeBand;
use App\Domain\Primary\PrimaryDisclosure;
use App\Domain\Primary\UnitRights;
use Brick\Math\BigInteger;
use Brick\Math\RoundingMode;

/**
 * Prices the Investor's entire saved cart as one checkout (#383 step 3b, contract cart-checkout-1,
 * `QuoteInvestorCart`). The quote is kept but holds no units or cash; reserve and confirm admit the
 * purchase under their own locks later.
 *
 * - Every line is priced, or the whole cart is refused: a closed or funded raise
 *   (`CART_LINE_UNAVAILABLE`), or units above what is left and the 50% limit
 *   (`CART_UNITS_ABOVE_LIMIT`, with the most allowed per line). No line is skipped or shrunk.
 * - One INV-R04 band for the whole cart, from held-note principal plus the cart's principal
 *   (B = H + C). Wallet cash, reservations and unissued purchases never count. Held-note capital
 *   the source cannot prove complete refuses `POLICY_INPUT_REQUIRED` rather than guessing.
 * - Each line's earnings fee is charged on its own return at that band, half-up, never on
 *   principal. Note ordinals are allocated only at reserve, so a line's return and fee are the flat
 *   amounts on its units (`basis: indicative`), as the single-deal checkout shows before a hold.
 *   The band, rate, units and principal are exact.
 * - The KYC-R05 risk statements must be acknowledged, as for any reserve.
 *
 * @phpstan-import-type CartCampaign from InvestorCartQuotes
 * @phpstan-import-type QuotedCart from InvestorCartQuotes
 * @phpstan-import-type QuoteBody from InvestorCartQuotes
 * @phpstan-import-type Quote from InvestorCartQuotes
 */
final readonly class QuoteInvestorCart
{
    /** The version of the canonical object each line's disclosure hash covers. */
    private const string LINE_DISCLOSURE_VERSION = 'cart-line-disclosure-1';

    /** The campaign publication is immutable, so its only revision is the first. */
    private const int CAMPAIGN_REVISION = 1;

    public function __construct(private GetInvestorViewer $viewer, private InvestorCartQuotes $quotes, private InvestorDealCatalogue $catalogue,
        private InvestorCheckoutFacts $facts, private RiskAcknowledgements $acknowledgements, private HeldNoteCapital $capital, private CanonicalJson $json) {}

    /** @return Quote */
    public function handle(int $userId, int $contextRevision, int $expectedCartRevision): array
    {
        $wallet = $this->viewer->handle($userId, $contextRevision)['wallet'];
        if ($wallet === null) {
            throw new CommandRejection('IDENTITY_VERIFICATION_REQUIRED', 403);
        }
        if ($wallet['wallet']['status'] !== 'active') {
            throw new CommandRejection('RESTRICTION_ACTIVE', 409);
        }

        return $this->quotes->quote($userId, $contextRevision, $expectedCartRevision,
            fn (string $partyId, array $cart, array $campaigns): array => $this->price($userId, $partyId, $cart, $campaigns));
    }

    /**
     * @param  QuotedCart  $cart
     * @param  array<string, CartCampaign>  $campaigns
     * @return QuoteBody
     */
    private function price(int $userId, string $partyId, array $cart, array $campaigns): array
    {
        if (! $this->acknowledgements->current($partyId)) {
            throw new CommandRejection('RISK_ACKNOWLEDGEMENT_REQUIRED', 409);
        }
        $this->requireCapacity($userId, $cart);
        $capital = $this->capital->current($partyId);
        if (! $capital['complete']) {
            throw new CommandRejection('POLICY_INPUT_REQUIRED', 409);
        }
        $total = BigInteger::zero();
        foreach ($cart['lines'] as $line) {
            $total = $total->plus(BigInteger::of(UnitRights::UNIT_PRINCIPAL)->multipliedBy($line['units']));
        }
        $active = $total->plus($capital['outstanding_principal']);
        $band = EarningsFeeBand::forActiveCapital($active);
        $next = EarningsFeeBand::nextBandAt($active);
        $lines = [];
        $fee = BigInteger::zero();
        $net = BigInteger::zero();
        foreach ($this->canonical($cart['lines']) as $line) {
            $quoted = $this->line($line, $campaigns[$line['campaign_id']], $band);
            $fee = $fee->plus($quoted['earnings_fee']['amount']);
            $net = $net->plus($quoted['net_return']['amount']);
            $lines[] = $quoted;
        }

        return ['lines' => $lines, 'principal' => self::money($total), 'earnings_fee' => self::money($fee), 'net_return' => self::money($net),
            'fee_band' => [...$band, 'rate_pct' => EarningsFeeBand::percent($band['rate_bps']),
                'held_note_principal' => self::money($capital['outstanding_principal']), 'checkout_total' => self::money($total),
                'active_capital_after_checkout' => self::money($active), 'next_band_at' => $next === null ? null : self::money($next),
                'next_band_distance' => $next === null ? null : self::money(BigInteger::of($next)->minus($active)),
                'capital_revision' => $capital['revision'], 'capital_sha256' => $capital['sha256']]];
    }

    /**
     * Refuses the whole cart when a line's deal no longer lists, or its units are above what is left
     * and the 50% limit, the cart's own rule (`ManageInvestorCart`).
     *
     * @param  QuotedCart  $cart
     */
    private function requireCapacity(int $userId, array $cart): void
    {
        $unavailable = [];
        $over = [];
        foreach ($cart['lines'] as $line) {
            $deal = $this->catalogue->deal($line['campaign_id']);
            if ($deal === null || $deal['lifecycle'] !== 'live') {
                $unavailable[] = $line['campaign_id'];

                continue;
            }
            $max = (int) GetInvestorCheckout::capacity($deal, $this->facts->forInvestor($userId, $line['campaign_id'])['campaign_units'], true)['max_units'];
            if ($line['units'] > $max) {
                $over[$line['campaign_id']] = (string) $max;
            }
        }
        if ($unavailable !== []) {
            throw new CommandRejection('CART_LINE_UNAVAILABLE', 409, $cart['revision'], [], ['campaign_ids' => $unavailable]);
        }
        if ($over !== []) {
            throw new CommandRejection('CART_UNITS_ABOVE_LIMIT', 422, $cart['revision'],
                ['lines' => ['A line is above the most you may take in its raise. Change its units and quote again.']], ['max_units' => $over]);
        }
    }

    /**
     * @param  array{id: string, campaign_id: string, units: int}  $line
     * @param  CartCampaign  $campaign
     * @param  array{tier: string, rate_bps: int, basis: string, policy_version: string}  $band
     * @return array<string, mixed>
     */
    private function line(array $line, array $campaign, array $band): array
    {
        $principal = BigInteger::of(UnitRights::UNIT_PRINCIPAL)->multipliedBy($line['units']);
        $return = GetInvestorCheckout::flatReturn(['rate_pct' => $campaign['rate_pct']], $principal);
        $fee = $return->multipliedBy($band['rate_bps'])->dividedBy(10000, RoundingMode::HalfUp);
        $quoted = ['cart_line_id' => $line['id'], 'campaign_id' => $line['campaign_id'], 'units' => (string) $line['units'],
            'campaign_revision' => self::CAMPAIGN_REVISION, 'publication_sha256' => $campaign['publication_sha256'], 'principal' => self::money($principal),
            'terms' => ['rate_pct' => $campaign['rate_pct'], 'term_months' => $campaign['term_months'], 'policy_version' => $campaign['policy_version'],
                'earnings_fee' => $band], 'basis' => 'indicative', 'contractual_return' => self::money($return),
            'earnings_fee' => self::money($fee), 'net_return' => self::money($return->minus($fee)), 'disclosure_version' => PrimaryDisclosure::VERSION];

        return [...$quoted, 'disclosure_sha256' => hash('sha256', $this->json->encode(['version' => self::LINE_DISCLOSURE_VERSION, ...$quoted]))];
    }

    /**
     * The lines in the contract's canonical order: campaign id, then cart line id.
     *
     * @param  list<array{id: string, campaign_id: string, units: int}>  $lines
     * @return list<array{id: string, campaign_id: string, units: int}>
     */
    private function canonical(array $lines): array
    {
        usort($lines, fn (array $left, array $right): int => [$left['campaign_id'], $left['id']] <=> [$right['campaign_id'], $right['id']]);

        return $lines;
    }

    /** @return array{currency: string, amount: string} */
    private static function money(BigInteger|string $amount): array
    {
        return ['currency' => 'RWF', 'amount' => (string) $amount];
    }
}
