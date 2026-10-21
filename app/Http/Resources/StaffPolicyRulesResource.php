<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Disbursement\PlatformFee;
use App\Domain\Identity\Complaint;
use App\Domain\Identity\InvestorVerificationCase;
use App\Domain\Underwriting\FlatReturnPricing;
use App\Domain\Underwriting\LoanCapacity;
use App\Domain\Underwriting\UnderwritingObservationWindow;
use App\Domain\Wallet\WithdrawalTerms;

/**
 * The rules in effect for the console's Policies section, one row per rule under the design's tab,
 * read from the constants the domain enforces so the page cannot drift from what the engine does.
 * Each row names the policy version it comes from, where the rule carries one. Rules the platform
 * does not apply yet (late charges before servicing, Rozine Plus bands while deferred) are left out
 * rather than shown with invented values.
 */
final class StaffPolicyRulesResource
{
    /** @return list<array{tab: string, key: string, value: string, version: string|null}> */
    public static function rows(): array
    {
        $rwf = fn (int|string $amount): string => 'RWF '.number_format((int) $amount);
        $row = fn (string $tab, ?string $version) => fn (array $item): array => ['tab' => $tab, 'key' => $item['key'], 'value' => $item['value'], 'version' => $version];
        $tenors = FlatReturnPricing::TENORS;
        $underwriting = [...StaffApplicationsResource::policy(),
            ['key' => 'min_principal', 'value' => $rwf(LoanCapacity::MINIMUM_PRINCIPAL)],
            ['key' => 'max_principal', 'value' => $rwf(LoanCapacity::MAXIMUM_PRINCIPAL)],
            ['key' => 'tenors', 'value' => min($tenors).'–'.max($tenors)]];
        $business = [['key' => 'first_issue_months', 'value' => (string) UnderwritingObservationWindow::FIRST_ISSUE_MONTHS],
            ['key' => 'repeat_issue_months', 'value' => (string) UnderwritingObservationWindow::REPEAT_ISSUE_MONTHS]];
        $kyc = [['key' => 'minimum_age', 'value' => (string) InvestorVerificationCase::MINIMUM_AGE],
            ['key' => 'identity_evidence', 'value' => (string) count(InvestorVerificationCase::SLOTS)]];
        $penalties = [['key' => 'complaint_acknowledge', 'value' => Complaint::ACKNOWLEDGE_HOURS.' h'],
            ['key' => 'complaint_resolve', 'value' => Complaint::RESOLVE_HOURS.' h']];
        $limits = [['key' => 'withdrawal_min', 'value' => $rwf(WithdrawalTerms::MINIMUM)],
            ['key' => 'withdrawal_max', 'value' => $rwf(WithdrawalTerms::MAXIMUM)]];

        return [...array_map($row('fees', PlatformFee::VERSION), StaffPaymentsResource::policy()),
            ...array_map($row('underwriting', FlatReturnPricing::POLICY_VERSION), $underwriting),
            ...array_map($row('business', UnderwritingObservationWindow::VERSION), $business),
            ...array_map($row('kyc', null), $kyc),
            ...array_map($row('penalties', null), $penalties),
            ...array_map($row('limits', WithdrawalTerms::VERSION), $limits)];
    }
}
