<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Application\Identity\Contracts\IdentityAccessStore;
use App\Domain\Identity\IdentityViolation;

/**
 * Staff grant the Auditor access a verified person asked for from the launcher (#616). The person
 * then opens the Auditor app and uploads their ICPAR certificate, which staff verify on the Auditor
 * Network's desk before they take audits.
 */
final readonly class GrantRequestedAuditorAccess
{
    public function __construct(private IdentityAccessStore $access) {}

    /**
     * The grant's outcome code: MEMBERSHIP_UPDATED, MEMBERSHIP_UNCHANGED, or the identity refusal.
     */
    public function handle(int $actorId, string $accessRequestId, string $requestId): string
    {
        try {
            return $this->access->grantRequestedAuditorAccess($actorId, $accessRequestId,
                'Auditor access granted from the launcher request; the ICPAR certificate is verified on the Auditor Network desk.', $requestId)['code'];
        } catch (IdentityViolation $violation) {
            return $violation->reason;
        }
    }
}
