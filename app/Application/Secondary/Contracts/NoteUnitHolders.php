<?php

declare(strict_types=1);

namespace App\Application\Secondary\Contracts;

/**
 * Who holds each unit of an issued note now (SEC-R06, SEC-R07): every issued lot less the
 * ordinals its holders sold, and every lot bought on the market less what was resold from it. A
 * sale always moves a lot's lowest ordinals, so units keep their identity from issue to maturity
 * and each payout goes to whoever holds the units when the instalment is collected. Reads in the
 * caller's open transaction and never locks.
 *
 * @phpstan-import-type Lot from SecondaryNotes
 */
interface NoteUnitHolders
{
    /**
     * Every lot of the note still holding units, in issue and settlement order.
     *
     * @return list<Lot>
     */
    public function lots(string $noteId): array;

    /**
     * One lot of the note as it stands now, or null when the note has no such lot or it holds nothing any more.
     *
     * @return Lot|null
     */
    public function lot(string $noteId, string $lotType, string $lotId): ?array;

    /**
     * Every lot the user's Party bought on the market and still holds units of, newest first, with
     * the units it still holds (resold units leave it) and the price per unit it paid.
     *
     * @return list<array{lot_id: string, note_id: string, units: string, held_units: string, price: string, settled_at: string}>
     */
    public function boughtBy(int $userId): array;

    /** How many units of a lot its holder has sold on the market. */
    public function soldFrom(string $lotType, string $lotId): int;
}
