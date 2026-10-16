<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `ChangeFeed`: which topics changed, never what they now are. Unwrapped, for both transports.
 */
class ChangeFeedResource extends JsonResource
{
    /** @var string|null */
    public static $wrap = null;

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var array{changes: list<array{topic: string, subject: string, revision: int}>, next_cursor: string, reset: bool, server_time: string, poll_after_ms: int} $feed */
        $feed = $this->resource;

        return ['contract_version' => 'change-feed-v1', 'changes' => $feed['changes'], 'next_cursor' => $feed['next_cursor'],
            'reset' => $feed['reset'], 'server_time' => $feed['server_time'], 'poll_after_ms' => $feed['poll_after_ms']];
    }
}
