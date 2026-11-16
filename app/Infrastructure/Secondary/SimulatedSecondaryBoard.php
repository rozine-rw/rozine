<?php

declare(strict_types=1);

namespace App\Infrastructure\Secondary;

use App\Application\Environment\UatMoneySimulation;
use App\Application\Secondary\Contracts\SecondaryBoard;
use Illuminate\Support\Facades\DB;

/**
 * The Market on UAT while its money simulation is on: the real board, showing only the listings and
 * lots of the synthetic Businesses staging names, as `SimulatedSecondaryMarket` trades only those.
 */
final readonly class SimulatedSecondaryBoard implements SecondaryBoard
{
    public function __construct(private SecondaryBoard $board, private UatMoneySimulation $simulation) {}

    public function board(int $userId, ?int $contextRevision): array
    {
        $board = $this->board->board($userId, $contextRevision);
        $covered = [];
        $covers = function (string $noteId) use (&$covered): bool {
            return $covered[$noteId] ??= $this->simulation->covers((string) DB::table('business_campaigns')->where('id', $noteId)->value('business_id'));
        };

        return [...$board, 'listings' => array_values(array_filter($board['listings'], fn (array $listing): bool => $covers($listing['note_id']))),
            'sellable' => array_values(array_filter($board['sellable'], fn (array $lot): bool => $covers($lot['note_id'])))];
    }
}
