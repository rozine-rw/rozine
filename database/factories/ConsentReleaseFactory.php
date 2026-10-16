<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Identity\ConsentDocuments;
use App\Models\ConsentRelease;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ConsentRelease> */
class ConsentReleaseFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $documents = new ConsentDocuments;

        return ['revision' => 1, 'status' => 'active', 'synthetic' => true,
            'documents' => $documents->documents([
                ['kind' => 'terms', 'version' => 'synthetic-1', 'body' => 'Synthetic terms. Test use only.',
                    'summary' => [['heading' => 'Test terms', 'body' => 'Not approved legal terms.']]],
                ['kind' => 'privacy', 'version' => 'synthetic-1', 'body' => 'Synthetic privacy text. Test use only.',
                    'summary' => [['heading' => 'Test privacy', 'body' => 'Not an approved privacy notice.']]],
            ]),
            'disclosures' => $documents->disclosures([
                ['key' => 'test-disclosure', 'version' => 'synthetic-1', 'text' => 'Synthetic disclosure. Not approved for participants.'],
            ]),
            'approval_reference' => 'fixture:not-legal-approval', 'reason' => 'Isolated consent test.',
            'actor_user_id' => 1, 'policy_version' => 'engineering-2026-09-23.4'];
    }
}
