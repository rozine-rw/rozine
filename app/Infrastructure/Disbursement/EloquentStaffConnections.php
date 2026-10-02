<?php

declare(strict_types=1);

namespace App\Infrastructure\Disbursement;

use App\Application\Business\Contracts\BusinessConnections;
use App\Application\Disbursement\Contracts\StaffConnections;
use App\Application\Identity\Contracts\IdentityAccessStore;
use App\Domain\Disbursement\StaffIndependence;
use App\Domain\Operations\CommandRejection;
use App\Models\DisbursementIndependenceDeclaration;

/**
 * The real staff-person connection source (#96 5956110941, owner decision 5956161592).
 *
 * - `unavailable`: no current verified person for the staff account, no current declared Business
 *   connections, or no signed independence declaration for this disbursement.
 * - `connected`: the staff member's own Party is the Business entity, a person in its current
 *   mandate, or a committed Investor of the campaign. A visible connection always wins.
 * - `unconnected`: only with a current verified person, no visible connection and the staff
 *   member's own declaration of the current statement for this disbursement.
 *
 * Called from the disbursement adapter after the Business and campaign locks and before any
 * disbursement lock. `lockDeclared` re-enters the Business gate the caller already holds; the staff
 * identity and declaration reads take no row locks.
 */
final readonly class EloquentStaffConnections implements StaffConnections
{
    public function __construct(private IdentityAccessStore $identities, private BusinessConnections $businesses) {}

    public function connection(int $staffUserId, string $disbursementId, string $businessId, array $partyIds): string
    {
        $person = $this->identities->staffPerson($staffUserId);
        if ($person === null) {
            return 'unavailable';
        }
        try {
            $declared = $this->businesses->lockDeclared($businessId);
        } catch (CommandRejection) {
            return 'unavailable';
        }
        if ($person['party_id'] !== null && in_array($person['party_id'], [$declared['entity_party_id'], ...$declared['party_ids'], ...$partyIds], true)) {
            return 'connected';
        }

        return DisbursementIndependenceDeclaration::query()->where('disbursement_id', $disbursementId)->where('staff_user_id', $staffUserId)
            ->where('statement_sha256', StaffIndependence::statementSha256())->exists() ? 'unconnected' : 'unavailable';
    }
}
