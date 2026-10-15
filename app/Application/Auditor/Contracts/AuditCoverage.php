<?php

declare(strict_types=1);

namespace App\Application\Auditor\Contracts;

use DateTimeImmutable;

/**
 * Audit Partner coverage by the districts Businesses operate in (MVP-ADMIN-SCR-07). Reads only;
 * the caller checks the staff permission.
 *
 * A partner reaches a Business when dispatch would accept them on everything that does not depend
 * on that Business (BRS AUD-R01, AUD-R02): current standing, accepting work, accepted engagement
 * terms, open capacity, and a verified office within 30 km of the Business's verified premises.
 * Conflicts and rotation are judged per assignment, so they are not counted here. A district is
 * covered when every Business in it is reached by at least one partner.
 *
 * @phpstan-type District array{district: string, province: string, businesses: int, active_partners: int, audits_open: int, covered: bool}
 */
interface AuditCoverage
{
    /**
     * Every district with at least one Business, in the order of `config/rwanda.php`.
     *
     * @return list<District>
     */
    public function districts(DateTimeImmutable $now): array;
}
