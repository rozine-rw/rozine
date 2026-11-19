<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The bought-lot page (`investor-lot-v1`) from `GetInvestorLot`: one lot bought on the market, what
 * it cost, what it has been paid and its schedule, with the app frame's links. Back returns to the
 * Portfolio's Secondary tab, where the lot is listed; the Market link lets the Investor resell it.
 *
 * @phpstan-import-type Lot from \App\Application\Primary\GetInvestorLot
 */
class InvestorLotResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var array{identity_context_revision: int, lot: Lot, changes_cursor?: string} $data */
        $data = $this->resource;

        return ['contract_version' => 'investor-lot-v1', 'identity_context_revision' => $data['identity_context_revision'], 'server_time' => now()->toIso8601String(),
            'lot' => [...$data['lot'], 'link' => self::link('investor.lots.show', ['lot' => $data['lot']['lot_id']])],
            'links' => ['deals' => self::link('investor.deals'), 'portfolio' => self::link('investor.portfolio'), 'market' => self::link('investor.market'),
                'cart' => self::link('investor.cart'), 'profile' => self::link('investor.profile'), 'wallet' => self::link('investor.wallet'), 'notifications' => null,
                'launcher' => self::link('dashboard'), 'back' => self::link('investor.portfolio', ['tab' => 'secondary']),
                'changes' => isset($data['changes_cursor']) ? self::link('changes.index', ['topics' => 'purchase,wallet', 'after' => $data['changes_cursor']]) : null]];
    }

    /**
     * @param  array<string, string>  $query
     * @return array{url: string, method: 'get'}
     */
    private static function link(string $name, array $query = []): array
    {
        return ['url' => route($name, $query, false), 'method' => 'get'];
    }
}
