<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AuditReport;
use App\Models\AuditStepUpProof;
use App\Models\Party;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<AuditStepUpProof> */
class AuditStepUpProofFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['purpose' => 'audit.seal', 'audit_report_id' => AuditReport::factory(), 'report_revision' => 1,
            'actor_party_id' => Party::factory(), 'actor_user_id' => User::factory(), 'identity_context_revision' => 1,
            'digest' => hash('sha256', 'synthetic-report'), 'credential_binding' => hash('sha256', 'synthetic-credentials'),
            'proof_sha256' => hash('sha256', Str::random(64)), 'created_at' => now('UTC'), 'expires_at' => now('UTC')->addMinutes(5)];
    }

    public function forReport(AuditReport $report, User $user): static
    {
        return $this->state(['audit_report_id' => $report->id, 'report_revision' => $report->revision,
            'actor_party_id' => $report->author_party_id, 'actor_user_id' => $user->id, 'identity_context_revision' => $user->context_revision]);
    }
}
