<?php

declare(strict_types=1);

namespace App\Application\Operations;

use App\Domain\Disbursement\PlatformFee;
use App\Domain\Identity\Complaint;
use App\Domain\Identity\InvestorVerificationCase;
use App\Domain\Primary\EarningsFeeBand;
use App\Domain\Primary\UnitRights;
use App\Domain\Secondary\SecondaryTerms;
use App\Domain\Underwriting\FlatReturnPricing;
use App\Domain\Underwriting\LoanCapacity;
use App\Domain\Underwriting\UnderwritingObservationWindow;
use App\Domain\Wallet\WithdrawalTerms;
use Brick\Math\BigRational;

/**
 * The rules the console's policy strips and Policies section show, read from the constants the
 * domain enforces so no screen can drift from what the engine does. Each strip is the handful of
 * rules its queue is decided under; `rules()` is every one of them under the design's Policies tab,
 * naming the policy version it comes from where the rule carries one. A rule the engine does not
 * hold as a constant yet shows the value BRS v2.0 fixes for it (its rule ID is noted beside it); a
 * rule the BRS leaves open shows `not_set`, and nothing is invented. Rozine Plus bands stay out
 * while deferred.
 *
 * A worded value travels as a `term` the console translates, with `value` empty: `every_deal`,
 * `required`, `not_set`, `none`, `late_from_due`, `day_31` and `late_schedule`.
 *
 * The values are code, not versioned policy data yet, so the console only reads them: a change goes
 * through a release under ADM-R05 rather than an Edit form.
 *
 * @phpstan-type PolicyItem array{key: string, value: string, term?: string}
 * @phpstan-type PolicyRule array{tab: string, key: string, value: string, term?: string, version: string|null}
 */
final class PlatformPolicies
{
    /** UW-R01: verified annual revenue a Business needs to apply. */
    public const int MIN_ANNUAL_REVENUE = 30000000;

    /** UW-R03: an offer never exceeds this share of verified annual revenue. */
    public const int REVENUE_CAP_PERCENT = 35;

    /** UW-R04: the lowest rating that may raise. */
    public const string MIN_RATING = '2.0';

    /** INV-R01: the most of any raise one Investor may hold. */
    public const int CONCENTRATION_PERCENT = 50;

    /** NOTE-R03: how long a raise stays open. */
    public const int RAISE_DAYS = 30;

    /** FEE-R02: the Audit Partner's share of the earnings fee. */
    public const int CPA_EARNINGS_SHARE_PERCENT = 25;

    /** AUD-R02: how far from the CPA's office an assignment may be. */
    public const int CPA_RADIUS_KM = 30;

    /** SBX-R01: the sandbox's hard limits. */
    public const array SANDBOX = ['businesses' => 100, 'investors' => 10000, 'principal' => 5000000000];

    /**
     * A worded rule the console translates.
     *
     * @return array{key: string, value: string, term: string}
     */
    private static function term(string $key, string $term): array
    {
        return ['key' => $key, 'value' => '', 'term' => $term];
    }

    /**
     * The underwriting rules the applications queue is decided under: the coverage offers are sized
     * to, the sealed baseline every listing needs (AUD-R03), the coverage below which an application
     * is rejected, and the flat return band.
     *
     * @return list<PolicyItem>
     */
    public static function underwriting(): array
    {
        $coverage = fn (string $ratio): string => BigRational::of($ratio)->toScale(2).'×';

        return [['key' => 'target_dscr', 'value' => $coverage(LoanCapacity::TARGET_COVERAGE)], self::term('listing_audit', 'every_deal'),
            ['key' => 'audit_routing', 'value' => '≥ '.$coverage(LoanCapacity::MINIMUM_COVERAGE)],
            ['key' => 'rate_band', 'value' => FlatReturnPricing::MINIMUM_RETURN_PERCENT.'–'.FlatReturnPricing::MAXIMUM_RETURN_PERCENT.'%']];
    }

    /**
     * The fees the Payment Center collects: the platform fee, the Audit Partner's share of it (one
     * rate in Kigali and upcountry, FEE-R02), the listing and per-repayment fees the BRS leaves at
     * zero, and the seller's resale fee.
     *
     * @return list<PolicyItem>
     */
    public static function fees(): array
    {
        $percent = fn (int $basisPoints): string => rtrim(rtrim(number_format($basisPoints / 100, 2, '.', ''), '0'), '.').'%';

        return [['key' => 'platform_fee', 'value' => $percent(PlatformFee::FEE_BPS)],
            ['key' => 'cpa_share', 'value' => $percent(PlatformFee::AUDIT_PARTNER_SHARE_BPS)],
            ['key' => 'cpa_share_upcountry', 'value' => $percent(PlatformFee::AUDIT_PARTNER_SHARE_BPS)],
            ['key' => 'listing_fee', 'value' => '0%'], ['key' => 'repayment_fee', 'value' => '0%'],
            ['key' => 'resale_fee', 'value' => $percent(SecondaryTerms::SELLER_FEE_BPS)]];
    }

    /**
     * The rules Compliance works under: no grace before a payment is late (COL-R02), default at day
     * 31 (COL-R06), the re-verification interval and automatic freeze the BRS leaves unset (KYC-R04
     * screens "periodically"; no freeze on default exists), and the minimum Investor age.
     *
     * @return list<PolicyItem>
     */
    public static function compliance(): array
    {
        return [self::term('miss_grace', 'late_from_due'), self::term('default_at', 'day_31'), self::term('kyc_reverify', 'not_set'),
            self::term('auto_freeze', 'not_set'), ['key' => 'minimum_age', 'value' => (string) InvestorVerificationCase::MINIMUM_AGE]];
    }

    /**
     * Who may apply: the revenue floor (UW-R01), the audited history a first-time issuer needs in
     * years, the RDB registration KYB requires (KYC-R03), and the concurrent-note limit the BRS
     * leaves open (OD-20).
     *
     * @return list<PolicyItem>
     */
    public static function businesses(): array
    {
        return [['key' => 'min_revenue', 'value' => self::rwf(self::MIN_ANNUAL_REVENUE)],
            ['key' => 'min_statement_years', 'value' => (string) intdiv(UnderwritingObservationWindow::FIRST_ISSUE_MONTHS, 12)],
            self::term('rdb_certificate', 'required'), self::term('max_notes', 'not_set')];
    }

    private static function rwf(int|string $amount): string
    {
        return 'RWF '.number_format((int) $amount);
    }

    /** @return list<PolicyRule> */
    public static function rules(): array
    {
        $tenors = FlatReturnPricing::TENORS;
        $bands = implode(' · ', array_map(fn (int $basisPoints): string => ($basisPoints / 100).'%', EarningsFeeBand::RATES));
        [$platform, $share, $upcountry, $listing, $repayment, $resale] = self::fees();
        $business = self::businesses();
        $compliance = self::compliance();

        return [...self::tab('fees', PlatformFee::VERSION, [$platform, $share, $upcountry]),
            ...self::tab('fees', EarningsFeeBand::POLICY_VERSION, [['key' => 'earnings_fee', 'value' => $bands]]),
            ...self::tab('fees', null, [self::brs(['key' => 'cpa_earnings_share', 'value' => self::CPA_EARNINGS_SHARE_PERCENT.'%'], 'FEE-R02'),
                self::brs($resale, 'FEE-R01'), self::brs($listing, 'FEE-R01'), self::brs($repayment, 'FEE-R01'),
                self::brs(['key' => 'application_fee', 'value' => self::rwf(0)], 'FEE-R01'),
                self::brs(self::term('withdrawal_fee', 'none'), 'LED-R04'), self::brs(self::term('loss_reserve', 'none'), 'GEN-R01')]),
            ...self::tab('underwriting', FlatReturnPricing::POLICY_VERSION, [...self::underwriting(),
                self::brs(['key' => 'min_rating', 'value' => self::MIN_RATING], 'UW-R04'),
                self::brs(['key' => 'revenue_cap', 'value' => self::REVENUE_CAP_PERCENT.'%'], 'UW-R03'),
                ['key' => 'min_principal', 'value' => self::rwf(LoanCapacity::MINIMUM_PRINCIPAL)],
                ['key' => 'max_principal', 'value' => self::rwf(LoanCapacity::MAXIMUM_PRINCIPAL)],
                ['key' => 'tenors', 'value' => min($tenors).'–'.max($tenors)]]),
            ...self::tab('business', UnderwritingObservationWindow::VERSION, [self::brs($business[0], 'UW-R01'),
                ['key' => 'first_issue_months', 'value' => (string) UnderwritingObservationWindow::FIRST_ISSUE_MONTHS],
                ['key' => 'repeat_issue_months', 'value' => (string) UnderwritingObservationWindow::REPEAT_ISSUE_MONTHS],
                self::brs($business[2], 'KYC-R03'), self::brs($business[3], 'OD-20')]),
            ...self::tab('kyc', null, [['key' => 'minimum_age', 'value' => (string) InvestorVerificationCase::MINIMUM_AGE],
                ['key' => 'identity_evidence', 'value' => (string) count(InvestorVerificationCase::SLOTS)], self::brs($compliance[2], 'KYC-R04')]),
            ...self::tab('penalties', null, [self::brs(self::term('late_charge', 'late_schedule'), 'COL-R02'), self::brs($compliance[0], 'COL-R02'),
                self::brs($compliance[1], 'COL-R06'), self::brs($compliance[3], 'COL-R06'),
                ['key' => 'complaint_acknowledge', 'value' => Complaint::ACKNOWLEDGE_HOURS.' h'],
                ['key' => 'complaint_resolve', 'value' => Complaint::RESOLVE_HOURS.' h']]),
            ...self::tab('limits', null, [['key' => 'min_investment', 'value' => self::rwf(UnitRights::UNIT_PRINCIPAL)],
                self::brs(['key' => 'concentration', 'value' => self::CONCENTRATION_PERCENT.'%'], 'INV-R01'),
                self::brs(['key' => 'raise_days', 'value' => (string) self::RAISE_DAYS], 'NOTE-R03')]),
            ...self::tab('limits', WithdrawalTerms::VERSION, [['key' => 'withdrawal_min', 'value' => self::rwf(WithdrawalTerms::MINIMUM)],
                ['key' => 'withdrawal_max', 'value' => self::rwf(WithdrawalTerms::MAXIMUM)]]),
            ...self::tab('limits', null, [['key' => 'resale_listing_days', 'value' => (string) intdiv(SecondaryTerms::LISTING_SECONDS, 86400)],
                ['key' => 'resale_reservation_minutes', 'value' => (string) intdiv(SecondaryTerms::RESERVATION_SECONDS, 60)],
                self::brs(['key' => 'cpa_radius', 'value' => self::CPA_RADIUS_KM.' km'], 'AUD-R02'),
                self::brs(['key' => 'sandbox_businesses', 'value' => number_format(self::SANDBOX['businesses'])], 'SBX-R01'),
                self::brs(['key' => 'sandbox_investors', 'value' => number_format(self::SANDBOX['investors'])], 'SBX-R01'),
                self::brs(['key' => 'sandbox_principal', 'value' => self::rwf(self::SANDBOX['principal'])], 'SBX-R01')])];
    }

    /**
     * A rule whose value BRS v2.0 fixes rather than an engine constant, carrying its rule ID as its
     * version (`brs-2.0-uw-r01`).
     *
     * @param  PolicyItem  $item
     * @return array{key: string, value: string, term?: string, version: string}
     */
    private static function brs(array $item, string $rule): array
    {
        return [...$item, 'version' => 'brs-2.0-'.strtolower($rule)];
    }

    /**
     * @param  list<array{key: string, value: string, term?: string, version?: string}>  $items
     * @return list<PolicyRule>
     */
    private static function tab(string $tab, ?string $version, array $items): array
    {
        return array_map(fn (array $item): array => ['tab' => $tab, ...$item, 'version' => $item['version'] ?? $version], $items);
    }
}
