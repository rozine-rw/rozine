<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PulseInvestorPreviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array{pledge_amount: int, projected_return: int, blended_yield: float, listings: list<array<string, mixed>>} $preview */
        $preview = $this->resource;

        return [
            'pledge_amount' => $preview['pledge_amount'],
            'projected_return' => $preview['projected_return'],
            'blended_yield' => $preview['blended_yield'],
            'listings' => PulseListingResource::collection($preview['listings'])->resolve($request),
        ];
    }
}
