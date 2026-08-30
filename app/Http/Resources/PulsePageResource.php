<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PulsePageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $page */
        $page = $this->resource;

        return [
            'traction' => $page['traction'],
            'districts' => $page['districts'],
            'policy' => (new PulsePolicyResource($page['policy']))->resolve($request),
            'investor_preview' => (new PulseInvestorPreviewResource($page['investor_preview']))->resolve($request),
        ];
    }
}
