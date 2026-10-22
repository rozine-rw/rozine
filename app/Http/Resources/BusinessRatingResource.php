<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `BusinessRatingProps`: the Rating sheet drawn over the Business's real Home. The rating is the
 * latest published one Home shows, or null before the first. With it come the Business's own score
 * in four factors and how its capacity was sized, from the quote that rating was underwritten on;
 * the engine records no drift or refusal for it, so those stay null and are not drawn. Its verified
 * financials are the statement figures its rating was underwritten on.
 */
class BusinessRatingResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $home = (new BusinessHomeResource($this->resource))->toArray($request);
        /** @var array{raises: array{financials?: array<string, mixed>|null, factors?: list<array<string, mixed>>|null, sizing?: array<string, mixed>|null}} $data */
        $data = $this->resource;

        return ['home' => $home, 'rating' => $home['rating'], 'refusal' => null, 'drift' => null, 'factors' => $data['raises']['factors'] ?? null,
            'sizing' => $data['raises']['sizing'] ?? null, 'financials' => $data['raises']['financials'] ?? null,
            'links' => ['close' => $home['links']['home'], 'raise' => null]];
    }
}
