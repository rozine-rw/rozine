<?php

declare(strict_types=1);

namespace App\Application\Primary;

use App\Application\Business\Contracts\InvestorDealCatalogue;
use App\Application\Primary\Contracts\InvestorCart;
use App\Application\Primary\Contracts\InvestorCheckoutFacts;
use App\Domain\Operations\CommandRejection;

/**
 * Adds a deal to the Investor's cart, changes a line's units and takes a line out (BRS v2.0
 * INV-R02), under current Investor authority. Only an open deal with notes left may be added or
 * changed, in whole RWF 5,000 units within what is left and the 50% limit (INV-R01, the
 * checkout's own capacity rule), so a line over the limit is refused with the most allowed. A
 * line is a convenience only: nothing is reserved, and the checkout admits each purchase itself.
 */
final readonly class ManageInvestorCart
{
    public function __construct(private GetInvestorViewer $viewer, private InvestorCart $cart, private InvestorDealCatalogue $catalogue,
        private InvestorCheckoutFacts $facts) {}

    /** @return array{campaign_id: string, units: int, count: int} */
    public function add(int $userId, int $contextRevision, string $campaignId, int $units): array
    {
        return $this->put($userId, $contextRevision, $campaignId, $units, false);
    }

    /** @return array{campaign_id: string, units: int, count: int} */
    public function change(int $userId, int $contextRevision, string $campaignId, int $units): array
    {
        return $this->put($userId, $contextRevision, $campaignId, $units, true);
    }

    /** @return array{campaign_id: string, removed: bool, count: int} */
    public function remove(int $userId, int $contextRevision, string $campaignId): array
    {
        return $this->cart->remove($userId, $contextRevision, $campaignId);
    }

    /** @return array{campaign_id: string, units: int, count: int} */
    private function put(int $userId, int $contextRevision, string $campaignId, int $units, bool $existing): array
    {
        $wallet = $this->viewer->handle($userId, $contextRevision)['wallet'];
        if ($wallet === null) {
            throw new CommandRejection('IDENTITY_VERIFICATION_REQUIRED', 403);
        }
        if ($wallet['wallet']['status'] !== 'active') {
            throw new CommandRejection('RESTRICTION_ACTIVE', 409);
        }

        return $this->cart->put($userId, $contextRevision, $campaignId, $units, function (bool $exists, int $count) use ($userId, $campaignId, $units, $existing): void {
            if ($existing && ! $exists) {
                throw new CommandRejection('CART_LINE_NOT_FOUND', 404);
            }
            if (! $exists && $count >= InvestorCart::MAX_LINES) {
                throw new CommandRejection('CART_FULL', 409);
            }
            $deal = $this->catalogue->deal($campaignId) ?? throw new CommandRejection('DEAL_NOT_FOUND', 404);
            if ($deal['lifecycle'] !== 'live' || (string) $deal['units']['available'] === '0') {
                throw new CommandRejection('DEAL_FULLY_FUNDED', 409);
            }
            $max = (int) GetInvestorCheckout::capacity($deal, $this->facts->forInvestor($userId, $campaignId)['campaign_units'], true)['max_units'];
            if ($units > $max) {
                throw new CommandRejection('CART_UNITS_ABOVE_LIMIT', 422, null, ['units' => ["The most you may take in this raise is {$max} notes."]],
                    ['max_units' => (string) $max]);
            }
        });
    }
}
