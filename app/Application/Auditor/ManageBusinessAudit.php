<?php

declare(strict_types=1);

namespace App\Application\Auditor;

use App\Domain\Operations\CommandRejection;

/**
 * The audit commands the staff Business drawer sends (#446): a Flash audit request (BR-48), a
 * redispatch or close of an audit that waits on Audit Operations, and an independence review of a
 * nearby Audit Partner, which dispatch requires before it offers the partner the Business's audits. A refusal before the command is recorded (a
 * closed last audit, a reused request id) comes back as a rejected result, as a recorded refusal does,
 * so the drawer can show it under the reason.
 */
final class ManageBusinessAudit
{
    public function __construct(private RequestAuditAssignment $requests, private ResolveAuditAssignment $resolutions,
        private RecordAuditorIndependence $independence) {}

    /** @return array<string, mixed> */
    public function requestFlash(int $actorId, string $businessId, string $reason, string $requestId): array
    {
        try {
            return $this->requests->handle($actorId, $businessId, 'flash', $reason, $requestId);
        } catch (CommandRejection $rejection) {
            return ['status' => 'rejected', 'code' => $rejection->reason];
        }
    }

    /** @return array<string, mixed> */
    public function resolve(int $actorId, string $assignmentId, int $expectedRevision, string $decision, string $reason, string $requestId): array
    {
        try {
            return $this->resolutions->handle($actorId, $assignmentId, $expectedRevision, $decision, $reason, $requestId);
        } catch (CommandRejection $rejection) {
            return ['status' => 'rejected', 'code' => $rejection->reason];
        }
    }

    /**
     * @param  array<string, mixed>  $facts
     * @return array<string, mixed>
     */
    public function recordIndependence(int $actorId, string $businessId, string $partyId, int $expectedRevision, string $checkedAt, array $facts, string $reference, string $reason, string $requestId): array
    {
        try {
            return $this->independence->handle($actorId, $businessId, $partyId, $expectedRevision, $facts, $checkedAt, $reference, $reason, $requestId);
        } catch (CommandRejection $rejection) {
            return ['status' => 'rejected', 'code' => $rejection->reason];
        }
    }
}
