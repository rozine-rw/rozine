<?php

declare(strict_types=1);

namespace App\Domain\Underwriting;

use App\Domain\Operations\CommandRejection;

/**
 * @phpstan-import-type History from ApplicationUnderwriting
 * @phpstan-import-type Conduct from ApplicationUnderwriting
 * @phpstan-import-type ObligationInput from CashFlowEvidence
 * @phpstan-import-type RepeatEligibility from CashFlowEvidence
 *
 * @phpstan-type Facts array{history: History|null, obligations: list<ObligationInput>, restriction_active: bool}
 */
final class BorrowerCreditFacts
{
    /**
     * @param  array<string, mixed>  $input
     * @return Facts
     */
    public function normalize(array $input): array
    {
        $this->keys($input, ['history', 'obligations', 'restriction_active']);
        if (! is_array($input['obligations']) || ! array_is_list($input['obligations']) || ! is_bool($input['restriction_active'])) {
            $this->invalid();
        }
        $obligations = [];
        $ids = [];
        foreach ($input['obligations'] as $obligation) {
            if (! is_array($obligation)) {
                $this->invalid();
            }
            $this->keys($obligation, ['id', 'principal', 'service_by_month']);
            $id = $obligation['id'];
            if (! is_string($id) || trim($id) === '' || strlen($id) > 255 || ! mb_check_encoding($id, 'UTF-8')
                || preg_match('/[\p{Cc}\p{Cf}]/u', $id) || in_array($id, $ids, true) || ! is_array($obligation['service_by_month']) || $obligation['service_by_month'] === []) {
                $this->invalid();
            }
            $ids[] = $id;
            $schedule = [];
            foreach ($obligation['service_by_month'] as $month => $amount) {
                if (! is_string($month) || ! preg_match('/^[1-9][0-9]{3}-(0[1-9]|1[0-2])$/D', $month)) {
                    $this->invalid();
                }
                $schedule[$month] = $this->money($amount);
            }
            ksort($schedule);
            $obligations[] = ['id' => $id, 'principal' => $this->money($obligation['principal']), 'service_by_month' => $schedule];
        }
        usort($obligations, fn (array $left, array $right): int => strcmp($left['id'], $right['id']));

        return ['history' => $this->history($input['history']), 'obligations' => $obligations, 'restriction_active' => $input['restriction_active']];
    }

    /** @return History|null */
    private function history(mixed $input): ?array
    {
        if ($input === null) {
            return null;
        }
        if (! is_array($input)) {
            $this->invalid();
        }
        $this->keys($input, ['has_rozine_history', 'repeat_eligibility', 'instalment_conduct', 'report_conduct', 'post_grace_arrears', 'reporting_breach', 'defaulted', 'days_past_due']);
        foreach (['has_rozine_history', 'post_grace_arrears', 'reporting_breach', 'defaulted'] as $key) {
            if (! is_bool($input[$key])) {
                $this->invalid();
            }
        }
        if (! is_int($input['days_past_due']) || $input['days_past_due'] < 0) {
            $this->invalid();
        }
        $repeat = $this->repeat($input['repeat_eligibility']);
        $instalments = $this->conduct($input['instalment_conduct']);
        $reports = $this->conduct($input['report_conduct']);
        if (! $input['has_rozine_history'] && ($repeat !== null || $instalments !== null || $reports !== null
            || $input['post_grace_arrears'] || $input['reporting_breach'] || $input['defaulted'] || $input['days_past_due'] !== 0)) {
            $this->invalid();
        }

        return ['has_rozine_history' => $input['has_rozine_history'], 'repeat_eligibility' => $repeat, 'instalment_conduct' => $instalments,
            'report_conduct' => $reports, 'post_grace_arrears' => $input['post_grace_arrears'], 'reporting_breach' => $input['reporting_breach'],
            'defaulted' => $input['defaulted'], 'days_past_due' => $input['days_past_due']];
    }

    /** @return RepeatEligibility|null */
    private function repeat(mixed $input): ?array
    {
        if ($input === null) {
            return null;
        }
        if (! is_array($input)) {
            $this->invalid();
        }
        $this->keys($input, ['baseline_passed', 'settled_notes', 'late_payments', 'gap_audits_complete', 'automated_collection']);
        foreach (['baseline_passed', 'gap_audits_complete', 'automated_collection'] as $key) {
            if (! is_bool($input[$key])) {
                $this->invalid();
            }
        }
        foreach (['settled_notes', 'late_payments'] as $key) {
            if (! is_int($input[$key]) || $input[$key] < 0) {
                $this->invalid();
            }
        }

        return ['baseline_passed' => $input['baseline_passed'], 'settled_notes' => $input['settled_notes'], 'late_payments' => $input['late_payments'],
            'gap_audits_complete' => $input['gap_audits_complete'], 'automated_collection' => $input['automated_collection']];
    }

    /** @return Conduct|null */
    private function conduct(mixed $input): ?array
    {
        if ($input === null) {
            return null;
        }
        if (! is_array($input)) {
            $this->invalid();
        }
        $this->keys($input, ['on_time', 'total']);
        if (! is_int($input['on_time']) || ! is_int($input['total']) || $input['on_time'] < 0 || $input['total'] < $input['on_time']) {
            $this->invalid();
        }

        return ['on_time' => $input['on_time'], 'total' => $input['total']];
    }

    private function money(mixed $amount): string
    {
        if (! is_string($amount)) {
            $this->invalid();
        }
        try {
            ExactFinancialValue::amount($amount);
        } catch (UnderwritingViolation) {
            $this->invalid();
        }

        return $amount;
    }

    /**
     * @param  array<array-key, mixed>  $input
     * @param  list<string>  $expected
     */
    private function keys(array $input, array $expected): void
    {
        $keys = array_keys($input);
        sort($keys);
        sort($expected);
        if ($keys !== $expected) {
            $this->invalid();
        }
    }

    private function invalid(): never
    {
        throw new CommandRejection('CREDIT_FACTS_INVALID', 422);
    }
}
