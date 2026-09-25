<?php

declare(strict_types=1);

namespace App\Domain\Evidence;

use App\Domain\Operations\CommandRejection;
use App\Domain\Underwriting\ExactFinancialValue;
use App\Domain\Underwriting\UnderwritingViolation;
use DateTimeImmutable;
use DateTimeZone;

/**
 * Factual source review supplies underwriting inputs, not a credit decision or final report seal.
 * Current reviewer authority and exact original/transcription integrity belong to the adapter.
 *
 * @phpstan-import-type Observation from StatementReconciliation
 *
 * @phpstan-type Obligation array{id: string, principal: string, service_by_month: array<string, string>, source_ids: list<string>}
 * @phpstan-type Review array{procedure_version: string, checks: array<string, bool>, source_checks: array<string, array{sha256: string, reference: string}>, inventory_reference: string, obligations: list<Obligation>, recurring_owner_draw: string, owner_draw_reference: string, findings: string}
 * @phpstan-type VerifiedObservation array{month: string, operating_inflow: string, operating_outflow: string, owner_draw: string, debt_service: string, financing_inflow: string, transfer_inflow: string, transfer_outflow: string, verified: true, source_ids: list<string>, rail_ids: list<string>, classification_version: string}
 */
final class StatementAuditReview
{
    public const PROCEDURE = 'MVP-AUP-1';

    public const POLICY_VERSION = 'engineering-2026-09-23.4';

    public const CHECKS = ['complete_rail_inventory', 'originals_authentic', 'balances_and_transactions',
        'duplicates_and_classification', 'debt_schedules', 'baseline_and_gaps', 'draws_returns_and_exceptions'];

    /**
     * @param  array<string, mixed>  $review
     * @param  array<string, string>  $sourceHashes
     * @param  list<Observation>  $observations
     * @return array{review: Review, observations: list<VerifiedObservation>}
     */
    public function verify(array $review, array $sourceHashes, array $observations, DateTimeImmutable $now): array
    {
        $this->keys($review, ['procedure_version', 'checks', 'source_checks', 'inventory_reference', 'obligations', 'recurring_owner_draw', 'owner_draw_reference', 'findings'], 'STATEMENT_REVIEW_PROCEDURE_REQUIRED');
        if ($review['procedure_version'] !== self::PROCEDURE) {
            throw new CommandRejection('STATEMENT_REVIEW_PROCEDURE_REQUIRED', 422);
        }
        if (! is_array($review['checks'])) {
            throw new CommandRejection('STATEMENT_REVIEW_INCOMPLETE', 422);
        }
        $checks = array_keys($review['checks']);
        sort($checks);
        $requiredChecks = self::CHECKS;
        sort($requiredChecks);
        if ($checks !== $requiredChecks || array_filter($review['checks'], fn (mixed $checked): bool => $checked !== true) !== []) {
            throw new CommandRejection('STATEMENT_REVIEW_INCOMPLETE', 422, fieldErrors: ['checks' => ['Complete every factual source-review check.']]);
        }
        $inventoryReference = $this->text($review['inventory_reference']);
        $ownerDrawReference = $this->text($review['owner_draw_reference']);
        $findings = $this->text($review['findings']);
        $ownerDraw = $this->money($review['recurring_owner_draw']);
        if (! is_array($review['source_checks']) || $sourceHashes === [] || count($sourceHashes) !== count($review['source_checks'])) {
            throw new CommandRejection('STATEMENT_SOURCE_REVIEW_REQUIRED', 422);
        }
        $sourceChecks = [];
        foreach ($sourceHashes as $id => $hash) {
            $check = $review['source_checks'][$id] ?? null;
            if (! is_array($check)) {
                throw new CommandRejection('STATEMENT_SOURCE_REVIEW_REQUIRED', 422);
            }
            $this->keys($check, ['sha256', 'reference'], 'STATEMENT_SOURCE_REVIEW_REQUIRED');
            if (! is_string($check['sha256']) || ! hash_equals($hash, $check['sha256'])) {
                throw new CommandRejection('STATEMENT_SOURCE_REVIEW_REQUIRED', 422);
            }
            $sourceChecks[$id] = ['sha256' => $hash, 'reference' => $this->text($check['reference'])];
        }
        if (! is_array($review['obligations']) || ! array_is_list($review['obligations'])) {
            throw new CommandRejection('STATEMENT_DEBT_EVIDENCE_REQUIRED', 422);
        }
        $ids = [];
        $obligations = [];
        foreach ($review['obligations'] as $obligation) {
            if (! is_array($obligation)) {
                throw new CommandRejection('STATEMENT_DEBT_EVIDENCE_REQUIRED', 422);
            }
            $this->keys($obligation, ['id', 'principal', 'source_ids', 'service_by_month'], 'STATEMENT_DEBT_EVIDENCE_REQUIRED');
            $id = $this->text($obligation['id']);
            if (in_array($id, $ids, true) || ! is_array($obligation['source_ids']) || ! array_is_list($obligation['source_ids'])
                || $obligation['source_ids'] === [] || ! is_array($obligation['service_by_month']) || $obligation['service_by_month'] === []) {
                throw new CommandRejection('STATEMENT_DEBT_EVIDENCE_REQUIRED', 422);
            }
            $ids[] = $id;
            $sourceIds = [];
            foreach ($obligation['source_ids'] as $sourceId) {
                if (! is_string($sourceId) || ! array_key_exists($sourceId, $sourceHashes) || in_array($sourceId, $sourceIds, true)) {
                    throw new CommandRejection('STATEMENT_DEBT_EVIDENCE_REQUIRED', 422);
                }
                $sourceIds[] = $sourceId;
            }
            $principal = $this->money($obligation['principal']);
            $schedule = [];
            foreach ($obligation['service_by_month'] as $month => $service) {
                $schedule[$this->month($month)] = $this->money($service);
            }
            $obligations[] = ['id' => $id, 'principal' => $principal, 'source_ids' => $sourceIds, 'service_by_month' => $schedule];
        }
        if ($observations === []) {
            throw new CommandRejection('STATEMENT_RECONCILIATION_REQUIRED', 422);
        }
        $currentMonth = $now->setTimezone(new DateTimeZone('Africa/Kigali'))->format('Y-m');
        $verified = [];
        foreach ($observations as $observation) {
            $this->month($observation['month']);
            if ($observation['month'] >= $currentMonth) {
                throw new CommandRejection('STATEMENT_COMPLETE_MONTH_REQUIRED', 422);
            }
            $verified[] = [...$observation, 'verified' => true];
        }

        return ['review' => ['procedure_version' => self::PROCEDURE, 'checks' => array_fill_keys(self::CHECKS, true),
            'source_checks' => $sourceChecks, 'inventory_reference' => $inventoryReference, 'obligations' => $obligations,
            'recurring_owner_draw' => $ownerDraw, 'owner_draw_reference' => $ownerDrawReference, 'findings' => $findings],
            'observations' => $verified];
    }

    /**
     * @param  array<array-key, mixed>  $input
     * @param  list<string>  $required
     */
    private function keys(array $input, array $required, string $code): void
    {
        $keys = array_keys($input);
        sort($keys);
        sort($required);
        if ($keys !== $required) {
            throw new CommandRejection($code, 422);
        }
    }

    private function text(mixed $text): string
    {
        if (! is_string($text) || trim($text) === '' || ! mb_check_encoding($text, 'UTF-8') || mb_strlen($text) > 2000 || preg_match('/[\p{Cc}\p{Cf}]/u', $text)) {
            throw new CommandRejection('STATEMENT_REVIEW_REFERENCE_REQUIRED', 422);
        }

        return $text;
    }

    private function month(mixed $month): string
    {
        if (! is_string($month) || ! preg_match('/^[1-9][0-9]{3}-(0[1-9]|1[0-2])$/D', $month)) {
            throw new CommandRejection('STATEMENT_REVIEW_MONTH_INVALID', 422);
        }

        return $month;
    }

    private function money(mixed $amount): string
    {
        if (! is_string($amount)) {
            throw new CommandRejection('STATEMENT_REVIEW_AMOUNT_INVALID', 422);
        }
        try {
            ExactFinancialValue::amount($amount);
        } catch (UnderwritingViolation) {
            throw new CommandRejection('STATEMENT_REVIEW_AMOUNT_INVALID', 422);
        }

        return $amount;
    }
}
