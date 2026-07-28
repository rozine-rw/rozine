<?php

namespace App\Http\Resources;

use App\Models\PulseSignup;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * @mixin PulseSignup
 */
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
        $label = $this->listed ? $this->name : "Business in {$this->district}";

        return [
            'id' => $this->id,
            'initial' => Str::upper(Str::substr($this->listed ? $this->name : $this->district, 0, 1)),
            'name' => $label,
            'district' => $this->district,
            'term' => "{$this->term_months}mo",
            'yield' => number_format((float) $this->flat_rate, 1).'%',
            'yield_rate' => (float) $this->flat_rate,
            'rating_band' => $this->rating_band,
            'rating_score' => number_format((float) $this->rating_score, 1),
            'accent' => $this->id % 10,
        ];
    }
}
