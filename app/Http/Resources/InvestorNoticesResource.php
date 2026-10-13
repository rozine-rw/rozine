<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Route;

/**
 * The account's notices for the bell panel. Each notice carries its kind and facts for the client
 * to word, and the link to the screen that already holds its record, plus the action that marks it
 * read. A kind with no screen on this transport has a null link.
 *
 * @phpstan-import-type Inbox from \App\Application\Identity\Contracts\PartyNotices
 */
class InvestorNoticesResource extends JsonResource
{
    /** Where each kind opens, by route name and parameters. */
    private const array DESTINATIONS = [
        'verification.approved' => ['investor.deals', []],
        'verification.rejected' => ['investor.verification', []],
        'wallet.deposit_received' => ['investor.wallet', []],
        'primary.refunded' => ['investor.wallet', []],
        'security.password_changed' => ['investor.profile', ['section' => 'security']],
    ];

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var Inbox $inbox */
        $inbox = $this->resource;
        $prefix = $request->routeIs('api.*') ? 'api.v1.' : '';

        return ['unread' => $inbox['unread'],
            'notices' => array_map(fn (array $notice): array => [...$notice,
                'link' => $this->link($prefix, ...self::DESTINATIONS[$notice['kind']]),
                'read_action' => $notice['read'] ? null : ['url' => route($prefix.'investor.notices.read', ['notice' => $notice['id']], false), 'method' => 'post'],
            ], $inbox['notices']),
            'actions' => ['read_all' => $inbox['unread'] === 0 ? null : ['url' => route($prefix.'investor.notices.read_all', [], false), 'method' => 'post']]];
    }

    /**
     * @param  array<string, string>  $parameters
     * @return array{url: string, method: 'get'}|null
     */
    private function link(string $prefix, string $name, array $parameters): ?array
    {
        return Route::has($prefix.$name) ? ['url' => route($prefix.$name, $parameters, false), 'method' => 'get'] : null;
    }
}
