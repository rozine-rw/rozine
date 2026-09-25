<?php

declare(strict_types=1);

namespace App\Application\Evidence;

use App\Application\Evidence\Contracts\StatementStore;
use Closure;

/**
 * @phpstan-import-type Verification from StatementStore
 * @phpstan-import-type Business from \App\Application\Business\Contracts\BusinessAuthorityStore
 * @phpstan-import-type AccessSnapshot from \App\Domain\Identity\ActiveRolePolicy
 */
final class WithBusinessStatementVerification
{
    public function __construct(private StatementStore $store) {}

    /**
     * Holds the Business and source Auditor authority while the caller persists an evaluation.
     * Missing or invalidated evidence remains explicit so the caller can record a refused quote.
     *
     * @template TResult
     *
     * @param  Closure(Business, AccessSnapshot, Verification|null): TResult  $operation
     * @return TResult
     */
    public function handle(int $userId, int $contextRevision, string $businessId, string $permission, ?int $mandateVersion, Closure $operation): mixed
    {
        return $this->store->withBusinessVerification($userId, $contextRevision, $businessId, $permission, $mandateVersion, $operation);
    }
}
