<?php

declare(strict_types=1);

namespace App\Application\Auditor\Contracts;

use Closure;

/**
 * @phpstan-import-type Business from \App\Application\Business\Contracts\BusinessAuthorityStore
 * @phpstan-import-type Verification from \App\Application\Evidence\Contracts\StatementStore
 * @phpstan-import-type AuditBinding from \App\Application\Business\Contracts\BusinessApplicationStore
 */
interface PublishedApplicationReport
{
    /**
     * Internal gate; the caller retains current Business authority and source locks.
     *
     * @template TResult
     *
     * @param  Business  $business
     * @param  Verification|null  $verification
     * @param  AuditBinding  $binding
     * @param  Closure(array{id: string, revision: int, digest: string}): TResult  $operation
     * @return TResult
     */
    public function withPublishedApplication(array $business, ?array $verification, array $binding, Closure $operation): mixed;
}
