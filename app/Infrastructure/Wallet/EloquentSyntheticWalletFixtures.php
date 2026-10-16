<?php

declare(strict_types=1);

namespace App\Infrastructure\Wallet;

use App\Application\Environment\EnvironmentIsolation;
use App\Application\Wallet\Contracts\SyntheticEventSigner;
use App\Application\Wallet\Contracts\SyntheticWalletFixtures;
use App\Application\Wallet\Contracts\SyntheticWithdrawalSigner;
use App\Application\Wallet\SyntheticWalletGuard;
use App\Models\DepositPolicy;
use App\Models\InvestorAccountRestriction;
use App\Models\InvestorFundingMethod;
use App\Models\WalletDepositIntent;
use App\Models\WalletWithdrawal;
use Illuminate\Support\Str;
use LogicException;

/** Synthetic wallet rows for local, testing and UAT checks. Every write is append-only. */
final class EloquentSyntheticWalletFixtures implements SyntheticWalletFixtures
{
    public function __construct(private SyntheticWalletGuard $guard, private SyntheticEventSigner $signer, private EnvironmentIsolation $isolation,
        private SyntheticWithdrawalSigner $payouts) {}

    /** @return array{method_id: string, policy_version: string} */
    public function prepareInvestor(string $partyId): array
    {
        $this->guard->assertAllowed();
        $method = InvestorFundingMethod::query()->where('party_id', $partyId)->whereNotNull('verified_at')->whereNull('revoked_at')
            ->where('label', '!=', self::DECLINING_LABEL)->orderBy('id')->first();
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

    public function currentPolicy(): ?array
    {
        $this->guard->assertAllowed();
        $policy = DepositPolicy::query()->where('effective_at', '<=', now())->orderByDesc('effective_at')->orderByDesc('id')->first();

        return $policy === null ? null : ['version' => $policy->version, 'status' => $policy->status, 'fee' => $policy->fee,
            'minimum' => $policy->minimum, 'maximum' => $policy->maximum];
    }

    public function addDecliningMethod(string $partyId): string
    {
        $this->guard->assertAllowed();
        $method = InvestorFundingMethod::query()->where('party_id', $partyId)->whereNotNull('verified_at')->whereNull('revoked_at')
            ->where('label', self::DECLINING_LABEL)->orderBy('id')->first();
        if ($method === null) {
            $method = new InvestorFundingMethod;
            $method->forceFill(['party_id' => $partyId, 'kind' => 'mtn', 'label' => self::DECLINING_LABEL, 'masked' => '+250 788 ···· 000',
                'reference' => 'synthetic-msisdn-'.Str::lower(Str::random(12)), 'verification_source' => 'synthetic', 'verified_at' => now()])->save();
        }

        return $method->id;
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

    /** @return array{withdrawal_id: string, message: array<string, string>} */
    public function withdrawalEvent(string $requestId, string $state, ?string $eventId, ?string $amount): array
    {
        $this->guard->assertAllowed();
        $withdrawals = WalletWithdrawal::query()->where('request_id', strtolower($requestId))->get();
        if ($withdrawals->count() !== 1) {
            throw new LogicException('WALLET_SYNTHETIC_WITHDRAWAL_NOT_FOUND: no single withdrawal was recorded under that request id.');
        }
        $withdrawal = $withdrawals->sole();

        return ['withdrawal_id' => $withdrawal->id, 'message' => $this->payouts->sign(['provider' => 'synthetic',
            'event_id' => $eventId ?? 'synthetic-event-'.Str::lower(Str::random(16)), 'reference' => $withdrawal->provider_reference, 'state' => $state,
            'amount' => $amount ?? $withdrawal->received, 'currency' => 'RWF', 'environment' => $this->isolation->profile(),
            'observed_at' => now('UTC')->startOfSecond()->format(DATE_ATOM)])];
    }

    private function appendPolicy(string $version, string $status): DepositPolicy
    {
        $policy = new DepositPolicy;
        $policy->forceFill(['version' => $version, 'synthetic' => true, 'status' => $status, 'fee' => $status === 'active' ? self::POLICY_TERMS['fee'] : null,
            'minimum' => $status === 'active' ? self::POLICY_TERMS['minimum'] : null, 'maximum' => $status === 'active' ? self::POLICY_TERMS['maximum'] : null, 'effective_at' => now()])->save();

        return $policy;
    }
}
