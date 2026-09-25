<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @phpstan-import-type OperationsCase from \App\Application\Auditor\Contracts\AuditAssignmentStore */
class AuditOperationsResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var OperationsCase $case */
        $case = $this->resource;
        $prefix = $request->routeIs('api.*') ? 'api.v1.' : '';
        $actions = [];
        foreach (['redispatch', 'close'] as $decision) {
            $actions[$decision] = ['url' => route($prefix.'staff.audit.'.$decision, ['assignment' => $case['id']], false), 'method' => 'post'];
        }
        $placeholder = '00000000-0000-0000-0000-000000000000';
        $operation = route($prefix.'staff.audit.operations.show', ['request_id' => $placeholder], false);

        return ['contract_version' => 'audit-operations-v1', 'server_time' => now()->toIso8601String(), 'case' => $case, 'allowed_actions' => $case['allowed_actions'],
            'actions' => $actions, 'links' => ['operation' => ['url' => str_replace($placeholder, '{request_id}', $operation), 'method' => 'get']]];
    }
}
