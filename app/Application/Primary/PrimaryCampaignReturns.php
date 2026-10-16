<?php

declare(strict_types=1);

namespace App\Application\Primary;

use App\Application\Wallet\ReturnedCash;
use App\Domain\Primary\PrimaryReservation;

/** Complete retained cash returns under caller locks, not a closure or reusable inventory. */
final readonly class PrimaryCampaignReturns
{
    /**
     * @param  list<array{reservation_id: string, version_id: string, version_sha256: string, commitment_id: ?string, party_id: string, reservation: PrimaryReservation, cash: ReturnedCash}>  $returns
     */
    public function __construct(public string $campaignId, public string $publicationSha256, public string $releasedPrincipal,
        public string $refundedPrincipal, public int $refundedInvestors, public array $returns) {}
}
