<?php

declare(strict_types=1);

namespace App\Application\Business\Contracts;

/**
 * The unfunded raises of a partner's audited applications, as their Audit Partner's Portfolio lists
 * them: live while the raise takes commitments, failed once it closed without funding. A funded
 * raise is left out. A plain read; the caller holds the partner's current Auditor authority.
 *
 * @phpstan-type Raise array{campaign_id: string, business: string, title: string, status: 'live'|'failed', raised: string, target: string, investors: int}
 */
interface AuditedRaises
{
    /**
     * The unfunded raises of these applications, newest first, at most `$limit` of them, with what
     * investors have committed so far (or had committed before a failed raise refunded them).
     *
     * @param  list<string>  $applicationIds
     * @return list<Raise>
     */
    public function unfunded(array $applicationIds, int $limit): array;
}
