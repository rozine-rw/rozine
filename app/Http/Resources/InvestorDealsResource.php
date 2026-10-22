<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `C3InvestorDealsProps` (investor-primary-v1) for both transports, or `C3InvestorDealProps` when
 * the Resource is for one deal. It shapes already authorized facts and adds real routes only.
 * There is no quote here; the checkout link opens the live checkout for the focused deal when
 * the viewer may invest, and a destination with no live route yet is null. Portfolio and Profile are web pages only, so the API carries their links as
 * null. A person still being verified gets the deck behind the verification gate, with no wallet
 * and no wallet links. An Investor who may invest also gets the deals in their cart and the
 * focused deal's add or remove command; In cart filters the deck to them.
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
            'home' => $home, 'links' => ['back' => self::link($request, 'investor.deals'), 'checkout' => $home['links']['checkout']]];
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
        $page = fn (string $name): ?array => $request->routeIs('api.*') ? null : self::link($request, $name);

        return ['contract_version' => 'investor-primary-v1', 'identity_context_revision' => $data['identity_context_revision'],
            'server_time' => now()->toIso8601String(), 'allowed_actions' => [], 'gate' => self::gate($request, $data),
            'wallet' => $funded ? ['available' => $data['available'], 'next_payout' => null] : null, 'unread_notifications' => $data['unread_notifications'],
            'sorts' => array_map(fn (string $key): array => ['key' => $key, 'active' => $key === $data['sort'], 'link' => $filter($key, $data['industry'])],
                ['all', 'top_interest', 'top_rated', 'top_picks', 'in_cart']),
            'industries' => array_map(fn (array $row): array => [...$row, 'active' => $row['industry'] === $data['industry'],
                'link' => $filter($data['sort'], $row['industry'])], $data['industries']),
            'deals' => array_map(fn (array $deal): array => self::withLink($request, $deal), $data['deals']),
            'focus' => $focus === null ? null : self::withLink($request, $focus), 'quote' => null,
            'cart' => $funded && ! $data['restricted'] ? self::cart($request, $data['cart'] ?? [], $focus) : null,
            'links' => ['deals' => $deals(), 'portfolio' => $page('investor.portfolio'), 'market' => $page('investor.market'), 'cart' => $page('investor.cart'), 'profile' => $page('investor.profile'),
                'wallet' => $funded ? self::link($request, 'investor.wallet') : null,
                'notifications' => self::link($request, 'investor.notices'), 'launcher' => self::link($request, $request->routeIs('api.*') ? 'identity.show' : 'dashboard'),
                'deposit' => $funded ? self::link($request, 'investor.wallet', ['kind' => 'deposit']) : null,
                'withdraw' => $funded ? self::link($request, 'investor.wallet', ['kind' => 'withdraw']) : null,
                'checkout' => $funded && ! $data['restricted'] && $focus !== null ? self::link($request, 'investor.checkout', ['campaign' => (string) $focus['campaign_id']]) : null]];
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
     * The deals already in the Investor's cart (BRS v2.0 INV-R02), and the focused deal's cart
     * command: add while it is open with notes left and not yet in the cart, remove once it is.
     *
     * @param  list<string>  $campaignIds
     * @param  array<string, mixed>|null  $focus
     * @return array{campaign_ids: list<string>, add: array{url: string, method: 'post'}|null, remove: array{url: string, method: 'post'}|null}
     */
    private static function cart(Request $request, array $campaignIds, ?array $focus): array
    {
        $id = $focus === null ? null : (string) $focus['campaign_id'];
        $inCart = $id !== null && in_array($id, $campaignIds, true);
        $open = $focus !== null && ($focus['lifecycle'] ?? null) === 'live' && (string) ($focus['units']['available'] ?? '0') !== '0';

        return ['campaign_ids' => $campaignIds,
            'add' => $id !== null && ! $inCart && $open ? self::action($request, 'investor.cart_add', ['campaign' => $id]) : null,
            'remove' => $inCart ? self::action($request, 'investor.cart_remove', ['campaign' => $id]) : null];
    }

    /**
     * @param  array<string, string>  $parameters
     * @return array{url: string, method: 'post'}
     */
    private static function action(Request $request, string $name, array $parameters): array
    {
        return ['url' => route(($request->routeIs('api.*') ? 'api.v1.' : '').$name, $parameters, false), 'method' => 'post'];
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
