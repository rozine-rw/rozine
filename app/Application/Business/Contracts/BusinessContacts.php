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
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function save(int $userId, int $contextRevision, string $businessId, string $requestId, array $input): array;
}
