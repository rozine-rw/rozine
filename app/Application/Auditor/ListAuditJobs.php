<?php

declare(strict_types=1);

namespace App\Application\Auditor;

use App\Application\Auditor\Contracts\AuditAssignmentStore;
use App\Application\Business\GetAuditApplication;
use App\Application\Identity\AuthorizeActiveRole;
use App\Domain\Identity\IdentityViolation;
use App\Domain\Operations\CommandRejection;

/** @phpstan-import-type AuditApplication from \App\Application\Business\Contracts\BusinessApplicationStore */
final class ListAuditJobs
{
    public function __construct(private AuditAssignmentStore $assignments, private GetAuditApplication $applications, private AuthorizeActiveRole $roles) {}

    /** @return array{data: list<AuditApplication>, next_cursor: string|null} */
    public function handle(int $userId, int $contextRevision, ?string $before = null, int $limit = 25): array
    {
        $page = $this->assignments->workIdentifiers($userId, $contextRevision, $before, $limit);
        $jobs = [];
        foreach ($page['ids'] as $id) {
            try {
                $jobs[] = $this->applications->handle($userId, $contextRevision, $id);
            } catch (IdentityViolation) {
                continue;
            } catch (CommandRejection $exception) {
                if (! in_array($exception->reason, ['BUSINESS_NOT_FOUND', 'MANDATE_REQUIRED', 'ASSIGNMENT_NOT_FOUND', 'ASSIGNMENT_ACCEPTANCE_EXPIRED',
                    'AUDITOR_INDEPENDENCE_REVIEW_REQUIRED', 'ACCREDITATION_REQUIRED', 'ACCREDITATION_EXPIRED', 'ACCREDITATION_SUSPENDED', 'STANDING_CHECK_REQUIRED'], true)) {
                    throw $exception;
                }
            }
        }

        return $this->roles->handle($userId, 'auditor', $page['party_id'], $contextRevision,
            fn (): array => ['data' => $jobs, 'next_cursor' => $page['next_cursor']]);
    }
}
