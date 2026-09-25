<?php

declare(strict_types=1);

namespace App\Infrastructure\Auditor;

use App\Application\Auditor\Contracts\AuditStepUp;
use App\Application\Identity\VerifyAuthenticator;
use App\Domain\Operations\CommandRejection;
use App\Models\AuditStepUpProof;
use Illuminate\Support\Str;

final class EloquentAuditStepUp implements AuditStepUp
{
    public function __construct(private VerifyAuthenticator $authenticator) {}

    /** @return array{proof: string, expires_at: string} */
    public function issue(int $userId, string $partyId, int $contextRevision, string $reportId, int $reportRevision, string $digest, string $code): array
    {
        $credentialBinding = $this->authenticator->handle($userId, $code);
        $proof = Str::random(64);
        $expires = now('UTC')->addMinutes(5);
        (new AuditStepUpProof)->forceFill(['actor_user_id' => $userId, 'actor_party_id' => $partyId,
            'identity_context_revision' => $contextRevision, 'audit_report_id' => $reportId, 'report_revision' => $reportRevision,
            'digest' => $digest, 'credential_binding' => $credentialBinding, 'proof_sha256' => hash('sha256', $proof),
            'expires_at' => $expires])->save();

        return ['proof' => $proof, 'expires_at' => $expires->toIso8601String()];
    }

    public function consume(int $userId, string $partyId, int $contextRevision, string $reportId, int $reportRevision, string $digest, string $proof): void
    {
        $record = AuditStepUpProof::query()->where('proof_sha256', hash('sha256', $proof))->lockForUpdate()->first();
        if ($record === null || $record->consumed_at !== null || $record->actor_user_id !== $userId
            || $record->actor_party_id !== $partyId || $record->identity_context_revision !== $contextRevision
            || $record->audit_report_id !== $reportId || $record->report_revision !== $reportRevision
            || ! hash_equals($record->digest, $digest) || ! hash_equals($record->credential_binding, $this->authenticator->binding($userId))) {
            throw new CommandRejection('STEP_UP_INVALID', 403);
        }
        if ($record->expires_at->lessThanOrEqualTo(now('UTC'))) {
            throw new CommandRejection('STEP_UP_EXPIRED', 403);
        }
        $record->forceFill(['consumed_at' => now('UTC')])->save();
    }
}
