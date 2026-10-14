<?php

declare(strict_types=1);

namespace App\Application\Primary;

use App\Application\Primary\Contracts\InvestorHoldings;
use App\Domain\Operations\CommandRejection;
use Brick\Math\BigInteger;

/**
 * One issued Holding for the authenticated Investor (C3 v2 §2e), read under current Investor
 * authority. It carries the issue as recorded (`IssuedHolding`) and the figures the issue alone
 * supports: the expected return, the maturity value net of the payout fee and the next scheduled
 * instalment. Nothing has been received or serviced yet, so the payment record, recovery plan,
 * arrears, photos and monthly updates are empty.
 *
 * @phpstan-import-type IssuedHoldingRecord from InvestorHoldings
 */
final class GetInvestorHolding
{
    public function __construct(private GetInvestorViewer $viewer, private InvestorHoldings $holdings, private GetInvestorPortfolio $portfolio) {}

    /**
     * Refuses `HOLDING_NOT_FOUND` (404) for an unknown Holding or another Party's.
     *
     * @return array{identity_context_revision: int, verified: bool, holding: array<string, mixed>, issue: IssuedHoldingRecord}
     */
    public function handle(int $userId, ?int $contextRevision, string $holdingId): array
    {
        $viewer = $this->viewer->handle($userId, $contextRevision);
        $holding = $viewer['wallet'] === null ? null : $this->holdings->find($userId, $holdingId);
        if ($holding === null) {
            throw new CommandRejection('HOLDING_NOT_FOUND', 404);
        }
        $card = $this->portfolio->card($holding['campaign_id']);
        $position = ['holding' => $holding, 'card' => $card];
        $upcoming = HoldingFigures::upcoming([$position], now());

        return ['identity_context_revision' => $viewer['identity_context_revision'], 'verified' => true, 'issue' => $holding,
            'holding' => [...GetInvestorPortfolio::summary($position), 'rating' => $card['rating'], 'rate_pct' => $holding['terms']['rate_pct'],
                'expected_profit' => $holding['rights']['total_return'], 'maturity_value' => HoldingFigures::maturityValue($holding),
                'received' => HoldingFigures::money(BigInteger::zero()),
                'next_payment' => $upcoming === [] ? null : ['amount' => $upcoming[0]['amount'], 'due_on' => HoldingFigures::kigaliMidnight($upcoming[0]['due_on'])],
                'months_left' => count($upcoming), 'investors' => $card['investors']]];
    }
}
