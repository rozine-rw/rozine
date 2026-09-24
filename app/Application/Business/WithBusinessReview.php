<?php

declare(strict_types=1);

namespace App\Application\Business;

use App\Application\Business\Contracts\BusinessAuthorityStore;
use Closure;

/** @phpstan-import-type Business from BusinessAuthorityStore */
final class WithBusinessReview
{
    public function __construct(private BusinessAuthorityStore $store) {}

    /**
     * Staff verification boundary; denial/invalidation may still run after a mandate lapses.
     *
     * @template TResult
     *
     * @param  Closure(Business): TResult  $operation
     * @return TResult
     */
    public function handle(int $actorId, string $businessId, bool $requireVerified, Closure $operation): mixed
    {
        return $this->store->withReview($actorId, $businessId, $requireVerified, $operation);
    }
}
