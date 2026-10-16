<?php

declare(strict_types=1);

namespace App\Application\Business;

use App\Application\Business\Contracts\BusinessAuthorityStore;

/**
 * @phpstan-import-type Profile from \App\Domain\Business\MandateAuthority
 * @phpstan-import-type Terms from \App\Domain\Business\MandateAuthority
 */
final class ConfigureBusinessAuthority
{
    public function __construct(private BusinessAuthorityStore $store) {}

    /**
     * @param  Profile  $profile
     * @param  Terms  $terms
     * @return array<string, mixed>
     */
    public function handle(int $actorId, string $entityKind, string $entityPartyId, array $profile, array $terms, int $expectedRevision, string $evidenceReference, string $reason, string $requestId): array
    {
        return $this->store->configure($actorId, $entityKind, $entityPartyId, $profile, $terms, $expectedRevision, $evidenceReference, $reason, $requestId);
    }
}
