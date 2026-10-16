<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Domain\Underwriting\ApplicationUnderwriting;
use App\Domain\Underwriting\CashFlowEvidence;
use DateTimeImmutable;

/**
 * @phpstan-import-type MonthInput from CashFlowEvidence
 * @phpstan-import-type ObligationInput from CashFlowEvidence
 * @phpstan-import-type RepeatEligibility from CashFlowEvidence
 * @phpstan-import-type History from ApplicationUnderwriting
 */
final class UnderwritingEvidenceFixture
{
    /** @return array{requested_principal: string, tenor_months: int, accepted_principal: string|null, months: list<MonthInput>, last_complete_month: string, first_repayment_month: string, recurring_owner_draw: string, obligations: list<ObligationInput>, history: History, restriction_active: bool} */
    public static function application(): array
    {
        return ['requested_principal' => '12000000', 'tenor_months' => 6, 'accepted_principal' => null,
            'months' => self::months(), 'last_complete_month' => '2026-08', 'first_repayment_month' => '2026-09',
            'recurring_owner_draw' => '0', 'obligations' => [], 'restriction_active' => false,
            'history' => ['has_rozine_history' => false, 'repeat_eligibility' => null, 'instalment_conduct' => null, 'report_conduct' => null,
                'post_grace_arrears' => false, 'reporting_breach' => false, 'defaulted' => false, 'days_past_due' => 0]];
    }

    /** @return non-empty-list<MonthInput> */
    public static function months(int $count = 36, string $inflow = '4000000', string $outflow = '1000000'): array
    {
        $first = (new DateTimeImmutable('2026-08-01'))->modify('-'.($count - 1).' months');

        return array_map(fn (int $offset): array => [
            'month' => $first->modify('+'.$offset.' months')->format('Y-m'),
            'operating_inflow' => $inflow, 'operating_outflow' => $outflow,
            'owner_draw' => '0', 'debt_service' => '0', 'verified' => true,
        ], range(0, $count - 1));
    }

    /** @return RepeatEligibility */
    public static function repeat(): array
    {
        return ['baseline_passed' => true, 'settled_notes' => 1, 'late_payments' => 0, 'gap_audits_complete' => true, 'automated_collection' => true];
    }

    /** @return array<string, string> */
    public static function debt(string $amount): array
    {
        return array_fill_keys(['2026-09', '2026-10', '2026-11', '2026-12', '2027-01', '2027-02'], $amount);
    }
}
