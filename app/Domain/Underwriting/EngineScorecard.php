<?php

declare(strict_types=1);

namespace App\Domain\Underwriting;

use Brick\Math\BigRational;
use Brick\Math\RoundingMode;

/** Internal replay result. Scores and component points must never enter marketplace Resources. */
final class EngineScorecard
{
    public const VERSION = 'engine-score-v1';

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
        $conduct = BigRational::of(50);
        if ($hasRozineHistory) {
            $instalments = $this->conductScore($instalmentConduct);
            $reports = $this->conductScore($reportConduct);
            if ($instalments === null || $reports === null) {
                $result['reason_codes'] = ['CONDUCT_EVIDENCE_REQUIRED'];

                return $result;
            }
            $conduct = $instalments->multipliedBy('7/10')->plus($reports->multipliedBy('3/10'));
        }
        $components = [
            'coverage' => $this->up($coverage, '1', '3/2'),
            'cfads_margin' => $this->up($cfadsMargin, '1/20', '1/4'),
            'nocf_stability' => BigRational::of(100)->minus($this->up($relativeMad, '1/10', '3/5')),
            'positive_months' => $this->up(BigRational::ofFraction($positiveMonths, $verifiedMonths), '1/2', '1'),
            'history_depth' => $this->up(BigRational::of($verifiedMonths), '6', '18'),
            'conduct' => $conduct,
        ];
        $weights = ['coverage' => 30, 'cfads_margin' => 20, 'nocf_stability' => 20, 'positive_months' => 10, 'history_depth' => 5, 'conduct' => 15];
        $score = BigRational::zero();
        foreach ($components as $name => $component) {
            $score = $score->plus($component->multipliedBy($weights[$name])->dividedBy(100));
        }
        $result['uncapped_score'] = ExactFinancialValue::ratio($score);
        if ($postGraceArrears || $reportingBreach) {
            $score = BigRational::min($score, 58);
            $result['reason_codes'][] = 'ADVERSE_CONDUCT_CAP';
        }
        if ($defaulted || $daysPastDue >= 30) {
            $score = BigRational::min($score, 38);
            $result['reason_codes'][] = 'DEFAULT_CAP';
        }
        $rating = BigRational::min(5, BigRational::max(0, $score->dividedBy(20)))->toScale(1, RoundingMode::HalfUp);
        $result['rating'] = (string) $rating;
        $result['band'] = match (true) {
            $rating->isGreaterThanOrEqualTo(4) => 'Strong',
            $rating->isGreaterThanOrEqualTo(3) => 'Stable',
            $rating->isGreaterThanOrEqualTo(2) => 'Weak',
            default => 'Distressed',
        };
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
