<?php

declare(strict_types=1);

namespace App\Application\Operations\Contracts;

/**
 * Recorded command outcomes for a projection that has already authorized their target. Read
 * only: it never executes, replays or changes an operation, and returns each result unchanged.
 *
 * @phpstan-type OperationRecord array{operation_id: string, request_id: string, result: array<string, mixed>, recorded_at: string}
 */
interface OperationRecords
{
    /**
     * Every operation one actor recorded for one command on one target, oldest first.
     *
     * @return list<OperationRecord>
     */
    public function forTarget(string $actorKey, string $command, string $targetType, string $targetId): array;
}
