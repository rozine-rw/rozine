<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IdentityContextResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $identity */
        $identity = $this->resource;

        return [
            'contract_version' => $identity['contract_version'],
            'policy_version' => $identity['policy_version'],
            'code' => $identity['code'],
            'party' => $identity['party'],
            'available_roles' => $identity['available_roles'],
            'active_role' => $identity['active_role'],
            'context_revision' => $identity['context_revision'],
            'allowed_actions' => $identity['allowed_actions'],
        ];
    }
}
