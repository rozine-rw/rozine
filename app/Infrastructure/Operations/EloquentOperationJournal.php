<?php

declare(strict_types=1);

namespace App\Infrastructure\Operations;

use App\Application\Operations\Contracts\CanonicalJson;
use App\Application\Operations\Contracts\OperationJournal;
use App\Domain\Operations\CommandRejection;
use App\Domain\Operations\OperationResult;
use App\Models\CommandOperation;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class EloquentOperationJournal implements OperationJournal
{
    public function __construct(private CanonicalJson $json) {}

    /**
     * @param  array<string, mixed>  $permittedInput
     * @param  Closure(string, string): void  $authorize
     * @param  Closure(): OperationResult  $operation
     * @return array<string, mixed>
     */
    public function execute(string $actorKey, int $actorUserId, string $command, string $requestId, string $targetType, string $targetId, array $permittedInput, Closure $authorize, Closure $operation): array
    {
        $requestId = strtolower($requestId);
        $this->validateIdentity($actorKey, $command, $requestId);
        if ($actorUserId < 1 || $targetType === '' || strlen($targetType) > 80 || $targetId === '' || strlen($targetId) > 255) {
            throw new CommandRejection('OPERATION_INPUT_INVALID', 422);
        }
        $hash = hash('sha256', $this->json->encode(['target_type' => $targetType, 'target_id' => $targetId, 'input' => $permittedInput]));

        return DB::transaction(function () use ($actorKey, $actorUserId, $command, $requestId, $targetType, $targetId, $hash, $authorize, $operation): array {
            $authorize($targetType, $targetId);
            DB::select('SELECT pg_advisory_xact_lock(hashtextextended(?, 0))', [$actorKey.'|'.$command.'|'.$requestId]);
            $previous = $this->lookup($actorKey, $command, $requestId);
            if ($previous !== null) {
                if (! hash_equals($previous->request_hash, $hash)) {
                    throw new CommandRejection('IDEMPOTENCY_CONFLICT');
                }

                return $previous->result;
            }

            $operationId = strtolower((string) Str::ulid());
            try {
                $outcome = DB::transaction($operation);
                $result = [
                    'operation_id' => $operationId, 'status' => 'completed', 'code' => $outcome->code,
                    'data' => $outcome->data, 'revision' => $outcome->revision, 'policy_version' => $outcome->policyVersion,
                    'allowed_actions' => $outcome->allowedActions, 'field_errors' => [], 'http_status' => 200,
                ];
            } catch (CommandRejection $exception) {
                $result = [
                    'operation_id' => $operationId, 'status' => 'rejected', 'code' => $exception->reason,
                    'data' => [], 'revision' => $exception->revision, 'policy_version' => 'engineering-2026-09-23.4',
                    'allowed_actions' => [], 'field_errors' => $exception->fieldErrors, 'http_status' => $exception->status,
                ];
            }
            $result['server_time'] = now()->toIso8601String();
            $this->json->encode($result);
            $record = new CommandOperation;
            $record->forceFill([
                'id' => $operationId, 'actor_key' => $actorKey, 'actor_user_id' => $actorUserId, 'command' => $command,
                'request_id' => $requestId, 'target_type' => $targetType, 'target_id' => $targetId, 'request_hash' => $hash,
                'result' => $result, 'retain_until' => now()->addDays(8),
            ])->save();

            return $record->refresh()->result;
        }, 3);
    }

    /**
     * @param  Closure(string, string): void  $authorize
     * @return array<string, mixed>
     */
    public function find(string $actorKey, string $command, string $requestId, Closure $authorize): array
    {
        $requestId = strtolower($requestId);
        $this->validateIdentity($actorKey, $command, $requestId);

        return DB::transaction(function () use ($actorKey, $command, $requestId, $authorize): array {
            $record = $this->lookup($actorKey, $command, $requestId);
            if ($record === null) {
                throw new CommandRejection('OPERATION_NOT_FOUND', 404);
            }
            $authorize($record->target_type, $record->target_id);

            return $record->result;
        }, 3);
    }

    private function validateIdentity(string $actorKey, string $command, string $requestId): void
    {
        if (strlen($actorKey) > 80 || ! preg_match('/^(party:[0-9a-hjkmnp-tv-z]{26}|staff:[1-9][0-9]*)$/D', $actorKey)
            || ! preg_match('/^[a-z][a-z0-9]*(?:[._-][a-z0-9]+){1,7}$/D', $command)
            || strlen($command) > 100 || ! Str::isUuid($requestId)) {
            throw new CommandRejection('OPERATION_INPUT_INVALID', 422);
        }
    }

    private function lookup(string $actorKey, string $command, string $requestId): ?CommandOperation
    {
        return CommandOperation::query()->where('actor_key', $actorKey)->where('command', $command)->where('request_id', $requestId)->first();
    }
}
