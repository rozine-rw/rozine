<?php

declare(strict_types=1);

namespace App\Infrastructure\Evidence;

use App\Application\Evidence\Contracts\StatementReviews;
use App\Domain\Evidence\StatementAuditReview;
use App\Domain\Evidence\StatementReconciliation;
use App\Models\StatementEvidence;
use App\Models\StatementVerification;

/**
 * @phpstan-import-type LatestVerification from StatementReviews
 */
final class EloquentStatementReviews implements StatementReviews
{
    /** @return LatestVerification|null */
    public function latestVerification(string $businessId): ?array
    {
        $evidence = StatementEvidence::query()->where('business_id', $businessId)->first();
        $latest = $evidence === null ? null : StatementVerification::query()->where('statement_evidence_id', $evidence->id)->orderByDesc('revision')->first();
        if ($evidence === null || $latest === null) {
            return null;
        }

        return ['assignment_id' => $latest->assignment_id, 'actor_party_id' => $latest->actor_party_id, 'assignment' => $latest->payload['assignment'],
            'current' => $latest->source_revision === $evidence->revision && $latest->policy_version === StatementAuditReview::POLICY_VERSION
                && $latest->procedure_version === StatementAuditReview::PROCEDURE && $latest->payload['classification_version'] === StatementReconciliation::VERSION];
    }
}
