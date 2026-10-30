<?php

declare(strict_types=1);

namespace App\Application\Auditor;

use App\Domain\Operations\CommandRejection;

/**
 * The audit commands the staff Business drawer sends (#446): a Flash audit request (BR-48), and a
 * redispatch or close of an audit that waits on Audit Operations. A refusal before the command is recorded (a
 * closed last audit, a reused request id) comes back as a rejected result, as a recorded refusal does,
 * so the drawer can show it under the reason.
 */
final class ManageBusinessAudit
{
    public function __construct(private RequestAuditAssignment $requests, private ResolveAuditAssignment $resolutions) {}

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
}
