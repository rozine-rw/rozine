<?php

declare(strict_types=1);

namespace App\Application\Operations\Contracts;

use App\Domain\Operations\OperationResult;
use Closure;

/**
 * Callers resolve actor keys on the server. Authorization must lock current authority and
 * scope the target inside this transaction; it runs before every execution or replay.
 */
interface OperationJournal
{
    /**
     * @param  array<string, mixed>  $permittedInput
     * @param  Closure(string, string): void  $authorize
     * @param  Closure(): OperationResult  $operation
     * @return array<string, mixed>
     */
    public function execute(
        string $actorKey,
        int $actorUserId,
        string $command,
        string $requestId,
        string $targetType,
        string $targetId,
        array $permittedInput,
        Closure $authorize,
        Closure $operation,
    ): array;

    /**
     * @param  Closure(string, string): void  $authorize
     * @return array<string, mixed>
     */
    public function find(string $actorKey, string $command, string $requestId, Closure $authorize): array;
}
