<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PulsePolicyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array{terms: list<int>, sectors: list<string>, registration_years: list<int>, minimum_revenue: int, minimum_loan: int, maximum_loan: int, pledge: array{minimum: int, maximum: int, step: int, default: int}} $policy */
        $policy = $this->resource;

        return [
            'terms' => $policy['terms'],
            'sectors' => $policy['sectors'],
            'registration_years' => $policy['registration_years'],
            'minimum_revenue' => $policy['minimum_revenue'],
            'minimum_loan' => $policy['minimum_loan'],
            'maximum_loan' => $policy['maximum_loan'],
            'pledge' => $policy['pledge'],
        ];
    }
}
