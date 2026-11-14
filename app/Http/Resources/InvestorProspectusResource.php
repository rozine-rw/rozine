<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `InvestorProspectusProps` (investor-prospectus-v1): the signed prospectus behind one deal, with
 * its version, fingerprint and signer, the approved terms and the 3% platform fee, and which
 * use-of-funds lines and forecast months cite uploaded evidence. Back returns to the deal, and the
 * PDF link downloads the same signed version.
 */
class InvestorProspectusResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var array{campaign_id: string, business_name: string, summary: array<string, mixed>, inputs: array<string, mixed>,
         *     facts: array{principal: string, term_months: int, instalment: string, verified_monthly_revenue: string, latest_sealed_report_at: string|null},
         *     quoted_lines: list<int>, contracted_months: list<string>} $data */
        $data = $this->resource;
        $page = fn (string $name): ?array => $request->routeIs('api.*') ? null : self::link($request, $name);
        $fee = (string) intdiv((int) $data['facts']['principal'] * 3, 100);

        return ['contract_version' => 'investor-prospectus-v1', 'server_time' => now()->toIso8601String(),
            'deal' => ['campaign_id' => $data['campaign_id'], 'name' => $data['business_name']], 'prospectus' => $data['summary'],
            'inputs' => $data['inputs'], 'facts' => [...$data['facts'], 'platform_fee' => $fee, 'net_of_fee' => (string) ((int) $data['facts']['principal'] - (int) $fee)],
            'quoted_lines' => $data['quoted_lines'], 'contracted_months' => $data['contracted_months'],
            'links' => ['deals' => self::link($request, 'investor.deals'), 'portfolio' => $page('investor.portfolio'), 'market' => $page('investor.market'),
                'cart' => $page('investor.cart'), 'profile' => $page('investor.profile'), 'wallet' => null, 'notifications' => null,
                'launcher' => self::link($request, $request->routeIs('api.*') ? 'identity.show' : 'dashboard'),
                'back' => self::link($request, 'investor.deals.show', ['campaign' => $data['campaign_id']]),
                'pdf' => self::link($request, 'investor.deals.prospectus_pdf', ['campaign' => $data['campaign_id']])]];
    }

    /**
     * @param  array<string, string>  $parameters
     * @return array{url: string, method: 'get'}
     */
    private static function link(Request $request, string $name, array $parameters = []): array
    {
        return ['url' => route(($request->routeIs('api.*') ? 'api.v1.' : '').$name, $parameters, false), 'method' => 'get'];
    }
}
