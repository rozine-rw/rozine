<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StaffAccessResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $data */
        $data = $this->resource;

        return [
            'contract_version' => $data['contract_version'],
            'can_open_admin' => $data['can_open_admin'],
            'allowed_actions' => $data['allowed_actions'],
        ];
    }
}
