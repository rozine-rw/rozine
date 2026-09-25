<?php

declare(strict_types=1);

namespace App\Domain\Auditor;

use App\Domain\Operations\CommandRejection;

final class AuditReportDecision
{
    public const array CHANGES = ['missing_originals', 'reconciliation_difference', 'classification_unresolved', 'debt_evidence_missing', 'capture_unverified', 'other'];

    public const array REJECTION = ['evidence_unverifiable', 'procedure_incomplete', 'other'];

    /** @return array{code: string, explanation: string} */
    public static function reason(string $kind, string $status, bool $reject, mixed $code, mixed $explanation): array
    {
        if ($kind !== 'monthly' || $status !== 'draft') {
            throw new CommandRejection('AUDIT_REPORT_DECISION_NOT_ALLOWED');
        }
        if (! is_string($code) || ! in_array($code, $reject ? self::REJECTION : self::CHANGES, true)) {
            throw new CommandRejection('AUDIT_REPORT_DECISION_INVALID', 422, fieldErrors: ['reason_code' => ['Choose a factual report reason.']]);
        }
        if (! is_string($explanation) || ! mb_check_encoding($explanation, 'UTF-8') || trim($explanation) === ''
            || mb_strlen($explanation) > 2000 || preg_match('/[\p{Cc}\p{Cf}]/u', $explanation) === 1) {
            throw new CommandRejection('AUDIT_REPORT_DECISION_INVALID', 422, fieldErrors: ['reason' => ['Supply a factual explanation of at most 2,000 characters.']]);
        }

        return ['code' => $code, 'explanation' => trim($explanation)];
    }

    public static function amendable(string $status): bool
    {
        return in_array($status, ['changes_requested', 'rejected', 'sealed'], true);
    }
}
