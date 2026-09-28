<?php

declare(strict_types=1);

namespace App\Infrastructure\Wallet;

use App\Application\Environment\EnvironmentIsolation;
use App\Application\Wallet\Contracts\SyntheticEventSigner;
use App\Application\Wallet\Contracts\SyntheticWalletFixtures;
use App\Application\Wallet\SyntheticWalletGuard;
use App\Models\DepositPolicy;
use App\Models\InvestorAccountRestriction;
use App\Models\InvestorFundingMethod;
use App\Models\WalletDepositIntent;
use Illuminate\Support\Str;
use LogicException;

/** Synthetic wallet rows for local and testing checks. Every write is append-only. */
final class EloquentSyntheticWalletFixtures implements SyntheticWalletFixtures
{
    public function __construct(private SyntheticWalletGuard $guard, private SyntheticEventSigner $signer, private EnvironmentIsolation $isolation) {}

    /** @return array{method_id: string, policy_version: string} */
    public function prepareInvestor(string $partyId): array
    {
        $this->guard->assertAllowed();
        $method = InvestorFundingMethod::query()->where('party_id', $partyId)->whereNotNull('verified_at')->whereNull('revoked_at')->orderBy('id')->first();
        if ($method === null) {
            $method = new InvestorFundingMethod;
            $method->forceFill(['party_id' => $partyId, 'kind' => 'mtn', 'label' => 'MTN MoMo', 'masked' => '+250 788 ···· 456',
                'reference' => 'synthetic-msisdn-'.Str::lower(Str::random(12)), 'verification_source' => 'synthetic', 'verified_at' => now()])->save();
        }
        $current = DepositPolicy::query()->where('effective_at', '<=', now())->orderByDesc('effective_at')->orderByDesc('id')->first();
        if ($current === null || $current->status !== 'active') {
            $current = $this->appendPolicy('synthetic-deposit-policy-'.DepositPolicy::query()->count(), 'active');
        }

        return ['method_id' => $method->id, 'policy_version' => $current->version];
    }

    public function withdrawPolicy(): string
    {
        $this->guard->assertAllowed();

        return $this->appendPolicy('synthetic-deposit-policy-'.DepositPolicy::query()->count().'-withdrawn', 'withdrawn')->version;
    }

    public function restrict(string $partyId): string
    {
        $this->guard->assertAllowed();
        $case = new InvestorAccountRestriction;
        $case->forceFill(['party_id' => $partyId, 'cause' => 'high_risk_hold', 'scope' => ['withdrawals', 'primary_commitments', 'secondary_trading'],
            'source' => 'synthetic', 'effective_at' => now()->startOfSecond(), 'expires_at' => now()->startOfSecond()->addHours(24)])->save();

        return $case->id;
    }

    /** @return array{intent_id: string, message: array<string, string>} */
    public function event(string $requestId, string $state, ?string $eventId, ?string $amount): array
    {
        $this->guard->assertAllowed();
        $intents = WalletDepositIntent::query()->where('request_id', strtolower($requestId))->get();
        if ($intents->count() !== 1) {
            throw new LogicException('WALLET_SYNTHETIC_INTENT_NOT_FOUND: no single deposit was recorded under that request id.');
        }
        $intent = $intents->sole();

        return ['intent_id' => $intent->id, 'message' => $this->signer->sign(['provider' => 'synthetic',
            'event_id' => $eventId ?? 'synthetic-event-'.Str::lower(Str::random(16)), 'reference' => $intent->provider_reference, 'state' => $state,
            'amount' => $amount ?? $intent->amount, 'currency' => 'RWF', 'environment' => $this->isolation->profile(),
            'observed_at' => now('UTC')->startOfSecond()->format(DATE_ATOM)])];
    }

    private function appendPolicy(string $version, string $status): DepositPolicy
    {
        $policy = new DepositPolicy;
        $policy->forceFill(['version' => $version, 'synthetic' => true, 'status' => $status, 'fee' => $status === 'active' ? '0' : null,
            'minimum' => $status === 'active' ? '1000' : null, 'maximum' => $status === 'active' ? '5000000' : null, 'effective_at' => now()])->save();

        return $policy;
    }
}
