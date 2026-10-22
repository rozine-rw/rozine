<?php

declare(strict_types=1);

namespace App\Application\Operations;

use App\Domain\Disbursement\PlatformFee;
use App\Domain\Identity\Complaint;
use App\Domain\Identity\InvestorVerificationCase;
use App\Domain\Underwriting\FlatReturnPricing;
use App\Domain\Underwriting\LoanCapacity;
use App\Domain\Underwriting\UnderwritingObservationWindow;
use App\Domain\Wallet\WithdrawalTerms;
use Brick\Math\BigRational;

/**
 * The rules the console's policy strips and Policies section show, read from the constants the
 * domain enforces so no screen can drift from what the engine does. Each strip is the handful of
 * rules its queue is decided under; `rules()` is every one of them under the design's Policies tab,
 * naming the policy version it comes from where the rule carries one. Rules the platform does not
 * apply yet (late charges before servicing, Rozine Plus bands while deferred) are left out rather
 * than shown with invented values.
 *
 * @phpstan-type PolicyItem array{key: string, value: string}
 * @phpstan-type PolicyRule array{tab: string, key: string, value: string, version: string|null}
 */
final class PlatformPolicies
{
    /**
     * The underwriting rules the applications queue is decided under: the coverage offers are sized
     * to, the coverage below which an application is rejected, and the flat return band.
     *
     * @return list<PolicyItem>
     */
    public static function underwriting(): array
    {
        $coverage = fn (string $ratio): string => BigRational::of($ratio)->toScale(2).'×';

        return [['key' => 'target_dscr', 'value' => $coverage(LoanCapacity::TARGET_COVERAGE)],
            ['key' => 'audit_routing', 'value' => '≥ '.$coverage(LoanCapacity::MINIMUM_COVERAGE)],
            ['key' => 'rate_band', 'value' => FlatReturnPricing::MINIMUM_RETURN_PERCENT.'–'.FlatReturnPricing::MAXIMUM_RETURN_PERCENT.'%']];
    }

    /**
     * The fees the Payment Center collects: the platform fee, the Audit Partner's share of it, and
     * the listing and per-repayment fees the BRS leaves at zero.
     *
     * @return list<PolicyItem>
     */
    public static function fees(): array
    {
        $percent = fn (int $basisPoints): string => rtrim(rtrim(number_format($basisPoints / 100, 2, '.', ''), '0'), '.').'%';

        return [['key' => 'platform_fee', 'value' => $percent(PlatformFee::FEE_BPS)],
            ['key' => 'cpa_share', 'value' => $percent(PlatformFee::AUDIT_PARTNER_SHARE_BPS)],
            ['key' => 'listing_fee', 'value' => '0%'], ['key' => 'repayment_fee', 'value' => '0%']];
    }

    /**
     * The rules Compliance verifies Investors and answers complaints under.
     *
     * @return list<PolicyItem>
     */
    public static function compliance(): array
    {
        return [['key' => 'minimum_age', 'value' => (string) InvestorVerificationCase::MINIMUM_AGE],
            ['key' => 'identity_evidence', 'value' => (string) count(InvestorVerificationCase::SLOTS)],
            ['key' => 'complaint_acknowledge', 'value' => Complaint::ACKNOWLEDGE_HOURS.' h'],
            ['key' => 'complaint_resolve', 'value' => Complaint::RESOLVE_HOURS.' h']];
    }

    /**
     * The audited history a first-time issuer needs before it can apply, in years.
     *
     * @return list<PolicyItem>
     */
    public static function businesses(): array
    {
        return [['key' => 'min_statement_years', 'value' => (string) intdiv(UnderwritingObservationWindow::FIRST_ISSUE_MONTHS, 12)]];
    }

    /** @return list<PolicyRule> */
    public static function rules(): array
    {
        $rwf = fn (int|string $amount): string => 'RWF '.number_format((int) $amount);
        $row = fn (string $tab, ?string $version) => fn (array $item): array => ['tab' => $tab, 'key' => $item['key'], 'value' => $item['value'], 'version' => $version];
        $tenors = FlatReturnPricing::TENORS;
        $underwriting = [...self::underwriting(),
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

        return [...array_map($row('fees', PlatformFee::VERSION), self::fees()),
            ...array_map($row('underwriting', FlatReturnPricing::POLICY_VERSION), $underwriting),
            ...array_map($row('business', UnderwritingObservationWindow::VERSION), $business),
            ...array_map($row('kyc', null), $kyc),
            ...array_map($row('penalties', null), $penalties),
            ...array_map($row('limits', WithdrawalTerms::VERSION), $limits)];
    }
}
