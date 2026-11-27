<?php

declare(strict_types=1);

namespace App\Application\Secondary\Contracts;

/**
 * What Compliance reads before halting or cancelling on the secondary market (BRS v3.0 SEC-R08,
 * SEC-R09), under the staff `secondary.halt` permission: every halt still in force (the whole
 * market when `note_id` is null, else one note) and the open listings a staff cancel could end,
 * newest first, and the issued notes a halt can name whether or not they are listed. It reads
 * whether or not trading is switched on, so a halt can be seen and lifted; `mode` says how resale
 * runs: `live` when switched on, `simulated` while UAT's money simulation serves it, else `off`.
 *
 * @phpstan-type Halt array{halt_id: string, note_id: string|null, deal_name: string|null, reason: string, halted_at: string}
 * @phpstan-type StaffListing array{listing_id: string, note_id: string, deal_name: string, remaining_units: string,
 *     price: array{currency: string, amount: string}, listed_at: string, expires_at: string}
 * @phpstan-type IssuedNote array{note_id: string, deal_name: string}
 * @phpstan-type Oversight array{mode: 'live'|'simulated'|'off', halts: list<Halt>, listings: list<StaffListing>, notes: list<IssuedNote>}
 */
interface SecondaryOversight
{
    /** @return Oversight */
    public function overview(int $staffUserId): array;
}
