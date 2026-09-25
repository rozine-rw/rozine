<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AuditSigningKey;
use Illuminate\Database\Eloquent\Factories\Factory;
use Jose\Component\KeyManagement\JWKFactory;

/** @extends Factory<AuditSigningKey> */
class AuditSigningKeyFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $key = JWKFactory::createECKey('P-256', ['use' => 'sig', 'alg' => 'ES256']);

        return ['private_jwk' => $key->all(), 'public_jwk' => $key->toPublic()->all(),
            'valid_from' => now('UTC')->subDay(), 'rotate_at' => now('UTC')->addDays(89)];
    }
}
