<?php

declare(strict_types=1);

namespace App\Application\Primary;

use App\Application\Wallet\CommittedCash;
use App\Domain\Primary\PrimaryReservation;

/**
 * Complete retained purchases and their original committed cash, under caller locks.
 * Not a funded campaign: current eligibility, policy, destination and durable funding remain separate gates.
 */
final readonly class PrimaryFundingCandidate
{
    /**
     * @param  list<array{commitment_id: string, reservation_id: string, party_id: string, reservation: PrimaryReservation, cash: CommittedCash}>  $purchases
     */
    public function __construct(public string $campaignId, public string $publicationSha256, public string $principal, public array $purchases) {}
}
