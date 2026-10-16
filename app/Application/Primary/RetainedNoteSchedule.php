<?php

declare(strict_types=1);

namespace App\Application\Primary;

use App\Application\Disbursement\ClosingEvidence;

/** Internal historical snapshot, never current eligibility, a payment quote or a public Resource. */
final readonly class RetainedNoteSchedule
{
    /**
     * @param  list<RetainedHoldingSchedule>  $holdings
     * @param  list<RetainedNoteInstalment>  $instalments
     */
    public function __construct(public string $campaignId, public string $businessId, public string $fundingId,
        public ClosingEvidence $closing, public string $issuedAt, public string $principal, public string $contractualReturn,
        public string $gross, public array $holdings, public array $instalments) {}
}
