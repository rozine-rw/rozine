<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The Cart page (BRS v2.0 INV-R02, web only): the app frame's links and, once the identity is
 * verified, the cart's lines. Each line carries its deal's card, the chosen units, the most this
 * Party may take there now and one unit's flat return, then its own commands and the deal's live
 * checkout, where the client adds the chosen `units`. A filled line keeps its card and Remove only.
 * The cart's id, revision, snapshot hash and deal and note counts come with the lines (#383), with
 * the whole-cart commands: quote the cart, then hold every line at once. While a whole-cart
 * checkout is live the cart cannot change, so its lines offer no commands of their own and the
 * checkout comes with its Confirm all and Release.
 */
class InvestorCartResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var array{identity_context_revision: int, verification: string|null, eligible: bool, cart: array<string, mixed>|null, lines: list<array<string, mixed>>|null, checkout: array<string, mixed>|null} $data */
        $data = $this->resource;
        $checkout = $data['checkout'];
        $operation = route('investor.cart_operation', ['request_id' => '00000000-0000-0000-0000-000000000000'], false);
        $open = $data['eligible'] && $checkout === null;

        return ['contract_version' => 'investor-primary-v1', 'identity_context_revision' => $data['identity_context_revision'],
            'server_time' => now()->toIso8601String(), 'allowed_actions' => [], 'links' => [...InvestorShellPageResource::links(),
                'wallet' => $data['verification'] === null ? self::link('investor.wallet') : null],
            'cart' => $data['lines'] === null ? null : [...(array) $data['cart'],
                'lines' => array_map(fn (array $line): array => self::line($line, $open), $data['lines'])],
            'checkout' => $checkout === null ? null : [...$checkout, 'actions' => self::held($checkout)],
            'actions' => ['quote' => $open ? self::action('investor.cart_quote', []) : null, 'reserve' => $open ? self::action('investor.cart_reserve', []) : null],
            'operation' => ['url' => str_replace('00000000-0000-0000-0000-000000000000', '{request_id}', $operation), 'method' => 'get']];
    }

    /**
     * A held checkout is confirmed or released as a whole; one past its deadline only waits for its
     * expiry to settle.
     *
     * @param  array<string, mixed>  $checkout
     * @return array{confirm: array{url: string, method: 'post'}|null, release: array{url: string, method: 'post'}|null}
     */
    private static function held(array $checkout): array
    {
        $checkoutId = ['checkout' => (string) $checkout['id']];
        $held = $checkout['state'] === 'held';

        return ['confirm' => $held ? self::action('investor.cart_confirm', $checkoutId) : null,
            'release' => $held ? self::action('investor.cart_release', $checkoutId) : null];
    }

    /**
     * @param  array<string, mixed>  $line
     * @return array<string, mixed>
     */
    private static function line(array $line, bool $eligible): array
    {
        $campaign = ['campaign' => (string) $line['campaign_id']];
        /** @var array<string, mixed> $deal */
        $deal = $line['deal'];
        $buyable = $eligible && ! $line['filled'] && $line['max_units'] !== '0';

        return [...$line, 'deal' => [...$deal, 'links' => ['detail' => self::link('investor.deals.show', $campaign)]],
            'checkout' => $buyable ? self::link('investor.checkout', $campaign) : null,
            'actions' => ['units' => $buyable ? self::action('investor.cart_units', $campaign) : null, 'remove' => self::action('investor.cart_remove', $campaign)]];
    }

    /**
     * @param  array<string, string>  $parameters
     * @return array{url: string, method: 'get'}
     */
    private static function link(string $name, array $parameters = []): array
    {
        return ['url' => route($name, $parameters, false), 'method' => 'get'];
    }

    /**
     * @param  array<string, string>  $parameters
     * @return array{url: string, method: 'post'}
     */
    private static function action(string $name, array $parameters): array
    {
        return ['url' => route($name, $parameters, false), 'method' => 'post'];
    }
}
