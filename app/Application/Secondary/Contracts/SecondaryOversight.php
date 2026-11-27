<?php

declare(strict_types=1);

namespace App\Application\Secondary\Contracts;

/**
 * What Compliance reads before halting or cancelling on the secondary market (BRS v3.0 SEC-R08,
 * SEC-R09), under the staff `secondary.halt` permission: every halt still in force (the whole
 * market when `note_id` is null, else one note) and the open listings a staff cancel could end,
 * newest first. It reads whether or not trading is switched on, so a halt can be seen and lifted.
 *
 * @phpstan-type Halt array{halt_id: string, note_id: string|null, deal_name: string|null, reason: string, halted_at: string}
 * @phpstan-type StaffListing array{listing_id: string, note_id: string, deal_name: string, remaining_units: string,
 *     price: array{currency: string, amount: string}, listed_at: string, expires_at: string}
 * @phpstan-type Oversight array{halts: list<Halt>, listings: list<StaffListing>}
 */
interface SecondaryOversight
{
    /** @return Oversight */
    public function overview(int $staffUserId): array;
}
