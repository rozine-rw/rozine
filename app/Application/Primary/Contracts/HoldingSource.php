<?php

declare(strict_types=1);

namespace App\Application\Primary\Contracts;

/**
 * The retained Primary facts a Holding must copy, and the application half of the Holding source
 * binding. PostgreSQL compares a Holding's plaintext facts and digest pins; it cannot read the
 * encrypted original rights and terms. This port decrypts them. It is read-only: it takes no
 * locks, writes nothing and issues nothing.
 *
 * @phpstan-type HoldingFacts array{business_campaign_id: string, commitment_id: string, primary_reservation_id: string, party_id: string, units: int, principal: string, ordinals: list<array{first: int, last: int}>, rights: mixed, terms: mixed, reservation_sha256: string, confirmation_version_id: string, confirmation_revision: int, confirmation_sha256: string}
 */
interface HoldingSource
{
    /**
     * The verified facts of one commitment in a durable funding record. Refuses a commitment that
     * is unknown or not funded, and any retained evidence that fails its digests.
     *
     * @return HoldingFacts
     */
    public function facts(string $commitmentId): array;

    /** Refuses unless the persisted Holding equals `facts` of its commitment, rights and terms included. */
    public function verify(string $holdingId): void;
}
