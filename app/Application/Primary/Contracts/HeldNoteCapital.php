<?php

declare(strict_types=1);

namespace App\Application\Primary\Contracts;

/**
 * The held-note half of the INV-R04 fee basis (contract atomic-cart-checkout, #383 §3): the
 * outstanding principal of the notes a Party holds now. Wallet cash, reservations and confirmed
 * but unissued purchases are never part of it. When the source cannot prove the figure is
 * complete, `complete` is false and pricing must refuse rather than guess.
 *
 * @phpstan-type Capital array{outstanding_principal: string, revision: int, sha256: string, complete: bool, as_of: string}
 */
interface HeldNoteCapital
{
    /** @return Capital */
    public function current(string $partyId): array;
}
