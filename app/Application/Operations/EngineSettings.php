<?php

declare(strict_types=1);

namespace App\Application\Operations;

use App\Domain\Disbursement\PlatformFee;
use App\Domain\Underwriting\EngineScorecard;
use App\Domain\Underwriting\FlatReturnPricing;
use App\Domain\Underwriting\LoanCapacity;
use App\Domain\Underwriting\UnderwritingObservationWindow;
use Brick\Math\BigRational;

/**
 * How the underwriting engine decides, read from the constants it runs on, for the console's Engines
 * section and the Ratings explainer. Each row names the version of the rule set it comes from.
 * Only the engines the platform runs are listed: sizing and pricing, and the Audit Partner's share
 * of the platform fee. Refinancing, the queue, partner floors and loss curves have no engine yet, so
 * their tabs stay empty rather than showing invented figures.
 *
 * @phpstan-type EngineRow array{tab: string, key: string, value: string, source: string}
 */
final class EngineSettings
{
    /** @return list<EngineRow> */
    public static function engines(): array
    {
        $coverage = fn (string $ratio): string => BigRational::of($ratio)->toScale(2).'×';
        $rwf = fn (int $amount): string => 'RWF '.number_format($amount);
        $percent = fn (int $basisPoints): string => rtrim(rtrim(number_format($basisPoints / 100, 2, '.', ''), '0'), '.').'%';
        $tenors = FlatReturnPricing::TENORS;
        $sizing = fn (string $key, string $value): array => ['tab' => 'sizing', 'key' => $key, 'value' => $value, 'source' => LoanCapacity::CALCULATOR_VERSION];
        $window = fn (string $key, string $value): array => ['tab' => 'sizing', 'key' => $key, 'value' => $value, 'source' => UnderwritingObservationWindow::VERSION];
        $pricing = fn (string $key, string $value): array => ['tab' => 'sizing', 'key' => $key, 'value' => $value, 'source' => FlatReturnPricing::POLICY_VERSION];
        $split = fn (string $key, string $value): array => ['tab' => 'cpa', 'key' => $key, 'value' => $value, 'source' => PlatformFee::VERSION];

        return [$sizing('target_dscr', $coverage(LoanCapacity::TARGET_COVERAGE)),
            $sizing('minimum_dscr', $coverage(LoanCapacity::MINIMUM_COVERAGE)),
            $sizing('repeat_monthly_dscr', $coverage(LoanCapacity::REPEAT_MONTHLY_COVERAGE)),
            $sizing('min_principal', $rwf(LoanCapacity::MINIMUM_PRINCIPAL)),
            $sizing('max_principal', $rwf(LoanCapacity::MAXIMUM_PRINCIPAL)),
            $sizing('issuance_unit', $rwf(LoanCapacity::ISSUANCE_UNIT)),
            $window('first_issue_months', (string) UnderwritingObservationWindow::FIRST_ISSUE_MONTHS),
            $window('repeat_issue_months', (string) UnderwritingObservationWindow::REPEAT_ISSUE_MONTHS),
            $pricing('tenors', min($tenors).'–'.max($tenors)),
            $pricing('rate_band', FlatReturnPricing::MINIMUM_RETURN_PERCENT.'–'.FlatReturnPricing::MAXIMUM_RETURN_PERCENT.'%'),
            $split('platform_fee', $percent(PlatformFee::FEE_BPS)),
            $split('cpa_share', $percent(PlatformFee::AUDIT_PARTNER_SHARE_BPS))];
    }

    /**
     * The rating scorecard: each component's weight and the range it is scaled over, the lowest
     * rating of each band, and what moves conduct and caps a rating.
     *
     * @return list<EngineRow>
     */
    public static function scorecard(): array
    {
        $percent = fn (string $ratio): string => (string) BigRational::of($ratio)->multipliedBy(100)->toScale(0).'%';
        $ranges = ['coverage' => fn (string $ratio): string => BigRational::of($ratio)->toScale(2).'×', 'cfads_margin' => $percent,
            'nocf_stability' => $percent, 'positive_months' => $percent, 'history_depth' => fn (string $months): string => $months];
        $row = fn (string $tab, string $key, string $value): array => ['tab' => $tab, 'key' => $key, 'value' => $value, 'source' => EngineScorecard::VERSION];
        $rows = [];
        foreach (EngineScorecard::WEIGHTS as $component => $weight) {
            $range = EngineScorecard::RANGES[$component] ?? null;
            $format = $ranges[$component] ?? null;
            $rows[] = $row('weights', $component, $weight.'%'.($range === null || $format === null ? '' : ' · '.$format($range[0]).'–'.$format($range[1])));
        }
        $rating = fn (int $score): string => (string) BigRational::of($score)->dividedBy(20)->toScale(1);
        $rows[] = $row('movers', 'new_issuer_conduct', (string) EngineScorecard::NEW_ISSUER_CONDUCT);
        $rows[] = $row('movers', 'instalment_conduct', $percent(EngineScorecard::INSTALMENT_CONDUCT_WEIGHT));
        $rows[] = $row('movers', 'report_conduct', $percent(EngineScorecard::REPORT_CONDUCT_WEIGHT));
        $rows[] = $row('movers', 'adverse_conduct_cap', $rating(EngineScorecard::ADVERSE_CONDUCT_CAP));
        $rows[] = $row('movers', 'default_cap', $rating(EngineScorecard::DEFAULT_CAP));
        foreach (EngineScorecard::BANDS as $band => $floor) {
            $rows[] = $row('bands', strtolower($band), '≥ '.BigRational::of($floor)->toScale(1));
        }
        $rows[] = $row('bands', 'distressed', '< '.BigRational::of(EngineScorecard::BANDS['Weak'])->toScale(1));

        return $rows;
    }
}
