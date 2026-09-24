<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OperationResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $data */
        $data = $this->resource;

        return [
            'operation_id' => $data['operation_id'], 'status' => $data['status'], 'code' => $data['code'],
            'data' => (object) $data['data'], 'revision' => $data['revision'], 'policy_version' => $data['policy_version'],
            'server_time' => $data['server_time'], 'allowed_actions' => $data['allowed_actions'],
            'field_errors' => (object) $data['field_errors'],
        ];
    }

    public function withResponse(Request $request, JsonResponse $response): void
    {
        /** @var array{http_status: int} $data */
        $data = $this->resource;
        $response->setStatusCode($data['http_status']);
    }
}
