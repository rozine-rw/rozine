<?php

declare(strict_types=1);

namespace App\Infrastructure\Auditor;

use App\Application\Auditor\Contracts\AuditReportCryptography;
use App\Application\Environment\EnvironmentIsolation;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Domain\Operations\CommandRejection;
use App\Models\AuditSigningKey;
use App\Models\AuditSigningKeyRevocation;
use Carbon\CarbonImmutable;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Core\JWK;
use Jose\Component\Signature\Algorithm\ES256;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Signature\JWSVerifier;
use Jose\Component\Signature\Serializer\CompactSerializer;
use Throwable;

final class JoseAuditReportCryptography implements AuditReportCryptography
{
    public function __construct(private EnvironmentIsolation $isolation, private CanonicalJson $json) {}

    public function available(): bool
    {
        return $this->current() !== null;
    }

    /** @param array<string, mixed> $payload
     * @return array{key_id: string, jws: string}
     */
    public function sign(array $payload): array
    {
        $key = $this->current() ?? throw new CommandRejection('SEAL_KEY_UNAVAILABLE', 503);
        try {
            $jwk = new JWK($key->private_jwk);
            if ($this->json->encode($jwk->toPublic()->all()) !== $this->json->encode($key->public_jwk)) {
                throw new CommandRejection('SEAL_KEY_UNAVAILABLE', 503);
            }
            $jws = (new JWSBuilder(new AlgorithmManager([new ES256])))->create()
                ->withPayload($this->json->encode($payload))
                ->addSignature($jwk, ['alg' => 'ES256', 'kid' => $key->id, 'typ' => 'rozine-audit+jws'])->build();

            return ['key_id' => $key->id, 'jws' => (new CompactSerializer)->serialize($jws, 0)];
        } catch (Throwable) {
            throw new CommandRejection('SEAL_KEY_UNAVAILABLE', 503);
        }
    }

    /** @param array<string, mixed> $payload */
    public function verify(string $keyId, string $jws, array $payload, bool $current = true): bool
    {
        if (! $this->isolation->canSeed()) {
            return false;
        }
        $key = AuditSigningKey::query()->whereKey($keyId)->sharedLock()->first();
        if ($key === null || ($current && AuditSigningKeyRevocation::query()->where('audit_signing_key_id', $keyId)->exists())) {
            return false;
        }
        try {
            $signedAt = CarbonImmutable::parse($payload['sealed_at']);
            $decoded = (new CompactSerializer)->unserialize($jws);

            return $signedAt->greaterThanOrEqualTo($key->valid_from) && $signedAt->lessThan($key->rotate_at)
                && $decoded->getSignature(0)->getProtectedHeader() === ['alg' => 'ES256', 'kid' => $keyId, 'typ' => 'rozine-audit+jws']
                && hash_equals($this->json->encode($payload), $decoded->getPayload() ?? '')
                && (new JWSVerifier(new AlgorithmManager([new ES256])))->verifyWithKey($decoded, new JWK($key->public_jwk), 0);
        } catch (Throwable) {
            return false;
        }
    }

    private function current(): ?AuditSigningKey
    {
        if (! $this->isolation->canSeed()) {
            return null;
        }

        $key = AuditSigningKey::query()->where('valid_from', '<=', now('UTC'))->where('rotate_at', '>', now('UTC'))
            ->whereNotIn('id', AuditSigningKeyRevocation::query()->select('audit_signing_key_id'))
            ->orderByDesc('valid_from')->orderByDesc('id')->sharedLock()->first();

        return $key !== null && ! AuditSigningKeyRevocation::query()->where('audit_signing_key_id', $key->id)->exists() ? $key : null;
    }
}
