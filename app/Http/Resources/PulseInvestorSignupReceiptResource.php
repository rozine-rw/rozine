<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PulseInvestorSignupReceiptResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $receipt */
        $receipt = $this->resource;

        return [
            'queue_number' => $receipt['queue_number'],
            'traction' => $receipt['traction'],
            'investor_preview' => (new PulseInvestorPreviewResource($receipt['investor_preview']))->resolve($request),
        ];
    }
}
