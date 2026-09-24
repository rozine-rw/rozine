<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleBookmarkResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $data */
        $data = $this->resource;

        return [
            'contract_version' => $data['contract_version'],
            'role' => $data['role'],
            'context_revision' => $data['context_revision'],
            'route' => $data['route'],
            'parameters' => (object) $data['parameters'],
            'query' => (object) $data['query'],
            'url' => route($data['route'], array_merge($data['parameters'], $data['query']), false),
        ];
    }
}
