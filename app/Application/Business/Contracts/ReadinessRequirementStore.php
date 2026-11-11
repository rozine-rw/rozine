<?php

declare(strict_types=1);

namespace App\Application\Business\Contracts;

/**
 * A Business's triggered readiness requirements (BRS v3.0 RDY-R03), one row per business and
 * requirement, kept from the Kigali day each was triggered. Every write is conditional on the
 * status it moves from, so a replayed evaluation changes nothing.
 *
 * @phpstan-import-type Code from \App\Domain\Business\ReadinessRequirements
 * @phpstan-import-type Status from \App\Domain\Business\ReadinessRequirements
 *
 * @phpstan-type Requirement array{code: Code, status: Status, triggered_on: string, due_on: string|null, met_on: string|null}
 */
interface ReadinessRequirementStore
{
    /**
     * The Business's triggered requirements in BRS order.
     *
     * @return list<Requirement>
     */
    public function forBusiness(string $businessId): array;

    /**
     * Records a newly triggered requirement; false when it was already in force.
     *
     * @param  Code  $code
     * @param  Status  $status
     */
    public function trigger(string $businessId, string $code, string $status, ?string $dueOn, string $today): bool;

    /**
     * Moves a requirement from one of `$from` to `$to`; false when it holds none of them.
     *
     * @param  Code  $code
     * @param  list<Status>  $from
     * @param  Status  $to
     * @param  array<string, mixed>|null  $confirmation
     */
    public function move(string $businessId, string $code, array $from, string $to, ?string $dueOn, ?string $metOn = null, ?array $confirmation = null): bool;

    /**
     * Whether any requirement of the Business is Overdue on the Kigali day `$today` (REP-R02,
     * RDY-R05): recorded Overdue, or Due or Lapsed with its grace ended that day or before, so it
     * agrees with the page before the hourly sweep records the move.
     */
    public function hasOverdue(string $businessId, string $today): bool;
}
