<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `C3InvestorCheckoutProps` (investor-primary-v1) for both transports. It shapes already
 * authorized facts and adds real routes only. A confirmed purchase is shown on its commitment
 * page, so `commitment` is always null here; there is no Deals home beneath the sheet. Portfolio
 * and Profile are web pages only, so the API carries their links as null.
 *
 * @phpstan-import-type CheckoutPage from \App\Application\Primary\GetInvestorCheckout
 */
class InvestorCheckoutResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var CheckoutPage $data */
        $data = $this->resource;
        $deal = $data['deal'];
        $campaign = (string) $deal['campaign_id'];
        $reservation = $data['reservation'];
        $held = $reservation === null ? null : (string) $reservation['reservation_id'];
        $page = fn (string $name): ?array => $request->routeIs('api.*') ? null : self::link($request, $name);
        $operation = route(self::prefix($request).'investor.primary.operations.show', array_filter(['campaign' => $campaign,
            'request_id' => '00000000-0000-0000-0000-000000000000', 'reservation' => $held]), false);

        return ['contract_version' => 'investor-primary-v1', 'identity_context_revision' => $data['identity_context_revision'],
            'server_time' => now()->toIso8601String(), 'allowed_actions' => $data['allowed_actions'],
            'deal' => ['campaign_id' => $campaign, 'revision' => $deal['revision'], 'name' => $deal['name'], 'accent' => $deal['accent'],
                'rate_pct' => $deal['rate_pct'], 'term_months' => $deal['term_months'], 'lifecycle' => $deal['lifecycle'],
                'restriction' => $deal['restriction'], 'clock' => $deal['clock'], 'unit_price' => $deal['unit_price']],
            'quote' => $data['quote'], 'quick_picks' => $data['quick_picks'], 'wallet' => $data['wallet'], 'disclosure' => $data['disclosure'],
            'reservation' => $reservation === null ? null : ['id' => $held, 'revision' => $reservation['revision'], 'campaign_id' => $campaign,
                'units' => $reservation['units'], 'ordinals' => $reservation['ordinal_ranges'], 'amount' => $reservation['principal'],
                'rights' => $reservation['rights'], 'state' => $reservation['state'],
                'clock' => ['starts_at' => $reservation['starts_at'], 'expires_at' => $reservation['expires_at']]],
            'commitment' => null, 'refusal' => null, 'home' => null,
            'risks' => $data['risks'] === null ? null : [...$data['risks'],
                'action' => $data['risks']['acknowledged'] ? null : self::action($request, 'investor.risks.acknowledge')],
            'links' => ['deals' => self::link($request, 'investor.deals'), 'portfolio' => $page('investor.portfolio'), 'market' => $page('investor.market'),
                'cart' => $page('investor.cart'), 'profile' => $page('investor.profile'), 'wallet' => self::link($request, 'investor.wallet'),
                'notifications' => null, 'launcher' => self::link($request, $request->routeIs('api.*') ? 'identity.show' : 'dashboard'),
                'close' => self::link($request, 'investor.deals.show', ['campaign' => $campaign]),
                'deposit' => self::link($request, 'investor.wallet', ['kind' => 'deposit']),
                'operation' => ['url' => str_replace('00000000-0000-0000-0000-000000000000', '{request_id}', $operation), 'method' => 'get']],
            'actions' => ['reserve' => self::action($request, 'investor.primary.reserve', ['campaign' => $campaign]),
                'confirm' => $held === null ? null : self::action($request, 'investor.primary.confirm', ['reservation' => $held]),
                'release' => $held === null ? null : self::action($request, 'investor.primary.release', ['reservation' => $held])]];
    }

    private static function prefix(Request $request): string
    {
        return $request->routeIs('api.*') ? 'api.v1.' : '';
    }

    /**
     * @param  array<string, string>  $parameters
     * @return array{url: string, method: 'get'}
     */
    private static function link(Request $request, string $name, array $parameters = []): array
    {
        return ['url' => route(self::prefix($request).$name, $parameters, false), 'method' => 'get'];
    }

    /**
     * @param  array<string, string>  $parameters
     * @return array{url: string, method: 'post'}
     */
    private static function action(Request $request, string $name, array $parameters = []): array
    {
        return ['url' => route(self::prefix($request).$name, $parameters, false), 'method' => 'post'];
    }
}
