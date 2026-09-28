<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Application\Identity\SelectActiveRole;
use App\Application\Wallet\RecordDepositIntent;
use App\Models\DepositPolicy;
use App\Models\InvestorFundingMethod;
use App\Models\Party;
use App\Models\RoleMembership;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Synthetic Investor wallet scenarios: a verified Party with the investor role active (identity
 * context revision 1), a synthetic funding method and an explicit synthetic deposit policy.
 */
final class InvestorWalletFixture
{
    /** @return array{user: User, party: Party} */
    public static function investor(?string $email = null): array
    {
        $party = Party::factory()->verified()->create();
        $user = User::factory()->for($party)->create($email === null ? [] : ['email' => $email]);
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
}
