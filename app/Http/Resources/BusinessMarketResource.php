<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `BusinessMarketProps`: the Market tab. Secondary trading has no demand, price, volume or
 * liquidity read and lists no note yet, so the page carries only its shell links and shows the
 * design's layout with nothing measured.
 */
class BusinessMarketResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var array{business_id: string} $data */
        $data = $this->resource;
        $business = $data['business_id'];

        return ['links' => ['home' => BusinessHomeResource::link($request, 'business.show', ['business' => $business]), ...BusinessHomeResource::tabs($request, $business),
            'launcher' => ['url' => route('dashboard', [], false), 'method' => 'get']]];
    }
}
