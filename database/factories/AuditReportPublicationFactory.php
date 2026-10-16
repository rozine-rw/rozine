<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AuditReportPublication;
use App\Models\AuditReportSeal;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<AuditReportPublication> */
class AuditReportPublicationFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['audit_report_id' => (string) Str::ulid(), 'business_id' => (string) Str::ulid(), 'mandate_version' => 1,
            'report_revision' => 2, 'digest' => hash('sha256', 'synthetic sealed report'), 'revision' => 1, 'status' => 'pending', 'published_at' => null];
    }

    /** A retained real seal is required by the publication insertion guard. */
    public function forSeal(AuditReportSeal $seal): static
    {
        return $this->state(fn (): array => ['audit_report_id' => $seal->audit_report_id, 'business_id' => $seal->payload['business']['id'],
            'mandate_version' => $seal->payload['business']['mandate_version'], 'report_revision' => $seal->report_revision, 'digest' => $seal->digest]);
    }
}
