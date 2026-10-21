<?php

declare(strict_types=1);

namespace App\Application\Primary;

use App\Application\Business\Contracts\InvestorDealCatalogue;
use App\Application\Primary\Contracts\InvestorCart;
use App\Application\Primary\Contracts\InvestorCheckoutFacts;
use App\Domain\Primary\UnitRights;
use Brick\Math\BigInteger;
use RuntimeException;

/**
 * The Investor's cart (BRS v2.0 INV-R02), read under current Investor authority. Each line carries
 * the deal's card as Deals shows it, the units chosen, the most this Party may take there now (the
 * checkout's capacity rule, INV-R01) and the flat return one unit earns, so the page can show
 * invest + interest = get back for any quantity. A line whose deal has filled, closed or no longer
 * lists stays in the cart marked filled, and is left out of any total. A person whose identity is
 * not yet verified has no cart. Nothing here quotes fees or reserves; each line opens the deal's
 * own checkout.
 *
 * @phpstan-type CartPage array{identity_context_revision: int, verification: 'required'|'pending'|null, eligible: bool,
 *     lines: list<array{campaign_id: string, deal: array<string, mixed>, units: string, max_units: string, binding: string, filled: bool,
 *     unit_return: array{currency: string, amount: string}}>|null}
 */
final readonly class GetInvestorCart
{
    public function __construct(private GetInvestorViewer $viewer, private InvestorCart $cart, private InvestorDealCatalogue $catalogue,
        private InvestorCheckoutFacts $facts) {}

    /** @return CartPage */
    public function handle(int $userId, ?int $contextRevision): array
    {
        $viewer = $this->viewer->handle($userId, $contextRevision);
        $wallet = $viewer['wallet'];
        $page = ['identity_context_revision' => $viewer['identity_context_revision'], 'verification' => $viewer['verification'],
            'eligible' => $wallet !== null && $wallet['wallet']['status'] === 'active', 'lines' => null];
        if ($wallet === null) {
            return $page;
        }

        return [...$page, 'lines' => array_map(fn (array $line): array => $this->line($userId, $line['campaign_id'], $line['units'], $page['eligible']),
            $this->cart->lines($userId, $viewer['identity_context_revision']))];
    }

    /**
     * One line. Only a rated, listed deal can be added, and a publication keeps its rating, so a
     * line always has a card.
     *
     * @return array{campaign_id: string, deal: array<string, mixed>, units: string, max_units: string, binding: string, filled: bool, unit_return: array{currency: string, amount: string}}
     */
    private function line(int $userId, string $campaignId, int $units, bool $eligible): array
    {
        $deal = $this->catalogue->deal($campaignId);
        $card = $deal ?? $this->catalogue->card($campaignId) ?? throw new RuntimeException('CART_DEAL_UNPUBLISHED');
        $filled = $deal === null || $deal['lifecycle'] !== 'live' || (string) $deal['units']['available'] === '0';
        $capacity = $filled ? ['max_units' => '0', 'binding' => 'availability']
            : GetInvestorCheckout::capacity($deal, $this->facts->forInvestor($userId, $campaignId)['campaign_units'], $eligible);
        $return = (string) GetInvestorCheckout::flatReturn($card, BigInteger::of(UnitRights::UNIT_PRINCIPAL));

        return ['campaign_id' => $campaignId, 'deal' => self::card($card), 'units' => (string) $units,
            'max_units' => $capacity['max_units'], 'binding' => $capacity['binding'], 'filled' => $filled,
            'unit_return' => ['currency' => 'RWF', 'amount' => $return]];
    }

    /**
     * The card facts Deals lists, without a detail page's sections.
     *
     * @param  array<string, mixed>  $deal
     * @return array<string, mixed>
     */
    private static function card(array $deal): array
    {
        return array_diff_key($deal, array_flip(['use_of_funds', 'financials', 'rationale', 'track_record', 'about', 'audit', 'updates', 'overdue_report']));
    }
}
