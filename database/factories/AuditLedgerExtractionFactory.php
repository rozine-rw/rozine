<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AuditLedgerExtraction;
use App\Models\AuditLedgerOriginal;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AuditLedgerExtraction> */
class AuditLedgerExtractionFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['audit_ledger_original_id' => AuditLedgerOriginal::factory(), 'revision' => 1,
            'parser_version' => 'synthetic-extraction-1', 'status' => 'needs_review',
            'reason_codes' => ['SYNTHETIC_FIXTURE'], 'text' => null, 'record_count' => null];
    }
}
