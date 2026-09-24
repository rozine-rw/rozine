<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Application\Identity\Contracts\IdentityAccessStore;
use Closure;

/** Verification boundary only: the calling Business action must also authorize the actor and mandate. */
final class WithVerifiedParties
{
    public function __construct(private IdentityAccessStore $access) {}

    /**
     * @template TResult
     *
     * @param  list<string>  $personPartyIds
     * @param  Closure(): TResult  $operation
     * @return TResult
     */
    public function handle(string $entityKind, string $entityPartyId, array $personPartyIds, Closure $operation, ?string $registryReference = null): mixed
    {
        return $this->access->withVerifiedParties($entityKind, $entityPartyId, $personPartyIds, $operation, $registryReference);
    }
}
