<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Application\Environment\EnvironmentIsolation;
use App\Application\Identity\SelectActiveRole;
use App\Application\Wallet\ApplyProviderOutcome;
use App\Application\Wallet\ApplyWithdrawalOutcome;
use App\Application\Wallet\Contracts\SyntheticEventSigner;
use App\Application\Wallet\Contracts\SyntheticWithdrawalSigner;
use App\Application\Wallet\RecordDepositIntent;
use App\Application\Wallet\RecordWithdrawal;
use App\Models\DepositPolicy;
use App\Models\InvestorFundingMethod;
use App\Models\Party;
use App\Models\RoleMembership;
use App\Models\User;
use App\Models\WalletDepositIntent;
use App\Models\WalletWithdrawal;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Synthetic Investor wallet scenarios: a verified Party with the investor role active (identity
 * context revision 1), a synthetic funding method and an explicit synthetic deposit policy.
 */
final class InvestorWalletFixture
{
    /** @return array{user: User, party: Party} */
    public static function investor(?string $email = null, ?string $password = null): array
    {
        $party = Party::factory()->verified()->create();
        $user = User::factory()->for($party)->create(array_filter(['email' => $email, 'password' => $password === null ? null : Hash::make($password)]));
        RoleMembership::factory()->for($party)->active()->create(['role' => 'investor']);
        app(SelectActiveRole::class)->handle($user->id, 'investor', 0, (string) Str::uuid());

        return ['user' => $user->refresh(), 'party' => $party];
    }

    public static function policy(string $fee = '0', ?string $minimum = '1000', ?string $maximum = '1000000', ?string $version = null): DepositPolicy
    {
        return DepositPolicy::factory()->create(['version' => $version ?? 'synthetic-deposit-policy-'.Str::lower(Str::random(8)),
            'fee' => $fee, 'minimum' => $minimum, 'maximum' => $maximum]);
    }

    public static function method(Party $party, string $kind = 'mtn'): InvestorFundingMethod
    {
        return InvestorFundingMethod::factory()->create(['party_id' => $party->id, 'kind' => $kind,
            'label' => ['mtn' => 'MTN MoMo', 'airtel' => 'Airtel Money', 'bank' => 'Bank of Kigali'][$kind],
            'masked' => $kind === 'bank' ? '···· 4417' : '+250 788 ···· 456']);
    }

    /**
     * A policy, an Investor and a verified method, ready to deposit.
     *
     * @return array{user: User, party: Party, method: InvestorFundingMethod, policy: DepositPolicy}
     */
    public static function ready(string $fee = '0'): array
    {
        $policy = DepositPolicy::query()->where('status', 'active')->first() ?? self::policy($fee, $fee === '0' ? '1000' : (string) ((int) $fee + 1000));
        $investor = self::investor();

        return [...$investor, 'method' => self::method($investor['party']), 'policy' => $policy];
    }

    /**
     * @param  array{user: User, method: InvestorFundingMethod}  $fixture
     * @return array<string, mixed>
     */
    public static function deposit(array $fixture, string $amount = '50000', ?string $requestId = null, ?string $methodId = null, int $context = 1, string $currency = 'RWF'): array
    {
        return app(RecordDepositIntent::class)->handle($fixture['user']->id, $context, $requestId ?? (string) Str::uuid(),
            ['currency' => $currency, 'amount' => $amount], $methodId ?? $fixture['method']->id);
    }

    /**
     * Delivers a signed synthetic provider event for an intent.
     *
     * @return array{disposition: string, state: string, credited: bool, replayed: bool}
     */
    public static function settle(string $intentId, string $state = 'succeeded', ?string $eventId = null, ?string $amount = null): array
    {
        $intent = WalletDepositIntent::query()->whereKey($intentId)->sole();

        return app(ApplyProviderOutcome::class)->handle(app(SyntheticEventSigner::class)->sign(['provider' => 'synthetic',
            'event_id' => $eventId ?? 'synthetic-event-'.Str::lower(Str::random(12)), 'reference' => $intent->provider_reference, 'state' => $state,
            'amount' => $amount ?? $intent->amount, 'currency' => 'RWF', 'environment' => app(EnvironmentIsolation::class)->profile(), 'observed_at' => now('UTC')->startOfSecond()->format(DATE_ATOM)]));
    }

    /**
     * An Investor ready to withdraw, with a verified deposit of `$balance` already credited.
     *
     * @return array{user: User, party: Party, method: InvestorFundingMethod, policy: DepositPolicy}
     */
    public static function funded(string $balance = '500000'): array
    {
        $fixture = self::ready();
        // Each deposit credits its amount less the policy fee, within the policy maximum.
        $fee = (int) $fixture['policy']->fee;
        $chunk = (int) ($fixture['policy']->maximum ?? 1000000) - $fee;
        for ($left = (int) $balance; $left > 0; $left -= $chunk) {
            self::settle((string) self::deposit($fixture, (string) (min($left, $chunk) + $fee))['data']['intent_id']);
        }

        return $fixture;
    }

    /**
     * @param  array{user: User, method: InvestorFundingMethod}  $fixture
     * @return array<string, mixed>
     */
    public static function withdraw(array $fixture, string $amount = '100000', ?string $requestId = null, ?string $methodId = null, int $context = 1): array
    {
        return app(RecordWithdrawal::class)->handle($fixture['user']->id, $context, $requestId ?? (string) Str::uuid(),
            ['currency' => 'RWF', 'amount' => $amount], $methodId ?? $fixture['method']->id);
    }

    /**
     * Delivers a signed synthetic provider outcome for a withdrawal.
     *
     * @param  array<string, string>  $overrides
     * @return array{disposition: string, state: string, settled: bool, replayed: bool}
     */
    public static function settleWithdrawal(string $withdrawalId, string $state = 'succeeded', ?string $eventId = null, array $overrides = []): array
    {
        return app(ApplyWithdrawalOutcome::class)->handle(self::withdrawalEvent($withdrawalId, $state, $eventId, $overrides));
    }

    /**
     * @param  array<string, string>  $overrides
     * @return array<string, string>
     */
    public static function withdrawalEvent(string $withdrawalId, string $state = 'succeeded', ?string $eventId = null, array $overrides = []): array
    {
        $withdrawal = WalletWithdrawal::query()->whereKey($withdrawalId)->sole();

        return app(SyntheticWithdrawalSigner::class)->sign([...['provider' => 'synthetic',
            'event_id' => $eventId ?? 'synthetic-event-'.Str::lower(Str::random(12)), 'reference' => $withdrawal->provider_reference, 'state' => $state,
            'amount' => $withdrawal->received, 'currency' => 'RWF', 'environment' => app(EnvironmentIsolation::class)->profile(), 'observed_at' => now('UTC')->startOfSecond()->format(DATE_ATOM)],
            ...$overrides]);
    }
}
