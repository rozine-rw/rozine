<?php

declare(strict_types=1);

namespace App\Application\Secondary;

use App\Application\Primary\GetInvestorViewer;
use App\Application\Secondary\Contracts\SecondaryBoard;

/**
 * The Market page's facts for the authenticated Investor (MVP-INVESTOR-SCR-06, BRS v3.0 §12): the
 * app frame's viewer and the secondary board. A person still being verified has no wallet to buy
 * or sell with, so their Market reads closed.
 *
 * @phpstan-import-type Board from SecondaryBoard
 */
final class GetInvestorMarket
{
    public function __construct(private GetInvestorViewer $viewer, private SecondaryBoard $board) {}

    /** @return array{identity_context_revision: int, verification: 'required'|'pending'|null, market: Board} */
    public function handle(int $userId, ?int $contextRevision): array
    {
        $viewer = $this->viewer->handle($userId, $contextRevision);

        return ['identity_context_revision' => $viewer['identity_context_revision'], 'verification' => $viewer['verification'],
            'market' => $viewer['wallet'] === null ? ['open' => false, 'listings' => [], 'sellable' => [], 'trades' => [], 'closed' => []]
                : $this->board->board($userId, $viewer['identity_context_revision'])];
    }
}
