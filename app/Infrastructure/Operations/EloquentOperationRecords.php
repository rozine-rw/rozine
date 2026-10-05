<?php

declare(strict_types=1);

namespace App\Infrastructure\Operations;

use App\Application\Operations\Contracts\OperationRecords;
use App\Models\CommandOperation;

final class EloquentOperationRecords implements OperationRecords
{
    public function forTarget(string $actorKey, string $command, string $targetType, string $targetId): array
    {
        return array_values(CommandOperation::query()->where('actor_key', $actorKey)->where('command', $command)->where('target_type', $targetType)
            ->where('target_id', $targetId)->orderBy('id')->get()
            ->map(fn (CommandOperation $operation): array => ['operation_id' => (string) $operation->id, 'request_id' => (string) $operation->request_id,
                'result' => (array) $operation->result, 'recorded_at' => $operation->created_at?->toIso8601String() ?? ''])->all());
    }

    public function attribution(string $operationId): ?array
    {
        $operation = CommandOperation::query()->whereKey($operationId)->first();

        return $operation === null ? null : ['operation_id' => (string) $operation->id, 'actor_key' => $operation->actor_key,
            'command' => $operation->command, 'recorded_at' => $operation->created_at?->toIso8601String() ?? ''];
    }
}
