<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Party;
use App\Models\StatementEvidence;
use App\Models\StatementOriginal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<StatementOriginal> */
class StatementOriginalFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $content = "date,amount\n2026-08-01,100\n";

        return ['statement_evidence_id' => StatementEvidence::factory(), 'evidence_revision' => 1,
            'filename' => 'synthetic-statement.csv', 'media_type' => 'text/csv', 'size_bytes' => strlen($content),
            'sha256' => hash('sha256', $content), 'content' => $content,
            'actor_user_id' => User::factory(), 'actor_party_id' => Party::factory()];
    }
}
