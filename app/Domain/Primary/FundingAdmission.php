<?php

declare(strict_types=1);

namespace App\Domain\Primary;

/** Validates a required server admission result; it never manufactures source evidence. */
final class FundingAdmission
{
    /** @param array<string, mixed> $admission */
    public static function rejectionReason(array $admission, string $campaignId, string $publicationSha256): ?string
    {
        if (($admission['campaign_id'] ?? null) !== $campaignId || ($admission['publication_sha256'] ?? null) !== $publicationSha256) {
            return 'FUNDING_ADMISSION_MISMATCH';
        }
        foreach (['eligibility', 'policy', 'connections', 'destination'] as $name) {
            $check = $admission[$name] ?? null;
            if (! is_array($check) || ! is_array($check['evidence'] ?? null) || ! self::hasSourceFact($check['evidence'])) {
                return 'POLICY_INPUT_REQUIRED';
            }
            if (($check['status'] ?? null) === 'failed') {
                return 'FUNDING_PRECHECK_FAILED';
            }
            if (($check['status'] ?? null) !== 'passed') {
                return 'POLICY_INPUT_REQUIRED';
            }
        }

        return null;
    }

    /** @param array<string|int, mixed> $evidence */
    private static function hasSourceFact(array $evidence): bool
    {
        foreach ($evidence as $fact) {
            if (is_array($fact)) {
                if (self::hasSourceFact($fact)) {
                    return true;
                }
            } elseif (is_scalar($fact) && (! is_string($fact) || trim($fact) !== '')) {
                return true;
            }
        }

        return false;
    }
}
