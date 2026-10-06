<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @phpstan-import-type Release from \App\Application\Auditor\Contracts\AuditEngagementStore
 * @phpstan-import-type Acceptance from \App\Domain\Auditor\AuditEngagementDocuments
 *
 * @phpstan-type Payload array{release: Release|null, acceptance: Acceptance|null, identity_context_revision: int}
 */
class AuditorEngagementResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var Payload $page */
        $page = $this->resource;
        $prefix = $request->routeIs('api.*') ? 'api.v1.auditor.engagement.' : 'auditor.engagement.';
        $mayAccept = $page['release'] !== null && $page['acceptance'] === null;
        $placeholder = '00000000-0000-0000-0000-000000000000';

        return ['contract_version' => 'auditor-engagement-v1', 'identity_context_revision' => $page['identity_context_revision'],
            'server_time' => now()->toIso8601String(), 'release' => $page['release'], 'acceptance' => $page['acceptance'],
            'allowed_actions' => $mayAccept ? ['audit.engagement.accept'] : [],
            'actions' => ['accept' => $mayAccept ? ['url' => route($prefix.'accept', [], false), 'method' => 'post'] : null],
            'links' => ['current' => ['url' => route($prefix.'show', [], false), 'method' => 'get'],
                'operation' => ['url' => str_replace($placeholder, '{request_id}', route($prefix.'operations.show', ['request_id' => $placeholder], false)), 'method' => 'get']]];
    }
}
