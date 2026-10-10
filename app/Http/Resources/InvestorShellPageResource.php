<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The Investor app frame's links for a page that has no facts of its own yet (Market, Cart). The
 * wallet opens only once the identity is verified, as on every Investor page.
 */
class InvestorShellPageResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var array{identity_context_revision: int, verification: string|null} $viewer */
        $viewer = $this->resource;

        return ['identity_context_revision' => $viewer['identity_context_revision'], 'links' => [...self::links(),
            'wallet' => $viewer['verification'] === null ? self::link('investor.wallet') : null]];
    }

    /**
     * The sidebar and tab bar destinations every web Investor page shares.
     *
     * @return array{deals: array{url: string, method: 'get'}, portfolio: array{url: string, method: 'get'}, market: array{url: string, method: 'get'},
     *     cart: array{url: string, method: 'get'}, profile: array{url: string, method: 'get'}, notifications: null, launcher: array{url: string, method: 'get'}}
     */
    public static function links(): array
    {
        return ['deals' => self::link('investor.deals'), 'portfolio' => self::link('investor.portfolio'), 'market' => self::link('investor.market'),
            'cart' => self::link('investor.cart'), 'profile' => self::link('investor.profile'), 'notifications' => null, 'launcher' => self::link('dashboard')];
    }

    /** @return array{url: string, method: 'get'} */
    private static function link(string $name): array
    {
        return ['url' => route($name, [], false), 'method' => 'get'];
    }
}
