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

    public const CHECKS = ['complete_rail_inventory', 'originals_authentic', 'balances_and_transactions',
        'duplicates_and_classification', 'debt_schedules', 'baseline_and_gaps', 'draws_returns_and_exceptions'];

    /**
     * @param  Review  $review
     * @param  array<string, string>  $sourceHashes
     * @param  list<Observation>  $observations
     * @return list<VerifiedObservation>
     */
    public function verify(array $review, array $sourceHashes, array $observations, DateTimeImmutable $now): array
    {
        $this->keys($review, ['procedure_version', 'checks', 'source_checks', 'inventory_reference', 'obligations', 'recurring_owner_draw', 'owner_draw_reference', 'findings'], 'STATEMENT_REVIEW_PROCEDURE_REQUIRED');
        if ($review['procedure_version'] !== self::PROCEDURE) {
            throw new CommandRejection('STATEMENT_REVIEW_PROCEDURE_REQUIRED', 422);
        }
        $checks = array_keys($review['checks']);
        sort($checks);
        $requiredChecks = self::CHECKS;
        sort($requiredChecks);
        if ($checks !== $requiredChecks || array_filter($review['checks'], fn (mixed $checked): bool => $checked !== true) !== []) {
            throw new CommandRejection('STATEMENT_REVIEW_INCOMPLETE', 422, fieldErrors: ['checks' => ['Complete every factual source-review check.']]);
        }
        $this->text($review['inventory_reference']);
        $this->text($review['owner_draw_reference']);
        $this->text($review['findings']);
        $this->money($review['recurring_owner_draw']);
        if ($sourceHashes === [] || count($sourceHashes) !== count($review['source_checks'])) {
            throw new CommandRejection('STATEMENT_SOURCE_REVIEW_REQUIRED', 422);
        }
        foreach ($sourceHashes as $id => $hash) {
            $check = $review['source_checks'][$id] ?? null;
            if ($check === null || ! hash_equals($hash, $check['sha256'])) {
                throw new CommandRejection('STATEMENT_SOURCE_REVIEW_REQUIRED', 422);
            }
            $this->keys($check, ['sha256', 'reference'], 'STATEMENT_SOURCE_REVIEW_REQUIRED');
            $this->text($check['reference']);
        }
        $ids = [];
        foreach ($review['obligations'] as $obligation) {
            $this->keys($obligation, ['id', 'principal', 'source_ids', 'service_by_month'], 'STATEMENT_DEBT_EVIDENCE_REQUIRED');
            $this->text($obligation['id']);
            if (in_array($obligation['id'], $ids, true) || $obligation['source_ids'] === [] || $obligation['service_by_month'] === []) {
                throw new CommandRejection('STATEMENT_DEBT_EVIDENCE_REQUIRED', 422);
            }
            $ids[] = $obligation['id'];
            foreach ($obligation['source_ids'] as $sourceId) {
                if (! array_key_exists($sourceId, $sourceHashes)) {
                    throw new CommandRejection('STATEMENT_DEBT_EVIDENCE_REQUIRED', 422);
                }
            }
            $this->money($obligation['principal']);
            foreach ($obligation['service_by_month'] as $month => $service) {
                $this->month($month);
                $this->money($service);
            }
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

        return $verified;
    }

    /**
     * @param  array<string, mixed>  $input
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

    private function text(string $text): void
    {
        if (trim($text) === '' || ! mb_check_encoding($text, 'UTF-8') || mb_strlen($text) > 2000 || preg_match('/[\p{Cc}\p{Cf}]/u', $text)) {
            throw new CommandRejection('STATEMENT_REVIEW_REFERENCE_REQUIRED', 422);
        }
    }

    private function month(string $month): void
    {
        if (! preg_match('/^[1-9][0-9]{3}-(0[1-9]|1[0-2])$/D', $month)) {
            throw new CommandRejection('STATEMENT_REVIEW_MONTH_INVALID', 422);
        }
    }

    private function money(string $amount): void
    {
        try {
            ExactFinancialValue::amount($amount);
        } catch (UnderwritingViolation) {
            throw new CommandRejection('STATEMENT_REVIEW_AMOUNT_INVALID', 422);
        }
    }
}
