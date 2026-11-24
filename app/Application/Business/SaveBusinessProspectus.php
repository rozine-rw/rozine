<?php

declare(strict_types=1);

namespace App\Application\Business;

use App\Application\Business\Contracts\BusinessProspectuses;
use App\Domain\Operations\CommandRejection;

/**
 * `prospectus.save`: saves the Business's prospectus draft as its next version under
 * `application.save`, once per request id. A draft may still fail its gates; a refusal (malformed
 * input, a stale version, a signed prospectus) comes back as a rejected result.
 */
final class SaveBusinessProspectus
{
    public function __construct(private BusinessProspectuses $prospectuses) {}

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function handle(int $userId, int $contextRevision, string $businessId, string $applicationId, int $expectedVersion, array $input, string $requestId): array
    {
        try {
            return $this->prospectuses->save($userId, $contextRevision, $businessId, $applicationId, $expectedVersion, $input, $requestId);
        } catch (CommandRejection $rejection) {
            return ['status' => 'rejected', 'code' => $rejection->reason, 'field_errors' => $rejection->fieldErrors, 'data' => $rejection->data];
        }
    }
}
