<?php

declare(strict_types=1);

namespace App\Infrastructure\Disbursement;

use App\Application\Disbursement\Contracts\PayoutProvider;
use App\Application\Disbursement\Contracts\SyntheticPayoutScripts;
use App\Application\Disbursement\PayoutInstruction;
use App\Application\Disbursement\SyntheticDisbursementGuard;
use App\Application\Disbursement\VerifiedPayoutEvent;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Domain\Operations\CommandRejection;
use App\Models\DisbursementIntent;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Str;
use LogicException;
use RuntimeException;
use Throwable;

/**
 * An offline payout provider for local and testing only. Messages are HMAC-signed over their
 * canonical JSON with a key derived from the application key, so an observation cannot be forged
 * or altered. What a send acknowledges and what a query answers are scripted; it contacts nothing
 * and holds no real account or PSP arrangement. It refuses to be called inside a transaction.
 */
final class SyntheticPayoutProvider implements PayoutProvider, SyntheticPayoutScripts
{
    public const array FIELDS = ['amount', 'currency', 'destination_sha256', 'effective_at', 'environment', 'event_id', 'observed_at',
        'operation_id', 'provider', 'reference', 'state'];

    private const string PREFIX = 'synthetic-payout:';

    public function __construct(private SyntheticDisbursementGuard $guard, private CanonicalJson $json, private Repository $config, private CacheFactory $cache) {}

    public function name(): string
    {
        return 'synthetic';
    }

    public function idempotentSends(): bool
    {
        return $this->sendScript()['idempotent'];
    }

    public function send(PayoutInstruction $instruction): bool
    {
        $this->assertOutsideTransaction();

        return match ($this->sendScript()['answer']) {
            'nack' => false,
            'throw' => throw new RuntimeException('SYNTHETIC_PAYOUT_TIMEOUT'),
            default => true,
        };
    }

    public function query(PayoutInstruction $instruction): ?VerifiedPayoutEvent
    {
        $this->assertOutsideTransaction();
        $script = $this->cache->store()->get(self::PREFIX.'query:'.$instruction->intentId);
        if (! is_array($script)) {
            return null;
        }
        $intent = DisbursementIntent::query()->findOrFail($instruction->intentId);

        return $this->verify($this->sign([...self::observation($intent, $script['state']), ...$script['overrides']]));
    }

    /** @param array<string, mixed> $message */
    public function verify(array $message): VerifiedPayoutEvent
    {
        $this->guard->assertAllowed();
        $fields = array_intersect_key($message, array_flip(self::FIELDS));
        ksort($fields);
        $strings = array_filter($message, is_string(...));
        if (count($fields) !== count(self::FIELDS) || count($message) !== count(self::FIELDS) + 1 || count($strings) !== count($message)
            || ! isset($message['signature']) || ! hash_equals($this->mac($fields), $message['signature'])) {
            throw new CommandRejection('PROVIDER_EVENT_UNVERIFIED', 401);
        }
        if ($fields['provider'] !== $this->name() || preg_match('/^[A-Za-z0-9._:-]{1,120}$/D', $fields['event_id']) !== 1
            || ! in_array($fields['state'], ['pending', 'succeeded', 'failed', 'unknown'], true) || ! $this->timestamp($fields['observed_at'])
            || ($fields['effective_at'] !== '' && ! $this->timestamp($fields['effective_at']))) {
            throw new CommandRejection('PROVIDER_EVENT_MALFORMED', 422);
        }
        $optional = fn (string $value): ?string => $value === '' ? null : $value;

        return new VerifiedPayoutEvent($fields['provider'], $fields['event_id'], $fields['state'], $optional($fields['operation_id']),
            $optional($fields['reference']), $optional($fields['amount']), $optional($fields['currency']), $optional($fields['environment']),
            $optional($fields['destination_sha256']), $fields['observed_at'], $optional($fields['effective_at']),
            hash('sha256', $this->json->encode($fields)), $message);
    }

    public function scriptSend(string $answer, bool $idempotent = false): void
    {
        $this->guard->assertAllowed();
        $this->cache->store()->forever(self::PREFIX.'send', ['answer' => $answer, 'idempotent' => $idempotent]);
    }

    public function scriptQuery(string $intentId, ?string $state, ?array $overrides = null): void
    {
        $this->guard->assertAllowed();
        $this->cache->store()->forever(self::PREFIX.'query:'.$intentId, $state === null ? null : ['state' => $state, 'overrides' => $overrides ?? []]);
    }

    public function callback(string $intentId, string $state, array $overrides = []): array
    {
        $this->guard->assertAllowed();

        return $this->sign([...self::observation(DisbursementIntent::query()->findOrFail($intentId), $state), ...$overrides]);
    }

    /**
     * What an honest provider would authenticate about this intent: its operation, reference,
     * exact amount, currency, destination and environment, and for a final state an effective
     * instant it asserts (never the arrival time).
     *
     * @return array<string, string>
     */
    private static function observation(DisbursementIntent $intent, string $state): array
    {
        $now = CarbonImmutable::now('UTC')->startOfSecond();

        return ['amount' => $intent->amount, 'currency' => $intent->currency, 'destination_sha256' => $intent->destination_sha256,
            'effective_at' => in_array($state, ['succeeded', 'failed'], true) ? $now->subMinute()->toIso8601String() : '',
            'environment' => $intent->environment, 'event_id' => 'evt-'.strtolower((string) Str::ulid()), 'observed_at' => $now->toIso8601String(),
            'operation_id' => $intent->operation_id, 'provider' => 'synthetic', 'reference' => $intent->provider_reference, 'state' => $state];
    }

    /**
     * @param  array<string, string>  $fields
     * @return array<string, string>
     */
    private function sign(array $fields): array
    {
        $this->guard->assertAllowed();
        $message = array_intersect_key($fields, array_flip(self::FIELDS));
        ksort($message);

        return [...$message, 'signature' => $this->mac($message)];
    }

    /** @return array{answer: string, idempotent: bool} */
    private function sendScript(): array
    {
        $this->guard->assertAllowed();

        return $this->cache->store()->get(self::PREFIX.'send', ['answer' => 'ack', 'idempotent' => false]);
    }

    private function assertOutsideTransaction(): void
    {
        $this->guard->assertAllowed();
        if (app('db.transactions')->callbackApplicableTransactions()->isNotEmpty()) {
            throw new LogicException('DISBURSEMENT_PROVIDER_TRANSACTION_OPEN: a provider is called only after the outermost commit.');
        }
    }

    /** @param array<string, mixed> $fields */
    private function mac(array $fields): string
    {
        return hash_hmac('sha256', $this->json->encode($fields), hash_hmac('sha256', 'rozine-synthetic-payout-provider-v1', $this->config->string('app.key')));
    }

    private function timestamp(string $value): bool
    {
        try {
            return CarbonImmutable::createFromFormat(DATE_ATOM, $value)?->format(DATE_ATOM) === $value;
        } catch (Throwable) {
            return false;
        }
    }
}
