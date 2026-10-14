<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `C3InvestorPortfolioProps` (investor-primary-v1) for the web page. It shapes the facts
 * `GetInvestorPortfolio` read and adds real routes only: each issued Holding opens its Holding
 * page. The totals, projected payouts, industries, risk bands and concentration are the server's.
 * Nothing has matured yet, so the Active tab lists every Holding and Matured opens empty;
 * Secondary (holdings listed for resale) and Saved (the watchlist) have no read yet either.
 * Commitments awaiting issue are not listed yet. A destination with no live route yet is null,
 * and a person still being verified has no wallet to link.
 *
 * @phpstan-import-type PortfolioFacts from \App\Application\Primary\GetInvestorPortfolio
 */
class InvestorPortfolioResource extends JsonResource
{
    /** The design's Portfolio tabs (Investor.dc.html L8073), in its order. */
    private const TABS = ['active', 'matured', 'secondary', 'saved'];

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var array{portfolio: PortfolioFacts, tab: string} $resource */
        $resource = $this->resource;
        $data = [...$resource['portfolio'], 'tab' => $resource['tab']];
        $portfolio = fn (array $query = []): array => self::link('investor.portfolio', $query);

        return ['contract_version' => 'investor-primary-v1', 'identity_context_revision' => $data['identity_context_revision'], 'server_time' => now()->toIso8601String(),
            'allowed_actions' => [], 'tab' => $data['tab'],
            'tabs' => array_map(fn (string $key): array => ['key' => $key, 'active' => $key === $data['tab'], 'link' => $portfolio(['tab' => $key])], self::TABS),
            'totals' => $data['totals'],
            'holdings' => $data['tab'] === 'active' ? array_map(fn (array $holding): array => [...$holding,
                'link' => self::link('investor.holdings.show', ['holding' => $holding['id']])], $data['holdings']) : [],
            'payouts' => $data['payouts'], 'industries' => $data['industries'], 'risk' => $data['risk'], 'concentration' => $data['concentration'],
            'idle' => $data['idle'], 'commitments' => [],
            'links' => ['deals' => self::link('investor.deals'), 'portfolio' => $portfolio(), 'market' => self::link('investor.market'), 'cart' => self::link('investor.cart'), 'profile' => self::link('investor.profile'),
                'wallet' => $data['verified'] ? self::link('investor.wallet') : null, 'notifications' => null, 'launcher' => self::link('dashboard')]];
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
