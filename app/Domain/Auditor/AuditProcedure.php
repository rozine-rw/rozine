<?php

declare(strict_types=1);

namespace App\Domain\Auditor;

use App\Domain\Operations\CommandRejection;
use App\Domain\Underwriting\ExactFinancialValue;
use App\Domain\Underwriting\UnderwritingViolation;

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
    /** @return list<string> */
    public function steps(string $kind): array
    {
        return match ($kind) {
            'flash' => ['review', 'check_in', 'photos', 'ledger', 'seal'],
            'monthly' => ['statements', 'count', 'photos', 'seal'],
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
                || ($step === 'count' && ($sources['reported_cash'] === null || $sources['reported_units'] === null))) {
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
        $fields = $this->fields($step, $input, $sources);
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
     * @param  array<string, mixed>  $input
     * @param  Sources  $sources
     * @return array<string, mixed>
     */
    private function fields(string $step, array $input, array $sources): array
    {
        $required = match ($step) {
            'ledger' => ['observed_stock', 'reconciled'],
            'count' => ['financial_proofs', 'inventory_proofs', 'cash', 'stock_units', 'operational_status'],
            'photos' => ['titles'],
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
            'ledger', 'count' => ['verification', 'declaration'],
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
