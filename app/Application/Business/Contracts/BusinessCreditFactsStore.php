<?php

declare(strict_types=1);

namespace App\Application\Business\Contracts;

use Closure;

/**
 * @phpstan-import-type Facts from \App\Domain\Underwriting\BorrowerCreditFacts
 *
 * @phpstan-type Snapshot array{id: string, business_id: string, revision: int, sha256: string, source_kind: 'isolated_alpha', source_reference: string, facts: Facts}
 */
interface BusinessCreditFactsStore
{
    /**
     * Trusted isolated-fixture publisher, never an application-request or production override.
     *
     * @param  array<string, mixed>|null  $facts
     * @return array<string, mixed>
     */
    public function recordFixture(int $actorId, string $businessId, int $expectedRevision, ?array $facts, string $sourceReference, string $reason, string $requestId): array;

    /**
     * Internal source port. The calling Business action authorizes the actor before using these
     * private facts. The aggregate lock keeps the source version stable through its effect.
     *
     * @template TResult
     *
     * @param  Closure(Snapshot|null): TResult  $operation
     * @return TResult
     */
    public function withCurrent(string $businessId, Closure $operation): mixed;
}
