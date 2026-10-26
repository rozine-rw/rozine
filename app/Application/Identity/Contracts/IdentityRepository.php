<?php

declare(strict_types=1);

namespace App\Application\Identity\Contracts;

/**
 * @phpstan-import-type AccessSnapshot from \App\Domain\Identity\ActiveRolePolicy
 */
interface IdentityRepository
{
    public function accountName(int $userId): string;

    /** Creates the person's Party and account, recording their acknowledgement of the current sandbox notice. */
    public function register(string $name, string $email, string $password): int;

    /** @return AccessSnapshot */
    public function forUser(int $userId): array;

    /** Current Party facts only; does not authorize an actor or grant access to a record. */
    public function auditorPartyIsActive(string $partyId): bool;

    /**
     * Each Party's kind, keyed by id, and nothing that names it.
     *
     * @param  list<string>  $partyIds
     * @return array<string, 'person'|'organization'>
     */
    public function partyKinds(array $partyIds): array;
}
