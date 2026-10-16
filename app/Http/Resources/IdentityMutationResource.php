<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IdentityMutationResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $result */
        $result = $this->resource;

        return [
            'contract_version' => $result['contract_version'], 'policy_version' => $result['policy_version'],
            'code' => $result['code'], 'user_id' => $result['user_id'] ?? null,
            'party_id' => $result['party_id'] ?? null, 'membership' => $result['membership'] ?? null,
        ];
    }
}
