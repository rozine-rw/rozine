<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class PulseListingResource extends JsonResource
{
    /**
     * Transform a pre-qualified business into the row investors see.
     *
     * A business is only named where it asked to be; otherwise it is shown by
     * the district it trades in.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array{id: int, name: string, district: string, listed: bool, term_months: int, flat_rate: float, rating_band: string, rating_score: float, projected_return: int} $listing */
        $listing = $this->resource;

        $label = $listing['listed'] ? $listing['name'] : "Business in {$listing['district']}";

        return [
            'id' => $listing['id'],
            'initial' => Str::upper(Str::substr($listing['listed'] ? $listing['name'] : $listing['district'], 0, 1)),
            'name' => $label,
            'district' => $listing['district'],
            'term' => "{$listing['term_months']}mo",
            'yield' => number_format($listing['flat_rate'], 1).'%',
            'yield_rate' => $listing['flat_rate'],
            'rating_band' => $listing['rating_band'],
            'rating_score' => number_format($listing['rating_score'], 1),
            'accent' => $listing['id'] % 10,
            'projected_return' => $listing['projected_return'],
        ];
    }
}
