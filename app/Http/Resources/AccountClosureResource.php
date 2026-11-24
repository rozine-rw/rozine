<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The Close account section of the profile settings: the account's roles and what still blocks its
 * closure, each blocker with a link to the page that settles it.
 */
class AccountClosureResource extends JsonResource
{
    /** Where each blocker is settled: a route name and whether it takes the business. */
    private const LINKS = [
        'INVESTOR_WALLET_BALANCE' => ['investor.wallet', false],
        'INVESTOR_MONEY_MOVING' => ['investor.wallet', false],
        'INVESTOR_COMMITMENTS' => ['investor.portfolio', false],
        'INVESTOR_HOLDINGS' => ['investor.portfolio', false],
        'INVESTOR_MARKET_ORDERS' => ['investor.market', false],
        'BUSINESS_LIVE_RAISE' => ['business.show', true],
        'BUSINESS_APPLICATION_IN_REVIEW' => ['business.show', true],
        'BUSINESS_REPAYMENTS_OWED' => ['business.repayments.show', true],
        'BUSINESS_WALLET_BALANCE' => ['business.wallet.show', true],
        'BUSINESS_MONEY_MOVING' => ['business.wallet.show', true],
        'AUDITOR_OPEN_AUDITS' => ['auditor.jobs.index', false],
        'AUDITOR_REPORTS_PENDING' => ['auditor.jobs.index', false],
    ];

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var array{roles: list<string>, blockers: list<array{code: string, role: string, business_id: string|null}>} $standing */
        $standing = $this->resource;

        return ['roles' => $standing['roles'], 'blockers' => array_map(fn (array $blocker): array => [
            'code' => $blocker['code'], 'role' => $blocker['role'], 'link' => self::link($blocker['code'], $blocker['business_id']),
        ], $standing['blockers'])];
    }

    /** @return array{url: string, method: 'get'}|null */
    private static function link(string $code, ?string $businessId): ?array
    {
        if (! isset(self::LINKS[$code])) {
            return null;
        }
        [$route, $takesBusiness] = self::LINKS[$code];

        return ['url' => route($route, $takesBusiness ? ['business' => $businessId] : [], false), 'method' => 'get'];
    }
}
