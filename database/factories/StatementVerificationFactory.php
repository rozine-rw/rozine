<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Application\Operations\Contracts\CanonicalJson;
use App\Domain\Evidence\StatementAuditReview;
use App\Models\AuditAssignment;
use App\Models\Party;
use App\Models\StatementEvidence;
use App\Models\StatementTranscription;
use App\Models\StatementVerification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StatementVerification>
 *
 * @phpstan-import-type VerificationPayload from \App\Application\Evidence\Contracts\StatementStore
 */
class StatementVerificationFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['actor_party_id' => Party::factory(),
            'actor_user_id' => fn (array $attributes): int => User::factory()->create(['party_id' => $attributes['actor_party_id']])->id,
            'statement_evidence_id' => StatementEvidence::factory(),
            'transcription_id' => fn (array $attributes): string => StatementTranscription::factory()->create(['statement_evidence_id' => $attributes['statement_evidence_id']])->id,
            'assignment_id' => fn (array $attributes): string => AuditAssignment::factory()->create([
                'business_id' => StatementEvidence::query()->whereKey($attributes['statement_evidence_id'])->firstOrFail()->business_id])->id,
            'revision' => 1, 'source_revision' => 1, 'amends_id' => null,
            'policy_version' => 'synthetic-only', 'procedure_version' => 'synthetic-only',
            'payload' => fn (array $attributes): array => $this->payload($attributes),
            'sha256' => fn (array $attributes): string => hash('sha256', app(CanonicalJson::class)->encode($attributes['payload']))];
    }

    /**
     * Complete historical shape with an unsupported synthetic policy; never grants current authority.
     *
     * @param  array<string, mixed>  $attributes
     * @return VerificationPayload
     */
    private function payload(array $attributes): array
    {
        $transcription = StatementTranscription::query()->whereKey($attributes['transcription_id'])->firstOrFail();
        $assignment = AuditAssignment::query()->whereKey($attributes['assignment_id'])->firstOrFail();

        return ['business_id' => $assignment->business_id, 'assignment' => ['id' => $assignment->id, 'business_id' => $assignment->business_id,
            'party_id' => $attributes['actor_party_id'], 'revision' => $assignment->revision, 'kind' => $assignment->state['kind'],
            'business_revision' => 0, 'mandate_version' => 0, 'mandate_sha256' => hash('sha256', '{}'),
            'independence' => ['id' => 'synthetic-only', 'revision' => 0, 'checked_at' => now('UTC')->format('Y-m-d\TH:i:s\Z'),
                'evidence_reference' => 'synthetic:needs-review', 'sha256' => hash('sha256', '{}')],
            'accreditation' => ['profile_revision' => 0, 'status' => 'unverified', 'licence' => null, 'expires_on' => null, 'checked_at' => null]],
            'source_revision' => $attributes['source_revision'], 'transcription' => ['id' => $transcription->id, 'sha256' => $transcription->sha256],
            'source_hashes' => [], 'source_provenance' => [], 'classification_version' => $transcription->classification_version,
            'policy_version' => $attributes['policy_version'], 'procedure_version' => $attributes['procedure_version'],
            'review' => ['procedure_version' => $attributes['procedure_version'], 'checks' => array_fill_keys(StatementAuditReview::CHECKS, false),
                'source_checks' => [], 'inventory_reference' => 'synthetic:needs-review', 'obligations' => [], 'recurring_owner_draw' => '0',
                'owner_draw_reference' => 'synthetic:needs-review', 'findings' => 'Synthetic unapproved historical fixture.'],
            'verified_at' => now('UTC')->format('Y-m-d\TH:i:s\Z'), 'report_approval' => 'not_cosigned', 'observations' => []];
    }
}
