<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `C3InvestorPortfolioProps` (investor-primary-v1) for the web page. It shapes the facts
 * `GetInvestorPortfolio` read and adds real routes only. Nothing is held yet, so the holdings,
 * payout schedule, exposure and commitments awaiting issue are empty and every total is the zero
 * of an empty portfolio, never a projection; idle cash is the wallet's own figure. A destination
 * with no live route yet is null, and a person still being verified has no wallet to link.
 */
class InvestorPortfolioResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var array{identity_context_revision: int, verified: bool, idle: array{currency: string, amount: string}|null, tab: string} $data */
        $data = $this->resource;
        $zero = ['currency' => 'RWF', 'amount' => '0'];
        $portfolio = fn (array $query = []): array => self::link('investor.portfolio', $query);

        return ['contract_version' => 'investor-primary-v1', 'identity_context_revision' => $data['identity_context_revision'], 'server_time' => now()->toIso8601String(),
            'allowed_actions' => [], 'tab' => $data['tab'],
            'tabs' => array_map(fn (string $key): array => ['key' => $key, 'active' => $key === $data['tab'], 'link' => $portfolio(['tab' => $key])], ['active', 'matured']),
            'totals' => ['businesses' => 0, 'value' => $zero, 'invested' => $zero, 'gain' => $zero, 'this_month' => $zero, 'projected_3m' => $zero, 'next_payout' => null,
                'avg_monthly' => $zero],
            'holdings' => [], 'payouts' => [], 'industries' => [], 'risk' => [], 'concentration' => null, 'idle' => $data['idle'], 'commitments' => [],
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
