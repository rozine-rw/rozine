<?php

declare(strict_types=1);

namespace App\Application\Primary;

use App\Application\Business\Contracts\InvestorDealCatalogue;
use App\Application\Primary\Contracts\HoldingPayouts;
use App\Application\Primary\Contracts\InvestorHoldings;
use App\Application\Secondary\Contracts\NoteUnitHolders;
use App\Domain\Operations\CommandRejection;
use Carbon\CarbonImmutable;

/**
 * One issued Holding for the authenticated Investor (C3 v2 §2e), read under current Investor
 * authority. It carries the issue as recorded (`IssuedHolding`) and the figures the issue alone
 * supports: the expected return, the maturity value net of the payout fee and the next scheduled
 * instalment, and the business's published monthly audit reports, the newest of which sets the
 * Holding's health and its on-plan strip (`latest_report`). What servicing has paid on to
 * it so far (`HoldingPayouts`) is its net received and its payments made; the recovery plan,
 * arrears and photos stay empty. `resold_units` is how many of its units were sold on the market;
 * once some are, every figure follows the units still held, while the issue stays as recorded.
 *
 * @phpstan-import-type IssuedHoldingRecord from InvestorHoldings
 */
final class GetInvestorHolding
{
    public function __construct(private GetInvestorViewer $viewer, private InvestorHoldings $holdings, private GetInvestorPortfolio $portfolio,
        private InvestorDealCatalogue $catalogue, private HoldingPayouts $paid, private NoteUnitHolders $lots) {}

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
        $resold = $this->lots->soldFrom('primary_holding', $holding['id']);
        $issue = $holding;
        $holding = HoldingFigures::held($holding, $resold);
        $position = ['holding' => $holding, 'card' => $card, 'paid' => $this->paid->forHoldings([$holding['id']])[$holding['id']] ?? []];
        $upcoming = HoldingFigures::upcoming([$position], now());
        $updates = $this->catalogue->updates($holding['campaign_id']);
        $dueOn = array_column($holding['schedule'], 'due_on', 'index');
        $made = $late = 0;
        foreach ($position['paid'] as $payout) {
            $paidOn = CarbonImmutable::parse($payout['recorded_at'])->setTimezone(HoldingFigures::ZONE)->format('Y-m-d');
            foreach ($payout['instalments'] as $index) {
                $made++;
                $late += $paidOn > ($dueOn[$index] ?? $paidOn) ? 1 : 0;
            }
        }
        $latest = $updates[0] ?? null;

        return ['identity_context_revision' => $viewer['identity_context_revision'], 'verified' => true, 'issue' => $issue,
            'holding' => [...GetInvestorPortfolio::summary($position, $latest), 'rating' => $card['rating'], 'rate_pct' => $holding['terms']['rate_pct'],
                'expected_profit' => $holding['rights']['total_return'], 'maturity_value' => HoldingFigures::maturityValue($holding),
                'received' => HoldingFigures::money(HoldingFigures::received($position['paid'])['net']),
                'next_payment' => $upcoming === [] ? null : ['amount' => $upcoming[0]['amount'], 'due_on' => HoldingFigures::kigaliMidnight($upcoming[0]['due_on'])],
                'months_left' => count($upcoming), 'on_time' => ['made' => $made, 'on_time' => $made - $late, 'late' => $late], 'investors' => $card['investors'],
                'updates' => $updates, 'resold_units' => (string) $resold,
                'latest_report' => $latest === null ? null : ['month' => $latest['month'], 'status' => $latest['status'], 'summary' => $latest['summary']]]];
    }
}
