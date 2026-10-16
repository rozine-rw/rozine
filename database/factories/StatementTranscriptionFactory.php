<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Application\Operations\Contracts\CanonicalJson;
use App\Models\Party;
use App\Models\StatementEvidence;
use App\Models\StatementTranscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<StatementTranscription> */
class StatementTranscriptionFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['statement_evidence_id' => StatementEvidence::factory(), 'evidence_revision' => 1,
            'amends_id' => null, 'classification_version' => 'synthetic-transcription-1',
            'actor_user_id' => User::factory(), 'actor_party_id' => Party::factory(),
            'payload' => fn (array $attributes): array => ['business_id' => StatementEvidence::query()->where('id', $attributes['statement_evidence_id'])->firstOrFail()->business_id,
                'source_revision' => 0, 'classification_version' => 'synthetic-transcription-1', 'rails' => [], 'months' => [],
                'statements' => [], 'source_hashes' => [], 'observations' => []],
            'sha256' => fn (array $attributes): string => hash('sha256', app(CanonicalJson::class)->encode($attributes['payload']))];
    }
}
