<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AuditDisputeProof;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** Synthetic denied-insert data; valid originals are produced by a Business dispute.
 * @extends Factory<AuditDisputeProof>
 */
class AuditDisputeProofFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $content = "%PDF-1.4\nSynthetic proof\n%%EOF";

        return ['audit_report_publication_id' => (string) Str::ulid(), 'audit_publication_event_id' => (string) Str::ulid(),
            'filename' => 'synthetic.pdf', 'content' => $content, 'mime_type' => 'application/pdf',
            'size_bytes' => strlen($content), 'sha256' => hash('sha256', $content), 'created_at' => now()];
    }
}
