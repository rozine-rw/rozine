<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Application\Identity\Contracts\RoleApplicationStore;
use App\Domain\Identity\InstitutionalInvestorTerms;

/**
 * A person's own application to switch from Investor to Business representative or Auditor: where it
 * stands, each step, each document, and sending it for review (Erastus, 23 Nov 2026).
 *
 * @phpstan-import-type Standing from RoleApplicationStore
 */
final class ApplyForRole
{
    public function __construct(private RoleApplicationStore $store) {}

    /**
     * What the institutional investor step offers (26 Nov 2026): the institution types and the
     * smallest monthly distribution an institution may commit to.
     *
     * @return array{institution_types: list<string>, min_monthly_commitment: int}
     */
    public static function institutionCatalog(): array
    {
        return ['institution_types' => InstitutionalInvestorTerms::TYPES, 'min_monthly_commitment' => InstitutionalInvestorTerms::MIN_MONTHLY_COMMITMENT];
    }

    /** @return Standing */
    public function standing(int $userId): array
    {
        return $this->store->get($userId);
    }

    /** @return array<string, array{id: string, filename: string, media_type: string}> */
    public function uploads(int $userId): array
    {
        return $this->store->uploads($userId);
    }

    /** @return array{filename: string, media_type: string, content: string} */
    public function document(int $userId, string $slot): array
    {
        return $this->store->ownDocument($userId, $slot);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function save(int $userId, int $contextRevision, int $expectedRevision, string $role, string $step, array $input, string $requestId): array
    {
        return $this->store->save($userId, $contextRevision, $expectedRevision, $role, $step, $input, $requestId);
    }

    /** @return array<string, mixed> */
    public function upload(int $userId, int $contextRevision, int $expectedRevision, string $slot, string $filename, string $content, string $requestId): array
    {
        return $this->store->upload($userId, $contextRevision, $expectedRevision, $slot, $filename, $content, $requestId);
    }

    /** @return array<string, mixed> */
    public function submit(int $userId, int $contextRevision, int $expectedRevision, bool $declared, string $requestId): array
    {
        return $this->store->submit($userId, $contextRevision, $expectedRevision, $declared, $requestId);
    }
}
