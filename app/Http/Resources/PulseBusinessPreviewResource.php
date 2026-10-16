<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PulseBusinessPreviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $preview */
        $preview = $this->resource;

        return [
            'rating' => $preview['rating'],
            'flat_rate' => $preview['flat_rate'],
            'sized_amount' => $preview['sized_amount'],
            'qualified_amount' => $preview['qualified_amount'],
            'monthly_repayment' => $preview['monthly_repayment'],
            'monthly_surplus' => $preview['monthly_surplus'],
            'cover_ratio' => $preview['cover_ratio'],
            'below_minimum' => $preview['below_minimum'],
            'at_maximum' => $preview['at_maximum'],
            'required_surplus' => $preview['required_surplus'],
            'status' => $preview['status'],
        ];
    }
}
