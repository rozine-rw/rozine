<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OperationResource extends JsonResource
{
    /**
     * A command result as the API answers it. A refusal returned before the journal recorded it (an
     * idempotency conflict or an input refused up front) has no operation, data or revision.
     *
     * @param  array<string, mixed>  $result
     */
    public static function fromResult(array $result, string $policyVersion): self
    {
        return new self([...['operation_id' => null, 'data' => [], 'revision' => null, 'policy_version' => $policyVersion,
            'server_time' => now()->toIso8601String(), 'allowed_actions' => [], 'field_errors' => []], ...$result]);
    }

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $data */
        $data = $this->resource;

        return [
            'operation_id' => $data['operation_id'], 'status' => $data['status'], 'code' => $data['code'],
            'data' => $data['data'] === [] ? null : (object) $data['data'], 'revision' => $data['revision'], 'policy_version' => $data['policy_version'],
            'server_time' => now()->toIso8601String(), 'recorded_at' => $data['server_time'],
            'allowed_actions' => $data['allowed_actions'],
            'field_errors' => (object) $data['field_errors'], 'errors' => (object) $data['field_errors'],
        ];
    }

    public function withResponse(Request $request, JsonResponse $response): void
    {
        /** @var array{http_status: int} $data */
        $data = $this->resource;
        $response->setStatusCode($data['http_status']);
    }
}
