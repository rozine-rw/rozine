<?php

declare(strict_types=1);

namespace App\Domain\Auditor;

use App\Domain\Operations\CommandRejection;
use App\Domain\Underwriting\ExactFinancialValue;
use App\Domain\Underwriting\UnderwritingViolation;
use Brick\Math\BigDecimal;

/**
 * The procedure records observations and source pins, never a credit verdict. Source facts are
 * supplied by protected application ports; none of them are accepted from a step's form.
 *
 * @phpstan-type Source array{id: string, revision: int, sha256: string}
 * @phpstan-type Sources array{verification: Source|null, check_in: Source|null, photos: Source|null, declaration: Source|null, reported_stock: string|null, reported_cash: string|null, reported_units: string|null, financial_proofs: list<string>, inventory_proofs: list<string>, extra_photos: list<string>, ledger_sources?: array<string, Source>}
 * @phpstan-type Draft array{note: string, completed_steps: list<string>, fields: array<string, array<string, mixed>>, sources?: array<string, array<string, Source>>, documents?: list<array{id: string, revision: int, sha256: string, replaces: string|null}>}
 */
final class AuditProcedure
{
    /** The cause codes a monthly difference is explained by (BRS v3 VIS-R01 step 9), in their listed order. */
    public const array DIFFERENCE_CAUSES = ['unrecorded_sales', 'unproven_expense', 'personal_spending', 'wrong_month', 'entry_error', 'counted_twice'];

    /**
     * The six categories the CPA sorts the month's costs into (BRS v3 VIS-R01 step 5), the same six the
     * Business uses in its monthly submission (VIS-R04), in their listed order.
     */
    public const array COST_CATEGORIES = ['stock', 'staff', 'premises', 'transport', 'taxes', 'other'];

    /** At most this many other loans are recorded on one monthly visit (BRS v3 VIS-R01 step 8). */
    public const int MAX_OTHER_LOANS = 10;

    /** How the business used its Rozine loan money this month (BRS v3 VIS-R01 step 13). */
    public const array LOAN_USES = ['as_planned', 'partly', 'not_as_planned'];

    /**
     * The four yes/no changes the CPA asks about (BRS v3 VIS-R01 step 14): the report-detected material
     * changes of PRO-R05 other than the revenue and cash movements, which are computed from the figures.
     */
    public const array CHANGE_KINDS = ['new_debt', 'director_change', 'ownership_change', 'legal_claim'];

    /** @return list<string> */
    public function steps(string $kind): array
    {
        return match ($kind) {
            'flash' => ['review', 'check_in', 'photos', 'ledger', 'seal'],
            'monthly' => ['statements', 'count', 'cash_sales', 'costs', 'drawings', 'loans', 'differences', 'staff', 'expiries', 'loan_use', 'changes', 'photos', 'seal'],
            default => throw new CommandRejection('AUDIT_PROCEDURE_INVALID', 422),
        };
    }

    public function selectStep(string $kind, string $currentStep, ?string $requestedStep): string
    {
        $steps = $this->steps($kind);
        $step = $requestedStep ?? $currentStep;
        $current = array_search($currentStep, $steps, true);
        $requested = array_search($step, $steps, true);
        if ($current === false || $requested === false || $requested > $current) {
            throw new CommandRejection('AUDIT_STEP_NOT_AVAILABLE', 422, fieldErrors: ['step' => ['Open a completed step or the current procedure step.']]);
        }

        return $step;
    }

    /**
     * @param  Draft  $draft
     * @param  Sources  $sources
     */
    public function unavailable(string $kind, string $currentStep, array $draft, string $step, array $sources): ?string
    {
        try {
            $steps = $this->steps($kind);
            $index = array_search($step, $steps, true);
            $current = array_search($currentStep, $steps, true);
            if ($index === false || $current === false || $index > $current) {
                return 'AUDIT_STEP_NOT_AVAILABLE';
            }
            $preceding = array_slice($steps, 0, $index);
            if (array_slice($draft['completed_steps'], 0, $index) !== $preceding) {
                return 'AUDIT_PROCEDURE_INCOMPLETE';
            }
            foreach ($preceding as $previous) {
                if (($draft['sources'][$previous] ?? []) !== $this->pins($previous, $sources)) {
                    return 'AUDIT_PROCEDURE_SOURCE_CHANGED';
                }
            }
            $this->pins($step, $sources);
            if (($step === 'ledger' && $sources['reported_stock'] === null)
                || (in_array($step, ['count', 'differences'], true) && ($sources['reported_cash'] === null || $sources['reported_units'] === null))) {
                return 'AUDIT_DECLARATION_REQUIRED';
            }
            if ($step === 'count' && ($sources['financial_proofs'] === [] || $sources['inventory_proofs'] === [])) {
                return 'AUDIT_PROOFS_REQUIRED';
            }
        } catch (CommandRejection $failure) {
            return $failure->reason;
        }

        return null;
    }

    /**
     * @param  Draft  $draft
     * @param  array<string, mixed>  $input
     * @param  Sources  $sources
     * @return array{step: string, draft: Draft}
     */
    public function save(string $kind, string $currentStep, array $draft, string $step, array $input, array $sources): array
    {
        $steps = $this->steps($kind);
        $index = array_search($step, $steps, true);
        $currentIndex = array_search($currentStep, $steps, true);
        if ($index === false || $currentIndex === false || $index > $currentIndex) {
            throw new CommandRejection('AUDIT_STEP_NOT_AVAILABLE', 422, fieldErrors: ['step' => ['Complete the preceding procedure steps first.']]);
        }
        $preceding = array_slice($steps, 0, $index);
        if (array_slice($draft['completed_steps'], 0, $index) !== $preceding) {
            throw new CommandRejection('AUDIT_PROCEDURE_INCOMPLETE', 409);
        }
        foreach ($preceding as $previous) {
            $currentPins = $this->pins($previous, $sources);
            if (($draft['sources'][$previous] ?? []) !== $currentPins) {
                throw new CommandRejection('AUDIT_PROCEDURE_SOURCE_CHANGED', 409, fieldErrors: ['step' => ['Review the changed source before continuing.']], data: ['step' => $previous]);
            }
        }
        $fields = $this->fields($step, $input, $sources, $draft);
        $pins = $this->pins($step, $sources);
        $completed = $step === 'seal' ? $preceding : [...$preceding, $step];
        $retained = array_fill_keys($completed, true);
        $draft['fields'] = array_intersect_key($draft['fields'], $retained);
        $draft['sources'] = array_intersect_key($draft['sources'] ?? [], $retained);
        $draft['completed_steps'] = $completed;
        if ($kind === 'flash' && $index < array_search('ledger', $steps, true)) {
            unset($draft['documents']);
        }
        if ($step === 'seal') {
            $draft['note'] = (string) $fields['note'];
        } else {
            $draft['fields'][$step] = $fields;
            $draft['sources'][$step] = $pins;
            $draft['note'] = '';
        }

        return ['step' => $steps[min($index + 1, count($steps) - 1)], 'draft' => $draft];
    }

    /**
     * The cash difference the monthly count left to explain: what the CPA counted against what the
     * statements report, as a non-negative whole-franc amount; null before both are known.
     *
     * @param  Draft  $draft
     * @param  Sources  $sources
     */
    public function cashDifference(array $draft, array $sources): ?string
    {
        $observed = $draft['fields']['count']['cash'] ?? null;
        if (! is_string($observed) || $sources['reported_cash'] === null) {
            return null;
        }

        return (string) ExactFinancialValue::amount($observed)->minus(ExactFinancialValue::amount($sources['reported_cash']))->abs();
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  Sources  $sources
     * @param  Draft  $draft
     * @return array<string, mixed>
     */
    private function fields(string $step, array $input, array $sources, array $draft): array
    {
        $required = match ($step) {
            'ledger' => ['observed_stock', 'reconciled'],
            'count' => ['financial_proofs', 'inventory_proofs', 'cash', 'stock_units', 'operational_status'],
            'photos' => ['titles'],
            'cash_sales' => ['undeposited_cash_sales'],
            'costs' => ['costs'],
            'drawings' => ['owner_drawings'],
            'loans' => ['loans'],
            'differences' => ['explanations'],
            'staff' => ['staff_count', 'rssb_checked'],
            'expiries' => ['tax_clearance_expires_on', 'rssb_clearance_expires_on'],
            'loan_use' => ['loan_use', 'loan_use_note'],
            'changes' => ['changes'],
            'seal' => ['note'],
            default => [],
        };
        $keys = array_keys($input);
        sort($keys);
        sort($required);
        if ($keys !== $required) {
            throw new CommandRejection('AUDIT_STEP_INPUT_INVALID', 422, fieldErrors: ['step' => ['Send only the fields for this procedure step.']]);
        }
        if ($step === 'ledger') {
            $amount = $this->amount($input['observed_stock'], 'observed_stock');
            if ($input['reconciled'] !== true) {
                throw new CommandRejection('AUDIT_RECONCILIATION_REQUIRED', 422, fieldErrors: ['reconciled' => ['Record the factual reconciliation before continuing.']]);
            }
            if ($sources['reported_stock'] === null) {
                throw new CommandRejection('AUDIT_DECLARATION_REQUIRED', 409);
            }

            return ['observed_stock' => $amount, 'reconciled' => true];
        }
        if ($step === 'count') {
            $cash = $this->amount($input['cash'], 'cash');
            $units = $this->amount($input['stock_units'], 'stock_units');
            if (! in_array($input['operational_status'], ['active', 'suspended', 'restricted'], true)) {
                throw new CommandRejection('AUDIT_STEP_INPUT_INVALID', 422, fieldErrors: ['operational_status' => ['Record the observed operating status.']]);
            }
            if ($sources['reported_cash'] === null || $sources['reported_units'] === null) {
                throw new CommandRejection('AUDIT_DECLARATION_REQUIRED', 409);
            }

            return ['cash' => $cash, 'stock_units' => $units, 'operational_status' => $input['operational_status'],
                'financial_proofs' => $this->proofs($input['financial_proofs'], $sources['financial_proofs'], 'financial_proofs'),
                'inventory_proofs' => $this->proofs($input['inventory_proofs'], $sources['inventory_proofs'], 'inventory_proofs')];
        }
        if ($step === 'cash_sales') {
            return ['undeposited_cash_sales' => $this->amount($input['undeposited_cash_sales'], 'undeposited_cash_sales')];
        }
        if ($step === 'costs') {
            return ['costs' => $this->costs($input['costs'])];
        }
        if ($step === 'drawings') {
            return ['owner_drawings' => $this->amount($input['owner_drawings'], 'owner_drawings')];
        }
        if ($step === 'loans') {
            return ['loans' => $this->loans($input['loans'])];
        }
        if ($step === 'staff') {
            $staff = $this->amount($input['staff_count'], 'staff_count');
            if ($input['rssb_checked'] !== true) {
                throw new CommandRejection('AUDIT_STEP_INPUT_INVALID', 422, fieldErrors: ['rssb_checked' => ['Check the staff count against the RSSB total page before continuing.']]);
            }

            return ['staff_count' => $staff, 'rssb_checked' => true];
        }
        if ($step === 'expiries') {
            return ['tax_clearance_expires_on' => $this->date($input['tax_clearance_expires_on'], 'tax_clearance_expires_on'),
                'rssb_clearance_expires_on' => $this->date($input['rssb_clearance_expires_on'], 'rssb_clearance_expires_on')];
        }
        if ($step === 'loan_use') {
            if (! in_array($input['loan_use'], self::LOAN_USES, true)) {
                throw new CommandRejection('AUDIT_STEP_INPUT_INVALID', 422, fieldErrors: ['loan_use' => ['Say how the loan money was used.']]);
            }
            $note = trim($this->text($input['loan_use_note'], 'loan_use_note', 200, $input['loan_use'] !== 'as_planned'));

            return ['loan_use' => $input['loan_use'], 'loan_use_note' => $note];
        }
        if ($step === 'changes') {
            return ['changes' => $this->changes($input['changes'])];
        }
        if ($step === 'differences') {
            return ['explanations' => $this->explanations($input['explanations'], $this->cashDifference($draft, $sources)
                ?? throw new CommandRejection('AUDIT_DECLARATION_REQUIRED', 409))];
        }
        if ($step === 'photos') {
            if (! is_array($input['titles']) || array_diff(array_keys($input['titles']), $sources['extra_photos']) !== []) {
                throw new CommandRejection('AUDIT_STEP_INPUT_INVALID', 422, fieldErrors: ['titles' => ['Use the received extra-photo identifiers.']]);
            }
            $titles = [];
            foreach ($sources['extra_photos'] as $id) {
                $titles[$id] = $this->text($input['titles'][$id] ?? '', 'titles', 100, true);
            }
            ksort($titles);

            return ['titles' => $titles];
        }
        if ($step === 'seal') {
            return ['note' => $this->text($input['note'], 'note', 100, false)];
        }

        return [];
    }

    /**
     * @param  Sources  $sources
     * @return array<string, Source>
     */
    private function pins(string $step, array $sources): array
    {
        $required = match ($step) {
            'check_in' => ['check_in'],
            'photos' => ['photos'],
            'statements' => ['verification'],
            'ledger', 'count', 'cash_sales', 'costs', 'drawings', 'loans', 'differences', 'staff', 'expiries', 'loan_use', 'changes' => ['verification', 'declaration'],
            default => [],
        };
        $pins = [];
        foreach ($required as $key) {
            $pin = $sources[$key];
            if ($pin === null) {
                throw new CommandRejection(match ($key) {
                    'verification' => 'AUDIT_VERIFIED_STATEMENTS_REQUIRED',
                    'declaration' => 'AUDIT_DECLARATION_REQUIRED',
                    default => 'AUDIT_CAPTURE_REQUIRED',
                }, 409);
            }
            $pins[$key] = $pin;
        }

        return $step === 'ledger' ? [...$pins, ...$sources['ledger_sources'] ?? []] : $pins;
    }

    /**
     * Every franc of the difference is explained, once per cause, by positive whole amounts that
     * add up to it exactly (BRS v3 VIS-R01: "explained differences must equal the total difference
     * to the franc"). A month with no difference records none.
     *
     * @return list<array{cause: string, amount: string}>
     */
    private function explanations(mixed $values, string $difference): array
    {
        if (! is_array($values) || ! array_is_list($values) || count($values) > count(self::DIFFERENCE_CAUSES)) {
            throw new CommandRejection('AUDIT_STEP_INPUT_INVALID', 422, fieldErrors: ['explanations' => ['List each cause once with its amount.']]);
        }
        $explanations = [];
        $total = BigDecimal::zero();
        foreach ($values as $value) {
            $keys = is_array($value) ? array_keys($value) : [];
            sort($keys);
            $cause = is_array($value) ? ($value['cause'] ?? null) : null;
            $given = is_array($value) ? ($value['amount'] ?? null) : null;
            if ($keys !== ['amount', 'cause'] || ! is_string($cause) || ! in_array($cause, self::DIFFERENCE_CAUSES, true) || isset($explanations[$cause])) {
                throw new CommandRejection('AUDIT_STEP_INPUT_INVALID', 422, fieldErrors: ['explanations' => ['List each cause once with its amount.']]);
            }
            $amount = $this->amount($given, 'explanations');
            if (BigDecimal::of($amount)->isZero()) {
                throw new CommandRejection('AUDIT_STEP_INPUT_INVALID', 422, fieldErrors: ['explanations' => ['Give each cause an amount above zero.']]);
            }
            $explanations[$cause] = $amount;
            $total = $total->plus(BigDecimal::of($amount));
        }
        if (! $total->isEqualTo(BigDecimal::of($difference))) {
            throw new CommandRejection('AUDIT_DIFFERENCE_UNEXPLAINED', 422, fieldErrors: ['explanations' => ['Explain the whole difference of RWF '.$difference.', to the franc.']]);
        }
        $ordered = [];
        foreach (self::DIFFERENCE_CAUSES as $cause) {
            if (isset($explanations[$cause])) {
                $ordered[] = ['cause' => $cause, 'amount' => $explanations[$cause]];
            }
        }

        return $ordered;
    }

    /**
     * Each loan the business owes anyone other than its Rozine note, as the CPA saw it in the loan
     * agreement or statement: who lent it, what is still owed and the monthly payment, in whole
     * francs. A business with no other loans records an empty list.
     *
     * @return list<array{lender: string, outstanding: string, monthly_payment: string}>
     */
    private function loans(mixed $values): array
    {
        if (! is_array($values) || ! array_is_list($values) || count($values) > self::MAX_OTHER_LOANS) {
            throw new CommandRejection('AUDIT_STEP_INPUT_INVALID', 422, fieldErrors: ['loans' => ['List at most '.self::MAX_OTHER_LOANS.' other loans.']]);
        }
        $loans = [];
        foreach ($values as $value) {
            $keys = is_array($value) ? array_keys($value) : [];
            sort($keys);
            if ($keys !== ['lender', 'monthly_payment', 'outstanding']) {
                throw new CommandRejection('AUDIT_STEP_INPUT_INVALID', 422, fieldErrors: ['loans' => ['Give each loan its lender, the amount still owed and the monthly payment.']]);
            }
            /** @var array{lender: mixed, outstanding: mixed, monthly_payment: mixed} $value */
            $outstanding = $this->amount($value['outstanding'], 'loans');
            if (BigDecimal::of($outstanding)->isZero()) {
                throw new CommandRejection('AUDIT_STEP_INPUT_INVALID', 422, fieldErrors: ['loans' => ['Leave out a loan that is fully repaid.']]);
            }
            $loans[] = ['lender' => trim($this->text($value['lender'], 'loans', 100, true)), 'outstanding' => $outstanding,
                'monthly_payment' => $this->amount($value['monthly_payment'], 'loans')];
        }

        return $loans;
    }

    /**
     * A whole-franc amount for each of the six cost categories, in the listed order; a category with no
     * cost this month is recorded as zero, never left out.
     *
     * @return array<string, string>
     */
    private function costs(mixed $values): array
    {
        if (! is_array($values) || count($values) !== count(self::COST_CATEGORIES)) {
            throw new CommandRejection('AUDIT_STEP_INPUT_INVALID', 422, fieldErrors: ['costs' => ['Enter an amount for each of the six cost categories.']]);
        }
        $costs = [];
        foreach (self::COST_CATEGORIES as $category) {
            if (! array_key_exists($category, $values)) {
                throw new CommandRejection('AUDIT_STEP_INPUT_INVALID', 422, fieldErrors: ['costs' => ['Enter an amount for each of the six cost categories.']]);
            }
            $costs[$category] = $this->amount($values[$category], 'costs');
        }

        return $costs;
    }

    /**
     * A yes or no for each change the CPA asks about, in the listed order.
     *
     * @return array<string, bool>
     */
    private function changes(mixed $values): array
    {
        if (! is_array($values) || count($values) !== count(self::CHANGE_KINDS)) {
            throw new CommandRejection('AUDIT_STEP_INPUT_INVALID', 422, fieldErrors: ['changes' => ['Answer yes or no for each change.']]);
        }
        $answers = [];
        foreach (self::CHANGE_KINDS as $kind) {
            $answer = $values[$kind] ?? null;
            if (! is_bool($answer)) {
                throw new CommandRejection('AUDIT_STEP_INPUT_INVALID', 422, fieldErrors: ['changes' => ['Answer yes or no for each change.']]);
            }
            $answers[$kind] = $answer;
        }

        return $answers;
    }

    /** A calendar date as written on the certificate, `YYYY-MM-DD`; a date already past is recorded as seen. */
    private function date(mixed $value, string $field): string
    {
        if (! is_string($value) || preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $parts) !== 1 || ! checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1])) {
            throw new CommandRejection('AUDIT_STEP_INPUT_INVALID', 422, fieldErrors: [$field => ['Use the date on the certificate, as YYYY-MM-DD.']]);
        }

        return $value;
    }

    private function amount(mixed $value, string $field): string
    {
        try {
            if (! is_string($value)) {
                throw new UnderwritingViolation('INVALID_MONEY');
            }
            ExactFinancialValue::amount($value);
        } catch (UnderwritingViolation) {
            throw new CommandRejection('AUDIT_STEP_INPUT_INVALID', 422, fieldErrors: [$field => ['Use a non-negative whole-number amount.']]);
        }

        return $value;
    }

    /** @param list<string> $required
     * @return list<string>
     */
    private function proofs(mixed $values, array $required, string $field): array
    {
        if (! is_array($values) || ! array_is_list($values) || array_any($values, fn (mixed $item): bool => ! is_string($item))) {
            throw new CommandRejection('AUDIT_STEP_INPUT_INVALID', 422, fieldErrors: [$field => ['List the inspected proof identifiers.']]);
        }
        sort($values);
        sort($required);
        if ($values !== $required || $required === []) {
            throw new CommandRejection('AUDIT_PROOFS_REQUIRED', 422, fieldErrors: [$field => ['Inspect every required proof before continuing.']]);
        }

        return $values;
    }

    private function text(mixed $value, string $field, int $max, bool $required): string
    {
        if (! is_string($value) || ! mb_check_encoding($value, 'UTF-8') || mb_strlen($value) > $max
            || preg_match('/[\p{Cc}\p{Cf}]/u', $value) || ($required && trim($value) === '')) {
            throw new CommandRejection('AUDIT_STEP_INPUT_INVALID', 422, fieldErrors: [$field => ['Use plain text of at most '.$max.' characters.']]);
        }

        return $value;
    }
}
