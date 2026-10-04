<?php

declare(strict_types=1);

namespace App\Application\Primary\Contracts;

use App\Domain\Primary\PrimaryTerms;
use App\Domain\Primary\UnitRights;
use Closure;

/**
 * The admission a new `primary.reserve` or `primary.confirm` runs under `PrimaryCheckout`'s locks:
 * current eligibility, connections, global exposure and fee policy, priced into the terms the
 * Investor is offered. The closure runs only for a new command, never on a replay, and must have
 * no external effects. It is handed what the Investor attested on screen, so a campaign or quote
 * that moved since is refused rather than re-priced silently.
 *
 * No source is bound yet: `UnavailablePrimaryAdmission` refuses `POLICY_INPUT_REQUIRED`, so the
 * purchase transport can be routed without any path fabricating a pass.
 */
interface PrimaryAdmission
{
    /** @return Closure(UnitRights, array<string, mixed>): PrimaryTerms */
    public function forReserve(int $userId, int $expectedCampaignRevision, int $quoteRevision): Closure;

    /** @return Closure(UnitRights, array<string, mixed>): PrimaryTerms */
    public function forConfirm(int $userId): Closure;
}
