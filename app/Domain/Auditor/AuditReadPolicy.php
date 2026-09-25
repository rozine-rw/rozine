<?php

declare(strict_types=1);

namespace App\Domain\Auditor;

/** Shared bounds and recoverable per-record refusals for current Auditor projections. */
final class AuditReadPolicy
{
    public const int MAX_PAGE_SIZE = 50;

    public const array DROPPED_RECORD_CODES = ['BUSINESS_NOT_FOUND', 'MANDATE_REQUIRED', 'ASSIGNMENT_NOT_FOUND', 'ASSIGNMENT_ACCEPTANCE_EXPIRED',
        'AUDITOR_INDEPENDENCE_REVIEW_REQUIRED', 'ACCREDITATION_REQUIRED', 'ACCREDITATION_EXPIRED', 'ACCREDITATION_SUSPENDED', 'STANDING_CHECK_REQUIRED'];
}
