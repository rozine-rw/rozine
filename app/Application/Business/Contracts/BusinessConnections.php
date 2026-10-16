<?php

declare(strict_types=1);

namespace App\Application\Business\Contracts;

/**
 * Private current declared connections: the Business Party and every person on its current mandate.
 * By owner decision (Erastus, 16 Oct 2026, "Mandate check is enough") this declared set is the
 * complete connection assessment for MVP Investor purchase admission, and `complete` says so.
 * Admission refuses whenever a source reports `complete: false`.
 *
 * @phpstan-type DeclaredConnections array{scope: 'declared-business-connections-v1', business_id: string, entity_party_id: string, mandate_id: string, mandate_version: int, mandate_sha256: string, checked_at: string, party_ids: list<string>, complete: bool}
 */
interface BusinessConnections
{
    /**
     * Requires the caller transaction and acquires only the Business gate. The caller
     * must acquire Business before any User/Party, Primary, disbursement or wallet lock.
     * Reentrant calls under that retained gate add no other row locks or writes.
     * Returns the canonical Business Party and the people in its current active,
     * effective and normalized mandate. Missing or inactive evidence refuses.
     * The digest binds the normalized mandate to its Business, entity and version.
     * For Investor purchase admission a Party absent from a complete list is unconnected;
     * staff-person resolution remains a separate required source for disbursement.
     *
     * @return DeclaredConnections
     */
    public function lockDeclared(string $businessId): array;
}
