<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Application\Operations\Contracts\CanonicalJson;
use App\Models\AuditAssignment;
use App\Models\Party;
use App\Models\StatementEvidence;
use App\Models\StatementTranscription;
use App\Models\StatementVerification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<StatementVerification> */
class StatementVerificationFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['statement_evidence_id' => StatementEvidence::factory(),
            'transcription_id' => fn (array $attributes): string => StatementTranscription::factory()->create(['statement_evidence_id' => $attributes['statement_evidence_id']])->id,
            'assignment_id' => AuditAssignment::factory(), 'revision' => 1, 'source_revision' => 1, 'amends_id' => null,
            'actor_user_id' => User::factory(), 'actor_party_id' => Party::factory(),
            'policy_version' => 'synthetic-only', 'procedure_version' => 'synthetic-only',
            'payload' => fn (array $attributes): array => ['business_id' => StatementEvidence::query()->whereKey($attributes['statement_evidence_id'])->firstOrFail()->business_id,
                'policy_version' => 'synthetic-only', 'observations' => []],
            'sha256' => fn (array $attributes): string => hash('sha256', app(CanonicalJson::class)->encode($attributes['payload']))];
    }
}
