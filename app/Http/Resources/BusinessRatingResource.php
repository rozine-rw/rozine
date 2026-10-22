<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `BusinessRatingProps`: the Rating sheet drawn over the Business's real Home. The rating is the
 * latest published one Home shows, or null before the first. The engine publishes no factor
 * scores, capacity sizing, drift or refusal to the Business, so each is null and its section is not
 * drawn. Its verified financials are the statement figures its rating was underwritten on.
 */
class BusinessRatingResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $home = (new BusinessHomeResource($this->resource))->toArray($request);
        /** @var array{raises: array{financials?: array<string, mixed>|null}} $data */
        $data = $this->resource;

        return ['home' => $home, 'rating' => $home['rating'], 'refusal' => null, 'drift' => null, 'factors' => null, 'sizing' => null, 'financials' => $data['raises']['financials'] ?? null,
            'links' => ['close' => $home['links']['home'], 'raise' => null]];
    }
}
