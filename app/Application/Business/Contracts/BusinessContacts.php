<?php

declare(strict_types=1);

namespace App\Application\Business\Contracts;

/**
 * A Business's contact details and registered address. Each save appends an immutable version under
 * the actor's current mandate permission `business.profile.update`, once per request id; the latest
 * version is the one the Profile shows.
 *
 * @phpstan-import-type Contact from \App\Domain\Business\BusinessContact
 */
interface BusinessContacts
{
    /**
     * The current contact, read inside the caller's Business authority; null until one is saved.
     *
     * @return Contact|null
     */
    public function current(string $businessId): ?array;

    /**
     * Records the contact a Business was approved with (its KYB registered address, the company
     * phone and the representative's e-mail) as its first version, so the Profile and audit dispatch
     * start from it. Does nothing once the Business has a contact, or when the details do not form a
     * valid contact. Runs inside the approval's transaction.
     *
     * @param  array<string, mixed>  $details
     */
    public function adopt(string $businessId, string $actorPartyId, array $details): void;

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function save(int $userId, int $contextRevision, string $businessId, string $requestId, array $input): array;
}
