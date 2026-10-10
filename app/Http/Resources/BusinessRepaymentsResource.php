<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `BusinessRepaymentsProps` (business-servicing-v1) with no servicing note. No servicing domain is
 * bound yet, so no note of this Business has a schedule, an amount due or a payable option, and
 * `repayment.pay` is refused before anything is debited. The sheet therefore carries no note,
 * servicing, schedule, ladder or pay panel, and no Pay action; it opens on its empty state over
 * the Business's real Home, with the wallet's deposit panel as its only way on.
 */
class BusinessRepaymentsResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $data */
        $data = $this->resource;
        $home = (new BusinessHomeResource($data))->toArray($request);
        $business = (string) $data['entry']['business_id'];
        $placeholder = '00000000-0000-0000-0000-000000000000';
        $operation = route('business.repayments.operations.show', ['business' => $business, 'request_id' => $placeholder, 'command' => 'repayment.pay',
            'identity_context_revision' => $data['identity_context_revision']], false);

        return ['contract_version' => 'business-servicing-v1', 'identity_context_revision' => $data['identity_context_revision'], 'server_time' => now()->toIso8601String(),
            'allowed_actions' => [], 'note' => null, 'servicing' => null, 'schedule' => [], 'ladder' => null, 'pay' => null, 'receipt' => null, 'recent' => [],
            'bases' => (object) [], 'home' => $home,
            'shell_links' => ['home' => $home['links']['home'], 'launcher' => $home['links']['launcher'], 'reports' => $home['links']['reports'], 'market' => $home['links']['market'], 'profile' => $home['links']['profile']],
            'links' => ['close' => $home['links']['home'], 'top_up' => $home['links']['deposit'], 'operation' => ['url' => str_replace($placeholder, '{request_id}', $operation), 'method' => 'get']],
            'actions' => ['pay' => null]];
    }
}
