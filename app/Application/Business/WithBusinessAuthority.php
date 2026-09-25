<?php

declare(strict_types=1);

namespace App\Application\Business;

use App\Application\Business\Contracts\BusinessAuthorityStore;
use Closure;

/**
 * @phpstan-import-type Business from BusinessAuthorityStore
 * @phpstan-import-type AccessSnapshot from \App\Domain\Identity\ActiveRolePolicy
 */
final class WithBusinessAuthority
{
    public function __construct(private BusinessAuthorityStore $store) {}

    /**
     * @template TResult
     *
     * @param  Closure(Business, AccessSnapshot): TResult  $operation
     * @param  list<string>  $additionalPartyIds
     * @return TResult
     */
    public function handle(int $userId, int $contextRevision, string $businessId, string $permission, ?int $mandateVersion, Closure $operation, array $additionalPartyIds = []): mixed
    {
        return $this->store->withAuthority($userId, $contextRevision, $businessId, $permission, $mandateVersion, $operation, $additionalPartyIds);
    }
}
