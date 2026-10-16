<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `InvestorVerifiedProps` for the web page: a verified Investor's available wallet balance as the
 * ledger reports it, and how many deals in their deck are open (`live`) now. It counts; it
 * calculates no amount.
 */
class InvestorVerifiedResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var array{available: array{currency: string, amount: string}, deals: list<array<string, mixed>>} $data */
        $data = $this->resource;

        return ['wallet' => ['available' => $data['available']],
            'open_deals' => count(array_filter($data['deals'], fn (array $deal): bool => $deal['lifecycle'] === 'live')),
            'links' => ['deals' => ['url' => route('investor.deals', [], false), 'method' => 'get']]];
    }
}
