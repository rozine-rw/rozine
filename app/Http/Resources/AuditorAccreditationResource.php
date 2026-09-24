<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditorAccreditationResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $data */
        $data = $this->resource;

        return ['contract_version' => $data['contract_version'], 'identity_context_revision' => $data['identity_context_revision'],
            'server_time' => $data['server_time'], 'standing' => $data['standing'], 'accreditation' => $data['accreditation'],
            'availability' => $data['availability'], 'allowed_actions' => $data['allowed_actions']];
    }
}
