<?php

declare(strict_types=1);

namespace App\Domain\Disbursement;

/**
 * The written reason every disbursement command carries (C3 v2 §2e). It is trimmed, non-empty, at
 * most 1,000 characters and free of control, format and line-separator characters, so the trail
 * shows exactly what the staff member wrote.
 */
final readonly class DisbursementReason
{
    public const int MAX = 1000;

    /** The recorded form of a reason, or null when it is not a valid reason. */
    public static function normalize(string $reason): ?string
    {
        $trimmed = trim($reason);
        if ($trimmed === '' || mb_strlen($trimmed) > self::MAX || ! mb_check_encoding($trimmed, 'UTF-8')
            || preg_match('/[\p{Cc}\p{Cf}\p{Zl}\p{Zp}]/u', $trimmed) !== 0) {
            return null;
        }

        return $trimmed;
    }
}
