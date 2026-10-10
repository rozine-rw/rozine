<?php

declare(strict_types=1);

namespace App\Infrastructure\Wallet;

use App\Application\Environment\EnvironmentIsolation;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Application\Wallet\ApplyBusinessProviderOutcome;
use App\Application\Wallet\ApplyProviderOutcome;
use App\Application\Wallet\Contracts\DepositProvider;
use App\Application\Wallet\Contracts\SyntheticEventSigner;
use App\Application\Wallet\Contracts\SyntheticWalletFixtures;
use App\Application\Wallet\DepositInstruction;
use App\Application\Wallet\SyntheticWalletGuard;
use App\Application\Wallet\VerifiedDepositEvent;
use App\Domain\Operations\CommandRejection;
use App\Models\InvestorFundingMethod;
use App\Models\WalletDepositIntent;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Sleep;
use Illuminate\Support\Str;
use LogicException;
use Throwable;

/**
 * An offline provider for local, testing and UAT only. Messages are HMAC-signed over their canonical
 * JSON with a secret derived from the application key, so a message cannot be forged or altered
 * without the key. It never contacts anything, and it holds no real account or PSP arrangement; on
 * UAT an accepted deposit settles by itself a few seconds later (see `scheduleSettlement`).
 */
final class SyntheticDepositProvider implements DepositProvider, SyntheticEventSigner
{
    public const array FIELDS = ['amount', 'currency', 'environment', 'event_id', 'observed_at', 'provider', 'reference', 'state'];

    public const int SETTLEMENT_DELAY_SECONDS = 3;

    public function __construct(private SyntheticWalletGuard $guard, private CanonicalJson $json, private Repository $config,
        private EnvironmentIsolation $isolation, private Application $app) {}

    public function name(): string
    {
        return 'synthetic';
    }

    public function idempotentSends(): bool
    {
        return true;
    }

    public function initiate(DepositInstruction $instruction): bool
    {
        $this->guard->assertAllowed();
        if (app('db.transactions')->callbackApplicableTransactions()->isNotEmpty()) {
            throw new LogicException('WALLET_DISPATCH_TRANSACTION_OPEN: a provider is called only after the outermost commit.');
        }
        $this->scheduleSettlement($instruction);

        return true;
    }

    /**
     * @param  array<string, string>  $fields
     * @return array<string, string>
     */
    public function sign(array $fields): array
    {
        $this->guard->assertAllowed();
        $message = array_intersect_key($fields, array_flip(self::FIELDS));
        ksort($message);

        return [...$message, 'signature' => $this->mac($message)];
    }

    /** @param array<string, mixed> $message */
    public function verify(array $message): VerifiedDepositEvent
    {
        $this->guard->assertAllowed();
        $fields = array_intersect_key($message, array_flip(self::FIELDS));
        ksort($fields);
        $strings = array_filter($message, is_string(...));
        if (count($fields) !== count(self::FIELDS) || count($message) !== count(self::FIELDS) + 1 || count($strings) !== count($message)
            || ! isset($message['signature']) || ! hash_equals($this->mac($fields), $message['signature'])) {
            throw new CommandRejection('PROVIDER_EVENT_UNVERIFIED', 401);
        }
        if ($fields['provider'] !== $this->name() || preg_match('/^[A-Za-z0-9_]{1,80}$/D', $fields['reference']) !== 1 || preg_match('/^[A-Za-z0-9._:-]{1,120}$/D', $fields['event_id']) !== 1
            || ! in_array($fields['state'], ['pending', 'succeeded', 'failed', 'unknown'], true)
            || preg_match('/^(0|[1-9][0-9]{0,11})$/D', $fields['amount']) !== 1 || preg_match('/^[A-Z]{3}$/D', $fields['currency']) !== 1
            || preg_match('/^[a-z]{1,20}$/D', $fields['environment']) !== 1 || ! $this->timestamp($fields['observed_at'])) {
            throw new CommandRejection('PROVIDER_EVENT_MALFORMED', 422);
        }

        return new VerifiedDepositEvent($fields['provider'], $fields['event_id'], $fields['reference'], $fields['state'], $fields['amount'],
            $fields['currency'], $fields['environment'], $fields['observed_at'], hash('sha256', $this->json->encode($fields)), $message);
    }

    /**
     * Staging's stand-in for the provider callback. On UAT the accepted deposit settles once, a few
     * seconds after the response is sent, through the same signed message, verification and
     * once-only application a real provider event takes: it succeeds, or fails when an Investor paid
     * from the declining test number. Local and testing keep the manual `local:wallet --event` hook,
     * so their checks decide every outcome themselves.
     */
    private function scheduleSettlement(DepositInstruction $instruction): void
    {
        if ($this->isolation->profile() !== 'uat') {
            return;
        }
        $pending = true;
        $this->app->terminating(function () use (&$pending, $instruction): void {
            if ($pending) {
                $pending = false;
                $this->settle($instruction);
            }
        });
    }

    private function settle(DepositInstruction $instruction): void
    {
        Sleep::for(self::SETTLEMENT_DELAY_SECONDS)->seconds();
        try {
            $intent = WalletDepositIntent::query()->where('provider', $this->name())
                ->where('provider_reference_sha256', hash('sha256', $instruction->providerReference))->first();
            $declined = $intent !== null && InvestorFundingMethod::query()->whereKey($intent->method_id)->value('label') === SyntheticWalletFixtures::DECLINING_LABEL;
            $message = $this->sign(['provider' => $this->name(), 'event_id' => 'synthetic-settlement-'.Str::lower(Str::random(16)),
                'reference' => $instruction->providerReference, 'state' => $declined ? 'failed' : 'succeeded', 'amount' => $instruction->amount,
                'currency' => $instruction->currency, 'environment' => $instruction->environment, 'observed_at' => now('UTC')->startOfSecond()->format(DATE_ATOM)]);
            $intent !== null
                ? $this->app->make(ApplyProviderOutcome::class)->handle($message)
                : $this->app->make(ApplyBusinessProviderOutcome::class)->handle($message);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    /** @param array<string, mixed> $fields */
    private function mac(array $fields): string
    {
        return hash_hmac('sha256', $this->json->encode($fields), hash_hmac('sha256', 'rozine-synthetic-deposit-provider-v1', $this->config->string('app.key')));
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
