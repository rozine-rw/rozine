<?php

declare(strict_types=1);

namespace App\Application\Secondary\Contracts;

/**
 * What the secondary market reads about one issued note, in the caller's open transaction: its
 * size, its contractual instalments, the lots it was issued as, its health and the last due date
 * whose payouts have posted. Null means the note has not been issued.
 *
 * Health and `posted_through` are servicing's facts (S4). Until servicing supplies them a note
 * reads as `current` with nothing posted: no instalment can be late before its first due date, and
 * from 23:00 Kigali on that date the note pauses until payouts post (SEC-R07), so nothing trades
 * on a note whose servicing state is unknown.
 *
 * @phpstan-type Lot array{lot_type: 'primary_holding'|'secondary_transfer', lot_id: string, party_id: string, units: string, ordinals: list<array{first: string, last: string}>}
 * @phpstan-type Note array{
 *     note_id: string, raise_units: string, health: 'current'|'late'|'defaulted'|'disputed', posted_through: string|null,
 *     instalments: list<array{due_on: string, principal: string, return: string}>, issued_lots: list<Lot>
 * }
 */
interface SecondaryNotes
{
    /** @return Note|null */
    public function find(string $noteId): ?array;
}
