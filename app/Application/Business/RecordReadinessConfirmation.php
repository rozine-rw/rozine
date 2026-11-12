<?php

declare(strict_types=1);

namespace App\Application\Business;

use App\Application\Business\Contracts\ReadinessRequirementStore;
use App\Domain\Business\ReadinessRequirements;
use App\Domain\Operations\CommandRejection;

/**
 * What the CPA finds for one readiness requirement at the monthly visit (BRS v3.0 RDY-R03,
 * VIS-R01 steps 10–11): confirmed with its evidence, it is Met; no longer held, a Met requirement
 * Lapses with 60 days to be met again. This is the port the guided visit records through once
 * the Audit Partner seals it; the visit carries the Audit Partner's authority, so nothing here
 * grants any. A requirement that was never triggered cannot be confirmed or lapse.
 *
 * @phpstan-import-type Code from ReadinessRequirements
 */
final class RecordReadinessConfirmation
{
    public function __construct(private ReadinessRequirementStore $requirements, private ReadinessRequirements $rules) {}

    /**
     * @param  Code  $code
     * @param  array<string, mixed>  $evidence
     */
    public function confirm(string $businessId, string $code, array $evidence, string $today): void
    {
        if (! $this->requirements->move($businessId, $code, ['due', 'overdue', 'lapsed', 'met'], 'met', null, $today, $evidence)) {
            throw new CommandRejection('READINESS_REQUIREMENT_NOT_TRIGGERED', 409);
        }
    }

    /** @param  Code  $code */
    public function lapse(string $businessId, string $code, string $today): void
    {
        if (! $this->requirements->move($businessId, $code, ['met'], 'lapsed', $this->rules->lapsedDue($today))) {
            throw new CommandRejection('READINESS_REQUIREMENT_NOT_MET', 409);
        }
    }
}
