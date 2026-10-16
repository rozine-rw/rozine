<?php

declare(strict_types=1);

use App\Application\Auditor\Contracts\AuditReportCryptography;
use App\Domain\Operations\CommandRejection;
use App\Models\AuditSigningKey;
use App\Models\AuditSigningKeyRevocation;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

it('authenticates the exact canonical payload and refuses altered or malformed signatures', function (): void {
    $key = AuditSigningKey::factory()->create();
    $cryptography = app(AuditReportCryptography::class);
    $payload = ['sealed_at' => now('UTC')->format('Y-m-d\TH:i:s\Z'), 'synthetic' => true, 'value' => '38000000'];
    $signed = $cryptography->sign($payload);
    expect($signed['key_id'])->toBe($key->id)->and($cryptography->verify($key->id, $signed['jws'], $payload))->toBeTrue()
        ->and($cryptography->verify($key->id, $signed['jws'], [...$payload, 'value' => '38000001']))->toBeFalse()
        ->and($cryptography->verify($key->id, 'malformed.signature', $payload))->toBeFalse()
        ->and($cryptography->verify((string) Str::ulid(), $signed['jws'], $payload))->toBeFalse();
    AuditSigningKeyRevocation::factory()->create(['audit_signing_key_id' => $key->id]);
    expect($cryptography->available())->toBeFalse()->and($cryptography->verify($key->id, $signed['jws'], $payload))->toBeFalse()
        ->and($cryptography->verify($key->id, $signed['jws'], $payload, false))->toBeTrue();
});

it('rejects unavailable expired or inconsistent signing keys without exposing key material', function (string $case): void {
    if ($case === 'expired') {
        AuditSigningKey::factory()->create(['valid_from' => now()->subDays(2), 'rotate_at' => now()->subDay()]);
    } elseif ($case === 'mismatch') {
        $other = AuditSigningKey::factory()->make();
        AuditSigningKey::factory()->create(['public_jwk' => $other->public_jwk]);
    }
    expect(fn () => app(AuditReportCryptography::class)->sign(['sealed_at' => now('UTC')->format('Y-m-d\TH:i:s\Z')]))
        ->toThrow(CommandRejection::class, 'SEAL_KEY_UNAVAILABLE');
})->with(['missing', 'expired', 'mismatch']);

it('does not activate isolated signing or public verification in production', function (): void {
    $key = AuditSigningKey::factory()->create();
    $cryptography = app(AuditReportCryptography::class);
    $payload = ['sealed_at' => now('UTC')->format('Y-m-d\TH:i:s\Z')];
    $signed = $cryptography->sign($payload);
    app()->detectEnvironment(fn (): string => 'production');
    try {
        expect($cryptography->available())->toBeFalse()->and($cryptography->verify($key->id, $signed['jws'], $payload))->toBeFalse();
        expect(fn () => $cryptography->sign($payload))->toThrow(CommandRejection::class, 'SEAL_KEY_UNAVAILABLE');
    } finally {
        app()->detectEnvironment(fn (): string => 'testing');
    }
});

it('retains rotated keys for old signatures and prevents changing key history or exceeding ninety days', function (): void {
    $key = AuditSigningKey::factory()->create();
    $cryptography = app(AuditReportCryptography::class);
    $payload = ['sealed_at' => now('UTC')->format('Y-m-d\TH:i:s\Z')];
    $old = $cryptography->sign($payload);
    $new = AuditSigningKey::factory()->create(['valid_from' => now()]);
    expect($cryptography->sign($payload)['key_id'])->toBe($new->id)
        ->and($cryptography->verify($key->id, $old['jws'], $payload))->toBeTrue()
        ->and($key->toArray())->not->toHaveKey('private_jwk');
    expect(fn () => DB::transaction(fn () => $key->forceFill(['rotate_at' => now()->addDay()])->save()))->toThrow(QueryException::class)
        ->and(fn () => DB::transaction(fn () => $key->delete()))->toThrow(QueryException::class)
        ->and(fn () => DB::transaction(fn () => AuditSigningKey::factory()->create(['valid_from' => now(), 'rotate_at' => now()->addDays(91)])))->toThrow(QueryException::class);
});
