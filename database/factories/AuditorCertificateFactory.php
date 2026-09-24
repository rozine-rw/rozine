<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Auditor\AccreditationCertificate;
use App\Models\AuditorCertificate;
use App\Models\AuditorProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AuditorCertificate> */
class AuditorCertificateFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $content = "%PDF-1.7\nSynthetic certificate fixture\n%%EOF";

        return [...(new AccreditationCertificate)->describe('synthetic-certificate.pdf', $content), 'content' => $content,
            'auditor_profile_id' => AuditorProfile::factory(), 'actor_user_id' => User::factory()];
    }
}
