<?php

declare(strict_types=1);

namespace App\Domain\Business;

use Brick\Math\BigInteger;
use Brick\Math\BigRational;
use DateTimeImmutable;

/**
 * The automatic prospectus gates of BRS v3.0 PRO-R03, decided by #524: gates P-G01 to P-G11 must pass
 * before the official representative may sign, and that signature completes P-G12. There is no review
 * queue and no staff override (GEN-R04); every failure names its section and the exact fix.
 *
 * @phpstan-type UseOfFundsLine array{label: string, amount: string, quote_amount: string|null}
 * @phpstan-type ForecastMonth array{month: string, revenue: string, costs: string, contract_attached: bool}
 * @phpstan-type Profile array{name: string, role: string, text: string}
 * @phpstan-type Risk array{text: string, concentration: bool}
 * @phpstan-type Inputs array{purpose: string, use_of_funds: array<int, UseOfFundsLine>, forecast: array<int, ForecastMonth>, assumptions: string, downside: string,
 *     profiles: array<int, Profile>, largest_customer_share_percent: int|null, risks: array<int, Risk>}
 * @phpstan-type Facts array{principal: string, term_months: int, instalment: string, verified_monthly_revenue: string, latest_sealed_report_at: string|null,
 *     registered_names: list<string>, signed: bool}
 * @phpstan-type Gate array{id: string, section: string, passed: bool, fix: string|null}
 */
final class ProspectusGates
{
    public const string POLICY_VERSION = 'brs-v3.0-pro-r03';

    /** The sixteen PRO-R01 sections, in their fixed order. */
    public const array SECTIONS = ['offer_summary', 'business', 'purpose', 'financial_performance', 'forecast', 'management', 'terms', 'schedule',
        'band_returns', 'fees', 'rating', 'investor_rights', 'risks', 'cpa_statement', 'declaration', 'rozine_statement'];

    /** The platform fee taken from principal at disbursement, as a fraction. */
    public const string PLATFORM_FEE = '3/100';

    public const int REPORT_FRESHNESS_DAYS = 35;

    public const int PURPOSE_MINIMUM = 80;

    public const int PURPOSE_MAXIMUM = 600;

    public const int PROFILE_MINIMUM_WORDS = 40;

    public const int PROFILE_MAXIMUM_WORDS = 300;

    public const int TEXT_MAXIMUM = 4000;

    public const int MINIMUM_RISKS = 3;

    public const int CONCENTRATION_PERCENT = 25;

    /** A use-of-funds line at or above this share of principal needs a quote or invoice. */
    public const string QUOTED_LINE_SHARE = '1/5';

    public const string QUOTE_TOLERANCE = '1/20';

    public const string FORECAST_TOLERANCE = '1/4';

    public const string DOWNSIDE_COVERAGE = '5/4';

    /** Profile roles PRO-R02 requires: every director, every 10%+ owner and the official representative. */
    public const array PROFILE_ROLES = ['director', 'owner', 'representative'];

    /** P-G10: claims a prospectus may never make, in English, Kinyarwanda and French. "Not guaranteed" stays allowed. */
    public const array PROHIBITED_CLAIMS = ['guaranteed return', 'guaranteed returns', 'guaranteed profit', 'guaranteed income', 'risk-free', 'risk free',
        'no risk', 'safe investment', 'cannot lose', '100% safe', 'nta ngaruka', 'inyungu yizewe', 'rendement garanti', 'sans risque', 'aucun risque',
        'placement sûr'];

    /**
     * Evaluates every gate. P-G12 passes only once the representative has signed, and signing is
     * unlocked only when P-G01 to P-G11 pass.
     *
     * @param  Inputs  $inputs
     * @param  Facts  $facts
     * @return array{policy_version: string, gates: list<Gate>, may_sign: bool, publishable: bool}
     */
    public function evaluate(array $inputs, array $facts, DateTimeImmutable $now): array
    {
        $gates = [
            $this->complete($inputs, $facts),
            $this->freshReport($facts, $now),
            $this->namesMatch($inputs, $facts),
            $this->fundsBalance($inputs, $facts),
            $this->quotesBackLargeLines($inputs, $facts),
            $this->forecastWithinRange($inputs, $facts),
            $this->downsideCoversWeakMonths($inputs, $facts),
            $this->concentrationDisclosed($inputs),
            $this->enoughRisks($inputs),
            $this->noProhibitedClaims($inputs),
            $this->lengthsWithinLimits($inputs),
        ];
        $maySign = array_all($gates, fn (array $gate): bool => $gate['passed']);
        $gates[] = $this->gate('P-G12', 'declaration', $maySign && $facts['signed'],
            $maySign ? 'The official representative signs the declaration to publish.' : 'Signing opens once every other gate passes.');

        return ['policy_version' => self::POLICY_VERSION, 'gates' => $gates, 'may_sign' => $maySign && ! $facts['signed'],
            'publishable' => $maySign && $facts['signed']];
    }

    /**
     * @param  Inputs  $inputs
     * @param  Facts  $facts
     * @return Gate
     */
    private function complete(array $inputs, array $facts): array
    {
        $missing = match (true) {
            trim($inputs['purpose']) === '' => 'purpose',
            $inputs['use_of_funds'] === [] => 'purpose',
            count($inputs['forecast']) !== $facts['term_months'] => 'forecast',
            trim($inputs['assumptions']) === '' || trim($inputs['downside']) === '' => 'forecast',
            ! in_array('representative', array_column($inputs['profiles'], 'role'), true) => 'management',
            $inputs['largest_customer_share_percent'] === null => 'risks',
            default => null,
        };

        return $this->gate('P-G01', $missing ?? 'offer_summary', $missing === null, match ($missing) {
            null => null,
            'purpose' => 'Describe the purpose and add at least one use-of-funds line.',
            'forecast' => 'Give a forecast for every month of the term, with your assumptions and downside.',
            'management' => 'Add the official representative\'s profile.',
            default => 'Enter your largest customer\'s share of revenue.',
        });
    }

    /**
     * @param  Facts  $facts
     * @return Gate
     */
    private function freshReport(array $facts, DateTimeImmutable $now): array
    {
        $sealed = $facts['latest_sealed_report_at'] === null ? null : new DateTimeImmutable($facts['latest_sealed_report_at']);
        $fresh = $sealed !== null && $sealed <= $now && $sealed->modify('+'.self::REPORT_FRESHNESS_DAYS.' days') >= $now;

        return $this->gate('P-G02', 'cpa_statement', $fresh, $fresh ? null : 'Your latest sealed CPA report must be at most 35 days old. Your next monthly audit will refresh it.');
    }

    /**
     * @param  Inputs  $inputs
     * @param  Facts  $facts
     * @return Gate
     */
    private function namesMatch(array $inputs, array $facts): array
    {
        $registered = array_map(self::name(...), $facts['registered_names']);
        $unknown = array_values(array_filter(array_column($inputs['profiles'], 'name'), fn (string $name): bool => ! in_array(self::name($name), $registered, true)));

        return $this->gate('P-G03', 'management', $unknown === [], $unknown === [] ? null
            : 'Use each person\'s name exactly as it appears in KYC and the RDB register: '.implode(', ', $unknown).'.');
    }

    /**
     * @param  Inputs  $inputs
     * @param  Facts  $facts
     * @return Gate
     */
    private function fundsBalance(array $inputs, array $facts): array
    {
        $principal = BigInteger::of($facts['principal']);
        $fee = BigRational::of(self::PLATFORM_FEE)->multipliedBy($principal);
        $lines = array_reduce($inputs['use_of_funds'], fn (BigInteger $sum, array $line): BigInteger => $sum->plus(self::amount($line['amount'])), BigInteger::zero());
        $balanced = $fee->plus($lines)->isEqualTo($principal);
        $expected = $fee->getNumerator()->mod($fee->getDenominator())->isZero() ? $principal->minus($fee->toBigInteger()) : null;

        return $this->gate('P-G04', 'purpose', $balanced, $balanced ? null : ($expected === null
            ? 'Choose a principal whose 3% platform fee is a whole franc.'
            : 'Use-of-funds lines must add up to RWF '.number_format($expected->toInt()).', the principal less the 3% platform fee.'));
    }

    /**
     * @param  Inputs  $inputs
     * @param  Facts  $facts
     * @return Gate
     */
    private function quotesBackLargeLines(array $inputs, array $facts): array
    {
        $threshold = BigRational::of(self::QUOTED_LINE_SHARE)->multipliedBy($facts['principal']);
        $unbacked = [];
        foreach ($inputs['use_of_funds'] as $line) {
            $amount = self::amount($line['amount']);
            if ($amount->toBigRational()->isLessThan($threshold)) {
                continue;
            }
            $quote = $line['quote_amount'] === null ? null : self::amount($line['quote_amount']);
            $allowed = BigRational::of(self::QUOTE_TOLERANCE)->multipliedBy($amount);
            if ($quote === null || $quote->minus($amount)->abs()->toBigRational()->isGreaterThan($allowed)) {
                $unbacked[] = $line['label'];
            }
        }

        return $this->gate('P-G05', 'purpose', $unbacked === [], $unbacked === [] ? null
            : 'Attach a quote or invoice within 5% of the amount for: '.implode(', ', $unbacked).'.');
    }

    /**
     * @param  Inputs  $inputs
     * @param  Facts  $facts
     * @return Gate
     */
    private function forecastWithinRange(array $inputs, array $facts): array
    {
        $average = BigInteger::of($facts['verified_monthly_revenue']);
        $allowed = BigRational::of(self::FORECAST_TOLERANCE)->multipliedBy($average);
        $outside = array_values(array_filter($inputs['forecast'], fn (array $month): bool => ! $month['contract_attached']
            && self::amount($month['revenue'])->minus($average)->abs()->toBigRational()->isGreaterThan($allowed)));

        return $this->gate('P-G06', 'forecast', $outside === [], $outside === [] ? null
            : 'Keep each month within 25% of your verified average of RWF '.number_format($average->toInt()).', or attach a signed contract for: '
                .implode(', ', array_column($outside, 'month')).'.');
    }

    /**
     * @param  Inputs  $inputs
     * @param  Facts  $facts
     * @return Gate
     */
    private function downsideCoversWeakMonths(array $inputs, array $facts): array
    {
        $instalment = BigInteger::of($facts['instalment']);
        $weak = array_filter($inputs['forecast'], fn (array $month): bool => $instalment->isPositive()
            && BigRational::of(self::amount($month['revenue'])->minus(self::amount($month['costs'])))->dividedBy($instalment)->isLessThan(self::DOWNSIDE_COVERAGE));
        $passed = $weak === [] || trim($inputs['downside']) !== '';

        return $this->gate('P-G07', 'forecast', $passed, $passed ? null : 'At least one forecast month falls below 1.25 coverage. Explain your downside plan.');
    }

    /**
     * @param  Inputs  $inputs
     * @return Gate
     */
    private function concentrationDisclosed(array $inputs): array
    {
        $required = ($inputs['largest_customer_share_percent'] ?? 0) >= self::CONCENTRATION_PERCENT;
        $passed = ! $required || in_array(true, array_column($inputs['risks'], 'concentration'), true);

        return $this->gate('P-G08', 'risks', $passed, $passed ? null : 'Your largest customer is 25% or more of revenue, so the concentration risk must stay in your risk factors.');
    }

    /**
     * @param  Inputs  $inputs
     * @return Gate
     */
    private function enoughRisks(array $inputs): array
    {
        $business = array_filter($inputs['risks'], fn (array $risk): bool => ! $risk['concentration'] && trim($risk['text']) !== '');
        $passed = count($business) >= self::MINIMUM_RISKS;

        return $this->gate('P-G09', 'risks', $passed, $passed ? null : 'Add at least 3 risks specific to your business. Pick from the common risks for your sector and edit them.');
    }

    /**
     * @param  Inputs  $inputs
     * @return Gate
     */
    private function noProhibitedClaims(array $inputs): array
    {
        $found = [];
        foreach (self::texts($inputs) as $section => $texts) {
            foreach ($texts as $text) {
                foreach (self::PROHIBITED_CLAIMS as $claim) {
                    if (preg_match('/(?<![\p{L}\p{N}])'.preg_quote($claim, '/').'(?![\p{L}\p{N}])/iu', $text) === 1) {
                        $found[$section] ??= $claim;
                    }
                }
            }
        }
        $section = array_key_first($found);

        return $this->gate('P-G10', $section ?? 'risks', $found === [], $section === null ? null
            : 'Remove the claim "'.$found[$section].'". A prospectus may never promise a guaranteed or risk-free return.');
    }

    /**
     * @param  Inputs  $inputs
     * @return Gate
     */
    private function lengthsWithinLimits(array $inputs): array
    {
        $purpose = mb_strlen(trim($inputs['purpose']));
        $failure = match (true) {
            $purpose < self::PURPOSE_MINIMUM || $purpose > self::PURPOSE_MAXIMUM => ['purpose', 'Write the purpose in 80 to 600 characters.'],
            array_any($inputs['profiles'], fn (array $profile): bool => self::words($profile['text']) < self::PROFILE_MINIMUM_WORDS
                || self::words($profile['text']) > self::PROFILE_MAXIMUM_WORDS) => ['management', 'Write each profile in 40 to 300 words.'],
            mb_strlen($inputs['assumptions']) > self::TEXT_MAXIMUM || mb_strlen($inputs['downside']) > self::TEXT_MAXIMUM => ['forecast', 'Keep assumptions and downside to 4,000 characters each.'],
            array_any($inputs['risks'], fn (array $risk): bool => mb_strlen($risk['text']) > self::TEXT_MAXIMUM) => ['risks', 'Keep each risk to 4,000 characters.'],
            default => null,
        };

        return $this->gate('P-G11', $failure[0] ?? 'offer_summary', $failure === null, $failure[1] ?? null);
    }

    /**
     * @param  Inputs  $inputs
     * @return array<string, list<string>>
     */
    private static function texts(array $inputs): array
    {
        return ['purpose' => [$inputs['purpose'], ...array_column($inputs['use_of_funds'], 'label')], 'forecast' => [$inputs['assumptions'], $inputs['downside']],
            'management' => array_column($inputs['profiles'], 'text'), 'risks' => array_column($inputs['risks'], 'text')];
    }

    /** @return Gate */
    private function gate(string $id, string $section, bool $passed, ?string $fix): array
    {
        return ['id' => $id, 'section' => $section, 'passed' => $passed, 'fix' => $passed ? null : $fix];
    }

    private static function amount(string $value): BigInteger
    {
        return preg_match('/^(0|[1-9][0-9]{0,17})$/D', $value) === 1 ? BigInteger::of($value) : BigInteger::zero();
    }

    private static function name(string $name): string
    {
        return mb_strtolower(trim((string) preg_replace('/\s+/u', ' ', $name)));
    }

    private static function words(string $text): int
    {
        return count(preg_split('/\s+/u', trim($text), flags: PREG_SPLIT_NO_EMPTY) ?: []);
    }
}
