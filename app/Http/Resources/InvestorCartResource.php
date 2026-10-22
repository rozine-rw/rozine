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
 * Checkout is per line: there is no multi-note checkout command yet (INV-R03).
 */
class InvestorCartResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var array{identity_context_revision: int, verification: string|null, eligible: bool, lines: list<array<string, mixed>>|null} $data */
        $data = $this->resource;

        return ['contract_version' => 'investor-primary-v1', 'identity_context_revision' => $data['identity_context_revision'],
            'server_time' => now()->toIso8601String(), 'allowed_actions' => [], 'links' => [...InvestorShellPageResource::links(),
                'wallet' => $data['verification'] === null ? self::link('investor.wallet') : null],
            'cart' => $data['lines'] === null ? null : ['lines' => array_map(fn (array $line): array => self::line($line, $data['eligible']), $data['lines'])]];
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
