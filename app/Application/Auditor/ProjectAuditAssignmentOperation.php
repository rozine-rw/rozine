<?php

declare(strict_types=1);

namespace App\Application\Auditor;

use App\Application\Identity\AuthorizeActiveRole;
use App\Domain\Auditor\AuditReadPolicy;
use App\Domain\Identity\IdentityViolation;
use App\Domain\Operations\CommandRejection;

/** Adds current authorized actions and the author's own declaration to a recorded command outcome. */
final class ProjectAuditAssignmentOperation
{
    public function __construct(private GetAuditAssignment $assignments, private GetOwnAuditConflict $conflicts, private AuthorizeActiveRole $identity) {}

    /**
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    public function handle(int $userId, int $revision, array $result): array
    {
        $data = (array) $result['data'];
        $assignmentId = $data['assignment_id'] ?? null;
        $actions = [];
        if (is_string($assignmentId)) {
            try {
                $actions = $this->assignments->handle($userId, $revision, $assignmentId)['allowed_actions'];
            } catch (IdentityViolation) {
                // The final identity check distinguishes a lapsed Business from a revoked actor.
            } catch (CommandRejection $exception) {
                if (! in_array($exception->reason, AuditReadPolicy::DROPPED_RECORD_CODES, true)) {
                    throw $exception;
                }
            }
            if ($result['status'] === 'completed' && $result['code'] === 'CONFLICT_RECORDED') {
                $receipt = $this->conflicts->handle($userId, $revision, $assignmentId)['conflict'];
                $outcome = (array) ($data['outcome'] ?? []);
                $data['conflict'] = [...$receipt, 'status' => $outcome['resolution'] ?? $receipt['status']];
            }
        }
        $this->identity->context($userId, 'auditor', $revision);

        return [...$result, 'data' => $data, 'allowed_actions' => $actions];
    }
}
