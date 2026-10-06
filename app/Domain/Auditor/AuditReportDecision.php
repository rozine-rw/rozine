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
        $errors = [];
        if (! is_string($code) || ! in_array($code, $reject ? self::REJECTION : self::CHANGES, true)) {
            $errors['reason_code'] = ['Choose a factual report reason.'];
        }
        if (! is_string($explanation)) {
            $errors['reason'] = ['Supply a factual explanation.'];
        } elseif (! mb_check_encoding($explanation, 'UTF-8')) {
            $errors['reason'] = ['Use valid text without hidden or unsupported characters.'];
        } else {
            $explanation = str_replace(["\r\n", "\r"], "\n", $explanation);
            if (trim($explanation) === '') {
                $errors['reason'] = ['Supply a factual explanation.'];
            } elseif (mb_strlen($explanation) > 2000) {
                $errors['reason'] = ['Use at most 2,000 characters for the explanation.'];
            } elseif (preg_match('/[\p{Cc}\p{Cf}\x{2028}\x{2029}]/u', str_replace(["\n", "\t"], '', $explanation)) === 1) {
                $errors['reason'] = ['Use valid text without hidden or unsupported characters.'];
            }
        }
        if ($errors !== []) {
            throw new CommandRejection('AUDIT_REPORT_DECISION_INVALID', 422, fieldErrors: $errors);
        }

        return ['code' => (string) $code, 'explanation' => trim((string) $explanation)];
    }

    public static function amendable(string $status): bool
    {
        return in_array($status, ['changes_requested', 'rejected', 'sealed'], true);
    }
}
