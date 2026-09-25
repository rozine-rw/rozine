<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Application\Operations\Contracts\CanonicalJson;
use App\Models\AuditReportPublication;
use App\Models\AuditReportSignature;
use App\Models\Party;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<AuditReportSignature> */
class AuditReportSignatureFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['audit_report_publication_id' => (string) Str::ulid(), 'publication_revision' => 2,
            'actor_party_id' => Party::factory(), 'actor_user_id' => User::factory(),
            'payload' => ['synthetic' => true], 'sha256' => hash('sha256', '{"synthetic":true}'), 'created_at' => now()];
    }

    public function forPublication(AuditReportPublication $publication, User $user): static
    {
        $payload = ['report_id' => $publication->audit_report_id, 'report_revision' => $publication->report_revision,
            'digest' => $publication->digest, 'mandate_version' => $publication->mandate_version,
            'actor_party_id' => $user->party_id, 'actor_user_id' => $user->id, 'accepted' => true, 'note' => 'Synthetic retained signature.',
            'signed_at' => now('UTC')->format('Y-m-d\TH:i:s\Z')];

        return $this->state(fn (): array => ['audit_report_publication_id' => $publication->id, 'publication_revision' => $publication->revision + 1,
            'actor_party_id' => $user->party_id, 'actor_user_id' => $user->id, 'payload' => $payload,
            'sha256' => hash('sha256', app(CanonicalJson::class)->encode($payload))]);
    }
}
