<?php

declare(strict_types=1);

namespace App\Application\Business;

use App\Application\Business\Contracts\BusinessProspectuses;
use App\Domain\Operations\CommandRejection;

/**
 * `prospectus.document.upload`: keeps a supplier quote or invoice, or a signed contract, as evidence
 * the prospectus may cite (BRS v3.0 PRO-R03 P-G05, P-G06), under `application.save`, once per
 * request id. A refusal comes back as a rejected result.
 */
final class UploadProspectusDocument
{
    public function __construct(private BusinessProspectuses $prospectuses) {}

    /** @return array<string, mixed> */
    public function handle(int $userId, int $contextRevision, string $businessId, string $applicationId, string $kind, string $filename, string $content, string $requestId): array
    {
        try {
            return $this->prospectuses->upload($userId, $contextRevision, $businessId, $applicationId, $kind, $filename, $content, $requestId);
        } catch (CommandRejection $rejection) {
            return ['status' => 'rejected', 'code' => $rejection->reason, 'field_errors' => $rejection->fieldErrors, 'data' => $rejection->data];
        }
    }
}
