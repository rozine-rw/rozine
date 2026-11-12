<?php

declare(strict_types=1);

namespace App\Application\Business;

use App\Application\Business\Contracts\BusinessProspectuses;
use App\Domain\Operations\CommandRejection;

/**
 * `prospectus.sign`: the official representative signs the latest draft once gates P-G01 to P-G11
 * pass (BRS v3.0 PRO-R03, #524), which completes P-G12 and locks the prospectus under its
 * fingerprint. A refusal, including the gates that still fail, comes back as a rejected result.
 */
final class SignBusinessProspectus
{
    public function __construct(private BusinessProspectuses $prospectuses) {}

    /** @return array<string, mixed> */
    public function handle(int $userId, int $contextRevision, string $businessId, string $applicationId, int $expectedVersion, string $requestId): array
    {
        try {
            return $this->prospectuses->sign($userId, $contextRevision, $businessId, $applicationId, $expectedVersion, $requestId);
        } catch (CommandRejection $rejection) {
            return ['status' => 'rejected', 'code' => $rejection->reason, 'field_errors' => $rejection->fieldErrors, 'data' => $rejection->data];
        }
    }
}
