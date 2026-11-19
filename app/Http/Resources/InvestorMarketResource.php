<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The Market page (`investor-market-v1`) from `GetInvestorMarket`: the app frame's links and the
 * secondary board, each listing with the commands open to this Investor on it. Someone else's
 * listing can be reserved unless another buyer holds it; the Investor's own reservation can be
 * settled; their own listing can be withdrawn. A lot with free units on a note whose trading window
 * is open can be listed. While trading is closed nothing is offered.
 *
 * @phpstan-import-type Board from \App\Application\Secondary\Contracts\SecondaryBoard
 */
class InvestorMarketResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var array{identity_context_revision: int, verification: string|null, market: Board} $data */
        $data = $this->resource;
        $market = $data['market'];
        $open = $market['open'];
        $post = fn (string $name, array $parameters = []): array => ['url' => route($name, $parameters, false), 'method' => 'post'];

        return ['contract_version' => 'investor-market-v1', 'identity_context_revision' => $data['identity_context_revision'],
            'server_time' => now()->toIso8601String(),
            'links' => [...InvestorShellPageResource::links(), 'wallet' => $data['verification'] === null ? ['url' => route('investor.wallet', [], false), 'method' => 'get'] : null],
            'market' => ['open' => $open,
                'listings' => array_map(fn (array $listing): array => [...$listing, 'actions' => [
                    'reserve' => $open && ! $listing['mine'] && ! $listing['reserved'] && $listing['reservation'] === null
                        ? $post('investor.market_reserve', ['listing' => $listing['listing_id']]) : null,
                    'settle' => $open && $listing['reservation'] !== null ? $post('investor.market_settle', ['listing' => $listing['listing_id']]) : null,
                    'withdraw' => $open && $listing['mine'] ? $post('investor.market_withdraw', ['listing' => $listing['listing_id']]) : null]],
                    $market['listings']),
                'sellable' => $market['sellable'], 'trades' => $market['trades'], 'closed' => $market['closed']],
            'actions' => ['list' => $open && array_filter($market['sellable'], fn (array $lot): bool => $lot['tradable'] && $lot['free_units'] !== '0') !== []
                ? $post('investor.market_list') : null]];
    }
}
