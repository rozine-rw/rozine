<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\StatementExtraction;
use App\Models\StatementOriginal;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<StatementExtraction> */
class StatementExtractionFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['statement_original_id' => StatementOriginal::factory(), 'revision' => 1,
            'parser_version' => 'synthetic-extraction-1', 'status' => 'needs_review',
            'reason_codes' => ['SYNTHETIC_FIXTURE'], 'text' => null, 'record_count' => null];
    }
}
