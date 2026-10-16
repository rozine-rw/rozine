<?php

declare(strict_types=1);

namespace App\Application\Disbursement;

/**
 * What a reconciled success issues: the closing that identifies it, the disbursement operation, the
 * authenticated effective instant and its Kigali date, and the due dates counted from that anchor.
 * The funding source issues each commitment's Holding with its original ordinals, rights and terms.
 */
final readonly class IssueInstruction
{
    /** @param list<string> $dueDates */
    public function __construct(
        public string $closingId,
        public string $disbursementId,
        public string $intentOperationId,
        public string $effectiveAt,
        public string $effectiveDate,
        public array $dueDates,
        public string $issuedAt,
    ) {}
}
