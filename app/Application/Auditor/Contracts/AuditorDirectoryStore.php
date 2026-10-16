<?php

declare(strict_types=1);

namespace App\Application\Auditor\Contracts;

/**
 * The Admin console's Audit Partner network: every Party with an Auditor membership or an
 * accreditation profile, with the standing staff recorded for it. Reads only and never locks; the
 * caller checks the staff permission.
 *
 * Standing is active while the reviewed licence is unexpired, licence expired once it lapses,
 * suspended when staff suspended or revoked it, and pending until staff first approve a licence.
 * Active engagements are the audits the partner has accepted and not yet completed.
 *
 * @phpstan-type Standing 'active'|'pending'|'licence_expired'|'suspended'
 * @phpstan-type DirectoryRow array{
 *     party_id: string, name: string, email: string, licence: string|null, standing: Standing, active_engagements: int
 * }
 * @phpstan-type Counts array{all: int, active: int, pending: int, licence_expired: int, suspended: int}
 * @phpstan-type Directory array{rows: list<DirectoryRow>, matching: int, counts: Counts, licences_expiring: int}
 * @phpstan-type Licence array{
 *     licence: string, expires_on: string, state: 'verified'|'pending'|'expired', revision: int, submission_id: string|null
 * }
 * @phpstan-type Engagement array{id: string, business: string, district: string}
 * @phpstan-type HistoryEntry array{
 *     id: string, at: string, actor: string, reason: string|null,
 *     event: 'submitted'|'renewal_submitted'|'withdrawn'|'availability'|'approved'|'rejected'|'suspended'|'revoked'
 * }
 * @phpstan-type Detail array{row: DirectoryRow, licence: Licence|null, engagements: list<Engagement>, history: list<HistoryEntry>}
 */
interface AuditorDirectoryStore
{
    /**
     * One page of the network under a chip and a name or email search. The figures and chip counts
     * cover every match of the search; licences expiring are active ones lapsing within thirty days.
     *
     * @param  'all'|'active'|'pending'|'licence_expired'  $chip
     * @return Directory
     */
    public function directory(string $chip, string $search, int $limit): array;

    /** @return Detail|null */
    public function partner(string $partyId): ?array;
}
