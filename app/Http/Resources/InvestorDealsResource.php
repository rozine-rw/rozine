<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `C3InvestorDealsProps` (investor-primary-v1) for both transports, or `C3InvestorDealProps` when
 * the Resource is for one deal. It shapes already authorized facts and adds real routes only.
 * There is no quote and no checkout until admission exists, and a destination with no live
 * route yet is null. A person still being verified gets the deck behind the verification gate,
 * with no wallet and no wallet links.
 */
class InvestorDealsResource extends JsonResource
{
    /** @param array<string, mixed> $resource */
    public function __construct(mixed $resource, private bool $single = false)
    {
        parent::__construct($resource);
    }

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $data */
        $data = $this->resource;
        $home = $this->home($request, $data);
        if (! $this->single) {
            return $home;
        }

        return ['contract_version' => 'investor-primary-v1', 'identity_context_revision' => $home['identity_context_revision'],
            'server_time' => $home['server_time'], 'allowed_actions' => [], 'deal' => $home['focus'], 'gate' => $home['gate'], 'quote' => null,
            'home' => $home, 'links' => ['back' => self::link($request, 'investor.deals'), 'checkout' => null]];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function home(Request $request, array $data): array
    {
        $deals = fn (array $query = []): array => self::link($request, 'investor.deals', $query);
        $filter = fn (string $sort, ?string $industry): array => $deals(array_filter(['sort' => $sort === 'all' ? null : $sort, 'industry' => $industry]));
        $focus = $data['focus'];
        // Before verification there is no wallet to show or fund.
        $funded = $data['available'] !== null;

        return ['contract_version' => 'investor-primary-v1', 'identity_context_revision' => $data['identity_context_revision'],
            'server_time' => now()->toIso8601String(), 'allowed_actions' => [], 'gate' => self::gate($request, $data),
            'wallet' => $funded ? ['available' => $data['available'], 'next_payout' => null] : null, 'unread_notifications' => 0,
            'sorts' => array_map(fn (string $key): array => ['key' => $key, 'active' => $key === $data['sort'], 'link' => $filter($key, $data['industry'])],
                ['all', 'top_interest', 'top_rated']),
            'industries' => array_map(fn (array $row): array => [...$row, 'active' => $row['industry'] === $data['industry'],
                'link' => $filter($data['sort'], $row['industry'])], $data['industries']),
            'deals' => array_map(fn (array $deal): array => self::withLink($request, $deal), $data['deals']),
            'focus' => $focus === null ? null : self::withLink($request, $focus), 'quote' => null,
            'links' => ['deals' => $deals(), 'portfolio' => null, 'profile' => null, 'wallet' => $funded ? self::link($request, 'investor.wallet') : null,
                'notifications' => null, 'launcher' => self::link($request, $request->routeIs('api.*') ? 'identity.show' : 'dashboard'),
                'deposit' => $funded ? self::link($request, 'investor.wallet', ['kind' => 'deposit']) : null, 'checkout' => null]];
    }

    /**
     * Whether this viewer may invest, or what stands before it.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private static function gate(Request $request, array $data): array
    {
        return match ($data['verification']) {
            'pending' => ['status' => 'verification_pending'],
            'required' => ['status' => 'verification_required', 'link' => self::link($request, 'investor.verification')],
            default => ['status' => $data['restricted'] ? 'restricted' : 'eligible'],
        };
    }

    /**
     * @param  array<string, mixed>  $deal
     * @return array<string, mixed>
     */
    private static function withLink(Request $request, array $deal): array
    {
        return [...$deal, 'links' => ['detail' => self::link($request, 'investor.deals.show', ['campaign' => (string) $deal['campaign_id']])]];
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
