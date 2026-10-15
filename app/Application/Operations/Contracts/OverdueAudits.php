<?php

declare(strict_types=1);

namespace App\Application\Operations\Contracts;

use DateTimeInterface;

/**
 * Every Flash Audit still unfinished past its 24-hour clock, for the Admin Exceptions (BRS AUD-R07:
 * an unfinished Flash Audit at 24 hours raises an alert). Reads only and never locks; the caller
 * checks the staff permission.
 *
 * An audit is unfinished while its assignment is offered, accepted or with Audit Operations and no
 * report for it is sealed. Each one names the business, the partner it sits with (null when none
 * has accepted it) and when its clock ran out.
 *
 * @phpstan-type Overdue array{
 *     id: string, business_id: string, business: string, partner: string|null, status: 'offered'|'accepted'|'operations', due_at: string
 * }
 */
interface OverdueAudits
{
    /**
     * The Flash Audits whose clock ran out before the given instant, longest overdue first.
     *
     * @return list<Overdue>
     */
    public function flashOverdue(DateTimeInterface $at): array;
}
