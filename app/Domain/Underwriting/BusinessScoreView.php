<?php

declare(strict_types=1);

namespace App\Domain\Underwriting;

use Brick\Math\BigInteger;
use Brick\Math\BigRational;
use Brick\Math\RoundingMode;

/**
 * What a Business may see of its own underwriting, and nobody else: its score in four factors and
 * how its capacity was sized. Every figure comes from the retained quote its published rating was
 * underwritten on; capacity is the engine's own sizing rerun without the amount that application
 * asked for, so it reads as what the Business could raise on those facts, never a new decision.
 *
 * @phpstan-type Ratio array{numerator: string, denominator: string}
 * @phpstan-type Money array{currency: string, amount: string}
 */
final class BusinessScoreView
{
    /**
     * Each factor is the weighted mean of its engine components (EngineScorecard::WEIGHTS). The
     * engine measures no growth, so the fourth factor is the depth of the verified track record.
     */
    public const array FACTORS = ['financial_health' => ['coverage', 'cfads_margin'], 'repayment_history' => ['conduct'],
        'statement_consistency' => ['nocf_stability', 'positive_months'], 'track_record' => ['history_depth']];

    public function __construct(private LoanCapacity $capacity) {}

    /**
     * @param  array<string, Ratio>  $components
     * @return list<array{key: string, score: int}>
     */
    public static function factors(array $components): array
    {
        $factors = [];
        foreach (self::FACTORS as $key => $names) {
            $points = BigRational::zero();
            $weight = 0;
            foreach ($names as $name) {
                $points = $points->plus(self::rational($components[$name])->multipliedBy(EngineScorecard::WEIGHTS[$name]));
                $weight += EngineScorecard::WEIGHTS[$name];
            }
            $factors[] = ['key' => $key, 'score' => $points->dividedBy($weight)->toScale(0, RoundingMode::HalfUp)->toBigInteger()->toInt()];
        }

        return $factors;
    }

    /**
     * The three limits on one note are what the cash flow carries at the target cover, the room
     * left under the exposure limit, and the policy maximum; the smallest binds. Capacity is the
     * engine's offer at that amount, and headroom is what remains of it beside funded notes.
     *
     * @param  array{cfads: Ratio, exposure_limit: Ratio, committed_exposure: Ratio, remaining_room: Ratio, repeat_projections: list<array{cfads: Ratio}>}  $cashFlow
     * @param  array{percent: string, fraction: Ratio}  $pricing
     * @param  Ratio  $rawCapacity
     * @return array<string, mixed>
     */
    public function sizing(array $cashFlow, array $pricing, array $rawCapacity, int $termMonths, BigInteger $outstanding): array
    {
        $cfads = self::rational($cashFlow['cfads']);
        $room = self::rational($cashFlow['remaining_room']);
        $limits = ['cash_flow' => BigInteger::max(0, ExactFinancialValue::halfUp(self::rational($rawCapacity))), 'exposure' => BigInteger::max(0, ExactFinancialValue::floor($room)),
            'policy_max' => BigInteger::of(LoanCapacity::MAXIMUM_PRINCIPAL)];
        $ceiling = BigInteger::min(...array_values($limits));
        $binding = array_find_key($limits, fn (BigInteger $limit): bool => $limit->isEqualTo($ceiling));
        $approved = BigInteger::zero();
        if ($ceiling->isGreaterThanOrEqualTo(LoanCapacity::MINIMUM_PRINCIPAL)) {
            $offer = $this->capacity->evaluate((string) $ceiling, $termMonths, self::rational($pricing['fraction']), $cfads, $room,
                array_map(fn (array $month): BigRational => self::rational($month['cfads']), $cashFlow['repeat_projections']))['offer'];
            $approved = BigInteger::of($offer['principal']['amount'] ?? '0');
        }
        $headroom = BigInteger::max(0, $approved->minus($outstanding))->quotient(LoanCapacity::ISSUANCE_UNIT)->multipliedBy(LoanCapacity::ISSUANCE_UNIT);

        return ['cash_per_month' => ExactFinancialValue::money(ExactFinancialValue::halfUp($cfads)),
            'target_cover' => (string) BigRational::of(LoanCapacity::TARGET_COVERAGE)->toScale(2), 'term_months' => $termMonths, 'rate_percent' => $pricing['percent'],
            'limits' => [
                ['key' => 'cash_flow', 'value' => ExactFinancialValue::money($limits['cash_flow']), 'binding' => $binding === 'cash_flow'],
                ['key' => 'exposure', 'value' => ExactFinancialValue::money($limits['exposure']), 'binding' => $binding === 'exposure',
                    'limit' => ExactFinancialValue::money(ExactFinancialValue::floor(self::rational($cashFlow['exposure_limit']))),
                    'committed' => ExactFinancialValue::money(ExactFinancialValue::halfUp(self::rational($cashFlow['committed_exposure'])))],
                ['key' => 'policy_max', 'value' => ExactFinancialValue::money($limits['policy_max']), 'binding' => $binding === 'policy_max'],
            ],
            'approved' => ExactFinancialValue::money($approved), 'headroom' => ExactFinancialValue::money($headroom)];
    }

    /** @param Ratio $ratio */
    private static function rational(array $ratio): BigRational
    {
        return BigRational::of($ratio['numerator'].'/'.$ratio['denominator']);
    }
}
