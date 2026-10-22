<?php

declare(strict_types=1);

namespace App\Domain\Underwriting;

use Brick\Math\BigRational;
use Brick\Math\RoundingMode;

/** Internal replay result. Scores and component points must never enter marketplace Resources. */
final class EngineScorecard
{
    public const VERSION = 'engine-score-v1';

    /** Each component's share of the 0–100 score, in percent. */
    public const array WEIGHTS = ['coverage' => 30, 'cfads_margin' => 20, 'nocf_stability' => 20, 'positive_months' => 10, 'history_depth' => 5, 'conduct' => 15];

    /**
     * The range each measured component is scaled over, from 0 points at the low end to 100 at the
     * high end; stability is scored inversely, so the least variable cash flow scores highest.
     */
    public const array RANGES = ['coverage' => ['1', '3/2'], 'cfads_margin' => ['1/20', '1/4'], 'nocf_stability' => ['1/10', '3/5'],
        'positive_months' => ['1/2', '1'], 'history_depth' => ['6', '18']];

    /** Conduct before any Rozine history, and the weights of on-time instalments and reports after it. */
    public const int NEW_ISSUER_CONDUCT = 50;

    public const string INSTALMENT_CONDUCT_WEIGHT = '7/10';

    public const string REPORT_CONDUCT_WEIGHT = '3/10';

    /** The score caps for arrears after grace or a reporting breach, and for default or 30+ days past due. */
    public const int ADVERSE_CONDUCT_CAP = 58;

    public const int DEFAULT_CAP = 38;

    /** The lowest rating (out of 5) of each band, best first; anything lower is Distressed. */
    public const array BANDS = ['Strong' => '4', 'Stable' => '3', 'Weak' => '2'];

    /**
     * @param  array{on_time: int, total: int}|null  $instalmentConduct
     * @param  array{on_time: int, total: int}|null  $reportConduct
     * @return array<string, mixed>
     */
    public function evaluate(
        ?BigRational $coverage,
        ?BigRational $cfadsMargin,
        ?BigRational $relativeMad,
        int $positiveMonths,
        int $verifiedMonths,
        bool $hasRozineHistory,
        ?array $instalmentConduct = null,
        ?array $reportConduct = null,
        bool $postGraceArrears = false,
        bool $reportingBreach = false,
        bool $defaulted = false,
        int $daysPastDue = 0,
    ): array {
        $result = [
            'scorecard_version' => self::VERSION,
            'rating' => null, 'band' => 'Unrated', 'components' => [],
            'uncapped_score' => null, 'score' => null, 'reason_codes' => [],
        ];
        if ($coverage === null || $cfadsMargin === null || $relativeMad === null || $verifiedMonths < 6
            || $positiveMonths < 0 || $positiveMonths > $verifiedMonths || $daysPastDue < 0 || $relativeMad->isNegative()) {
            $result['reason_codes'] = ['UNDERWRITING_EVIDENCE_REQUIRED'];

            return $result;
        }
        $conduct = BigRational::of(self::NEW_ISSUER_CONDUCT);
        if ($hasRozineHistory) {
            $instalments = $this->conductScore($instalmentConduct);
            $reports = $this->conductScore($reportConduct);
            if ($instalments === null || $reports === null) {
                $result['reason_codes'] = ['CONDUCT_EVIDENCE_REQUIRED'];

                return $result;
            }
            $conduct = $instalments->multipliedBy(self::INSTALMENT_CONDUCT_WEIGHT)->plus($reports->multipliedBy(self::REPORT_CONDUCT_WEIGHT));
        }
        $components = [
            'coverage' => $this->up($coverage, ...self::RANGES['coverage']),
            'cfads_margin' => $this->up($cfadsMargin, ...self::RANGES['cfads_margin']),
            'nocf_stability' => BigRational::of(100)->minus($this->up($relativeMad, ...self::RANGES['nocf_stability'])),
            'positive_months' => $this->up(BigRational::ofFraction($positiveMonths, $verifiedMonths), ...self::RANGES['positive_months']),
            'history_depth' => $this->up(BigRational::of($verifiedMonths), ...self::RANGES['history_depth']),
            'conduct' => $conduct,
        ];
        $score = BigRational::zero();
        foreach ($components as $name => $component) {
            $score = $score->plus($component->multipliedBy(self::WEIGHTS[$name])->dividedBy(100));
        }
        $result['uncapped_score'] = ExactFinancialValue::ratio($score);
        if ($postGraceArrears || $reportingBreach) {
            $score = BigRational::min($score, self::ADVERSE_CONDUCT_CAP);
            $result['reason_codes'][] = 'ADVERSE_CONDUCT_CAP';
        }
        if ($defaulted || $daysPastDue >= 30) {
            $score = BigRational::min($score, self::DEFAULT_CAP);
            $result['reason_codes'][] = 'DEFAULT_CAP';
        }
        $rating = BigRational::min(5, BigRational::max(0, $score->dividedBy(20)))->toScale(1, RoundingMode::HalfUp);
        $result['rating'] = (string) $rating;
        $result['band'] = array_find_key(self::BANDS, fn (string $floor): bool => $rating->isGreaterThanOrEqualTo($floor)) ?? 'Distressed';
        $result['components'] = array_map(ExactFinancialValue::ratio(...), $components);
        $result['score'] = ExactFinancialValue::ratio($score);

        return $result;
    }

    private function up(BigRational $value, string $low, string $high): BigRational
    {
        return BigRational::min(1, BigRational::max(0, $value->minus($low)->dividedBy(BigRational::of($high)->minus($low))))->multipliedBy(100);
    }

    /** @param array{on_time: int, total: int}|null $record */
    private function conductScore(?array $record): ?BigRational
    {
        if ($record === null || $record['total'] <= 0 || $record['on_time'] < 0 || $record['on_time'] > $record['total']) {
            return null;
        }

        return BigRational::ofFraction($record['on_time'], $record['total'])->multipliedBy(100);
    }
}
