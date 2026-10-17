<?php

declare(strict_types=1);

namespace App\Application\Primary;

/** Original issuance ownership and acknowledged terms; subsequent transfers are a separate source. */
final readonly class RetainedHoldingSchedule
{
    /**
     * @param  list<array{first: int, last: int}>  $ordinals
     * @param  array<string, mixed>  $rights
     * @param  array<string, mixed>  $terms
     * @param  list<RetainedNoteInstalment>  $instalments
     */
    public function __construct(public string $holdingId, public string $holdingSha256, public string $commitmentId,
        public string $reservationId, public string $partyId, public int $units, public array $ordinals,
        public string $reservationSha256, public string $confirmationVersionId, public int $confirmationRevision,
        public string $confirmationSha256, public array $rights, public array $terms, public string $principal,
        public string $contractualReturn, public string $gross, public array $instalments) {}
}
