<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The account's notices for the bell panel. Each notice carries its kind and facts for the client
 * to word, and the link to the screen that already holds its record, plus the action that marks it
 * read. A kind with no screen on this transport has a null link.
 *
 * @phpstan-import-type Inbox from \App\Application\Identity\Contracts\PartyNotices
 */
class InvestorNoticesResource extends JsonResource
{
    /** Where each kind opens, by route name. */
    private const array DESTINATIONS = [
        'verification.approved' => 'investor.deals',
        'verification.rejected' => 'investor.verification',
        'wallet.deposit_received' => 'investor.wallet',
    ];

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var Inbox $inbox */
        $inbox = $this->resource;
        $prefix = $request->routeIs('api.*') ? 'api.v1.' : '';

        return ['unread' => $inbox['unread'],
            'notices' => array_map(fn (array $notice): array => [...$notice,
                'link' => ['url' => route($prefix.self::DESTINATIONS[$notice['kind']], [], false), 'method' => 'get'],
                'read_action' => $notice['read'] ? null : ['url' => route($prefix.'investor.notices.read', ['notice' => $notice['id']], false), 'method' => 'post'],
            ], $inbox['notices']),
            'actions' => ['read_all' => $inbox['unread'] === 0 ? null : ['url' => route($prefix.'investor.notices.read_all', [], false), 'method' => 'post']]];
    }
}
