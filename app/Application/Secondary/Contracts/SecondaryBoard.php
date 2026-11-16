<?php

declare(strict_types=1);

namespace App\Application\Secondary\Contracts;

/**
 * What the Market page reads for the authenticated Investor (BRS v3.0 §12), under current Investor
 * authority: the open listings anyone can buy from (their own marked as theirs, one held by another
 * buyer's live reservation marked as reserved), the lots they could list with their free units and
 * the price cap, and their recent resale trades. `open` is false while secondary trading is off,
 * and everything else is then empty.
 *
 * @phpstan-type Money array{currency: string, amount: string}
 * @phpstan-type Reservation array{reservation_id: string, units: string, price: Money, buyer_pays: Money, expires_at: string}
 * @phpstan-type BoardListing array{listing_id: string, note_id: string, deal_name: string, remaining_units: string, price: Money, listed_at: string,
 *     expires_at: string, mine: bool, reserved: bool, reservation: Reservation|null}
 * @phpstan-type SellableLot array{lot_id: string, note_id: string, deal_name: string, units: string, free_units: string, cap: Money, tradable: bool}
 * @phpstan-type Trade array{transfer_id: string, side: 'buy'|'sell', deal_name: string, units: string, price: Money, amount: Money, fee: Money, settled_at: string}
 * @phpstan-type Board array{open: bool, listings: list<BoardListing>, sellable: list<SellableLot>, trades: list<Trade>}
 */
interface SecondaryBoard
{
    /** @return Board */
    public function board(int $userId, ?int $contextRevision): array;
}
