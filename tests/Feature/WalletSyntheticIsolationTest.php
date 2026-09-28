<?php

declare(strict_types=1);

use App\Application\Environment\EnvironmentIsolation;
use App\Application\Wallet\Contracts\DepositProvider;
use App\Application\Wallet\Contracts\SyntheticEventSigner;
use App\Application\Wallet\DepositInstruction;
use App\Application\Wallet\SyntheticWalletGuard;
use App\Domain\Operations\CommandRejection;

/**
 * @param  array<string, string>  $overrides
 * @return array<string, string>
 */
function syntheticFields(array $overrides = []): array
{
    return [...['provider' => 'synthetic', 'event_id' => 'synthetic-event-1', 'reference' => 'syn_abc123', 'state' => 'succeeded',
        'amount' => '50000', 'currency' => 'RWF', 'environment' => 'testing', 'observed_at' => '2026-09-28T10:00:00+00:00'], ...$overrides];
}

function syntheticInstruction(): DepositInstruction
{
    return new DepositInstruction('01m3ktaq30xydwy23eqgapdtv6', '01m3ktaq30xydwy23eqgapdtv7', 'syn_abc123', '50000', 'RWF', 'testing');
}

it('binds the synthetic provider on testing with live money off', function (): void {
    $provider = app(DepositProvider::class);
    $message = app(SyntheticEventSigner::class)->sign([...syntheticFields(), 'ignored' => 'dropped']);
    $event = $provider->verify($message);
    expect($provider->name())->toBe('synthetic')->and($provider->idempotentSends())->toBeTrue()
        ->and($provider->initiate(syntheticInstruction()))->toBeTrue()
        ->and(array_keys($message))->toBe(['amount', 'currency', 'environment', 'event_id', 'observed_at', 'provider', 'reference', 'state', 'signature'])
        ->and([$event->provider, $event->eventId, $event->providerReference, $event->state, $event->amount, $event->currency, $event->environment, $event->observedAt])
        ->toBe(['synthetic', 'synthetic-event-1', 'syn_abc123', 'succeeded', '50000', 'RWF', 'testing', '2026-09-28T10:00:00+00:00'])
        ->and($event->contentSha256)->toBe($provider->verify(app(SyntheticEventSigner::class)->sign(syntheticFields()))->contentSha256)
        ->and($event->contentSha256)->not->toBe($provider->verify(app(SyntheticEventSigner::class)->sign(syntheticFields(['amount' => '1'])))->contentSha256)
        ->and($event->evidence)->toBe($message);
});

it('refuses unauthenticated or altered messages without recording them', function (Closure $tamper): void {
    $message = $tamper(app(SyntheticEventSigner::class)->sign(syntheticFields()));
    expect(fn () => app(DepositProvider::class)->verify($message))->toThrow(CommandRejection::class, 'PROVIDER_EVENT_UNVERIFIED');
})->with([
    'altered amount' => [fn (array $message): array => [...$message, 'amount' => '500000']],
    'missing signature' => [fn (array $message): array => array_diff_key($message, ['signature' => true])],
    'extra field' => [fn (array $message): array => [...$message, 'note' => 'x']],
    'missing field' => [fn (array $message): array => array_diff_key($message, ['state' => true])],
    'non-string field' => [fn (array $message): array => [...$message, 'amount' => 50000]],
    'another key' => [fn (array $message): array => [...$message, 'signature' => hash_hmac('sha256', 'x', 'y')]],
]);

it('refuses a signed message whose facts are malformed', function (array $overrides): void {
    $message = app(SyntheticEventSigner::class)->sign(syntheticFields($overrides));
    expect(fn () => app(DepositProvider::class)->verify($message))->toThrow(CommandRejection::class, 'PROVIDER_EVENT_MALFORMED');
})->with([
    'provider' => [['provider' => 'mtn']], 'reference' => [['reference' => 'syn abc']], 'event id' => [['event_id' => '']],
    'state' => [['state' => 'redirected']], 'amount' => [['amount' => '50000.00']], 'currency' => [['currency' => 'rwf']],
    'environment' => [['environment' => 'Testing']], 'timestamp' => [['observed_at' => '2026-09-28 10:00']], 'impossible date' => [['observed_at' => '2026-02-30T10:00:00+00:00']],
]);

it('binds nothing that can send, verify or sign where synthetic support is not allowed', function (Closure $environment): void {
    $environment();
    $provider = app(DepositProvider::class);
    expect(app(SyntheticWalletGuard::class)->allowed())->toBeFalse()
        ->and($provider->name())->toBe('unavailable')->and($provider->idempotentSends())->toBeFalse()
        ->and(fn () => $provider->initiate(syntheticInstruction()))->toThrow(LogicException::class, 'WALLET_PROVIDER_UNAVAILABLE')
        ->and(fn () => $provider->verify(syntheticFields()))->toThrow(CommandRejection::class, 'PROVIDER_EVENT_UNVERIFIED')
        ->and(fn () => app(SyntheticEventSigner::class)->sign(syntheticFields()))->toThrow(LogicException::class, 'WALLET_PROVIDER_UNAVAILABLE')
        ->and(fn () => app(SyntheticWalletGuard::class)->assertAllowed())->toThrow(LogicException::class, 'WALLET_SYNTHETIC_ONLY');
})->with([
    'live money on' => [fn () => config(['isolation.live_money_enabled' => true])],
    'uat profile' => [fn () => app()->instance(EnvironmentIsolation::class, Mockery::mock(EnvironmentIsolation::class, ['profile' => 'uat']))],
    'demo profile' => [fn () => app()->instance(EnvironmentIsolation::class, Mockery::mock(EnvironmentIsolation::class, ['profile' => 'demo']))],
    'production profile' => [fn () => app()->instance(EnvironmentIsolation::class, Mockery::mock(EnvironmentIsolation::class, ['profile' => 'production']))],
]);

it('stops an already-resolved synthetic provider once the guard closes', function (): void {
    $provider = app(DepositProvider::class);
    $signer = app(SyntheticEventSigner::class);
    $message = $signer->sign(syntheticFields());
    config(['isolation.live_money_enabled' => true]);
    expect(fn () => $provider->initiate(syntheticInstruction()))->toThrow(LogicException::class, 'WALLET_SYNTHETIC_ONLY')
        ->and(fn () => $provider->verify($message))->toThrow(LogicException::class, 'WALLET_SYNTHETIC_ONLY')
        ->and(fn () => $signer->sign(syntheticFields()))->toThrow(LogicException::class, 'WALLET_SYNTHETIC_ONLY');
});
