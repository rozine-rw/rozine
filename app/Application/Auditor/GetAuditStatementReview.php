<?php

declare(strict_types=1);

namespace App\Application\Auditor;

use App\Application\Auditor\Contracts\AuditAssignmentStore;
use App\Application\Evidence\Contracts\StatementStore;
use App\Domain\Auditor\AuditEngagementState;
use App\Domain\Evidence\StatementAuditReview;

/**
 * What an accepted statement review (#396) puts in front of its Audit Partner: the statements and
 * the Business's own figures on file, the revisions a verification binds, and the factual checks the
 * procedure asks for. Any other engagement has none, since its report carries its own procedure.
 *
 * @phpstan-import-type AuditFile from StatementStore
 *
 * @phpstan-type StatementReview array{file: AuditFile, verification_revision: int, procedure_version: string, checks: list<string>}
 */
final class GetAuditStatementReview
{
    public function __construct(private AuditAssignmentStore $assignments, private StatementStore $statements) {}

    /**
     * @param  AuditFile|null  $file
     * @return StatementReview|null
     */
    public function handle(int $userId, int $contextRevision, ?array $file): ?array
    {
        if ($file === null || $this->assignments->purpose($file['assignment']['id']) !== AuditEngagementState::PURPOSE_STATEMENT_REVIEW) {
            return null;
        }
        $latest = $this->statements->auditVerification($userId, $contextRevision, $file['assignment']['id'], null);

        return ['file' => $file, 'verification_revision' => $latest['revision'] ?? 0,
            'procedure_version' => StatementAuditReview::PROCEDURE, 'checks' => StatementAuditReview::CHECKS];
    }
}
