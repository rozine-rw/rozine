<?php

declare(strict_types=1);

namespace App\Application\Business\Contracts;

/**
 * Private current declared connections, not a complete independence attestation.
 *
 * @phpstan-type DeclaredConnections array{scope: 'declared-business-connections-v1', business_id: string, entity_party_id: string, mandate_id: string, mandate_version: int, mandate_sha256: string, checked_at: string, party_ids: list<string>, complete: false}
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
     * A Party absent from this list is not thereby unconnected; broader connection
     * evidence and staff-person resolution remain separate required sources.
     *
     * @return DeclaredConnections
     */
    public function lockDeclared(string $businessId): array;
}
