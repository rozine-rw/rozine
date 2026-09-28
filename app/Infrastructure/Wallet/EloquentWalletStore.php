<?php

declare(strict_types=1);

namespace App\Infrastructure\Wallet;

use App\Application\Identity\AuthorizeActiveRole;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Application\Operations\Contracts\OperationJournal;
use App\Application\Wallet\Contracts\DepositProvider;
use App\Application\Wallet\Contracts\WalletStore;
use App\Application\Wallet\SyntheticWalletGuard;
use App\Domain\Operations\CommandRejection;
use App\Domain\Operations\OperationResult;
use App\Domain\Wallet\AccountRestriction;
use App\Domain\Wallet\DepositPolicyTerms;
use App\Domain\Wallet\WalletMoney;
use App\Models\DepositPolicy;
use App\Models\InvestorAccountRestriction;
use App\Models\InvestorFundingMethod;
use App\Models\InvestorWallet;
use App\Models\WalletDepositDispatch;
use App\Models\WalletDepositIntent;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * The wallet adapter. Lock order: the investor's user and Party (current authority), then the
 * wallet, then an intent. A deposit writes its intent, dispatch outbox row and journal receipt in
 * one transaction and never touches the ledger; no provider is called while any lock is held.
 *
 * @phpstan-type Policy array{model: DepositPolicy, terms: DepositPolicyTerms}
 */
final class EloquentWalletStore implements WalletStore
{
    public function __construct(private AuthorizeActiveRole $authority, private OperationJournal $journal, private CanonicalJson $json,
        private DepositProvider $provider, private SyntheticWalletGuard $guard) {}

    /**
     * @param  array{currency: string, amount: string}  $amount
     * @return array<string, mixed>
     */
    public function deposit(int $userId, int $contextRevision, string $requestId, array $amount, string $methodId): array
    {
        return $this->authority->handle($userId, 'investor', null, $contextRevision,
            function (array $identity) use ($userId, $contextRevision, $requestId, $amount, $methodId): array {
                $partyId = (string) $identity['party']['id'];
                $wallet = $this->lockWallet($partyId);
                $input = ['identity_context_revision' => $contextRevision, 'amount' => ['currency' => $amount['currency'], 'amount' => $amount['amount']], 'method_id' => $methodId];

                return $this->journal->execute('party:'.$partyId, $userId, 'wallet.deposit', $requestId, 'investor_wallet', $wallet->id, $input,
                    function (): void {}, function (string $operationId) use ($wallet, $partyId, $userId, $requestId, $amount, $methodId): OperationResult {
                        $data = ['wallet_id' => $wallet->id];
                        $policy = $this->applicablePolicy() ?? throw new CommandRejection('POLICY_INPUT_REQUIRED', data: $data);
                        $method = $this->verifiedMethods($partyId)->firstWhere('id', $methodId) ?? throw new CommandRejection('DEPOSIT_METHOD_UNVERIFIED', data: $data);
                        if ($this->currentRestriction($partyId)?->blocksDeposit() === true) {
                            throw new CommandRejection('RESTRICTION_ACTIVE', data: $data);
                        }
                        $money = $amount['currency'] === 'RWF' ? WalletMoney::fromInput($amount['amount']) : null;
                        $error = $money === null ? 'Enter a whole RWF amount of at most 12 digits.' : $policy['terms']->boundsError($money);
                        if ($money === null || $error !== null) {
                            throw new CommandRejection('VALIDATION_FAILED', 422, fieldErrors: ['amount' => [(string) $error]], data: $data);
                        }
                        $intent = $this->recordIntent($wallet, $method, $policy, $money, $operationId, $requestId, $userId);

                        return new OperationResult('DEPOSIT_INTENT_RECORDED', [...$data, 'intent_id' => $intent->id,
                            'receipt' => $this->intentReceipt($intent)], 1, policyVersion: $policy['terms']->version);
                    });
            });
    }

    /** @return array<string, mixed> */
    public function findDeposit(int $userId, int $contextRevision, string $requestId): array
    {
        return $this->authority->handle($userId, 'investor', null, $contextRevision, function (array $identity) use ($requestId): array {
            $partyId = (string) $identity['party']['id'];
            $walletId = InvestorWallet::query()->where('party_id', $partyId)->value('id');

            return $this->journal->find('party:'.$partyId, 'wallet.deposit', $requestId, function (string $type, string $id) use ($walletId): void {
                ($type === 'investor_wallet' && $id === $walletId) || throw new CommandRejection('OPERATION_NOT_FOUND', 404);
            });
        });
    }

    /** The Party's wallet, created on first use, locked for the rest of the transaction. */
    private function lockWallet(string $partyId): InvestorWallet
    {
        DB::insert("INSERT INTO investor_wallets (id, party_id, currency, created_at) VALUES (?, ?, 'RWF', now()) ON CONFLICT (party_id) DO NOTHING",
            [strtolower((string) Str::ulid()), $partyId]);

        return InvestorWallet::query()->where('party_id', $partyId)->lockForUpdate()->sole();
    }

    /**
     * The current applicable deposit policy: the latest version already in effect, unless that
     * version withdrew deposits. Synthetic versions apply only where the synthetic provider does.
     *
     * @return Policy|null
     */
    private function applicablePolicy(): ?array
    {
        $policy = $this->guard->allowed() ? DepositPolicy::query()->where('effective_at', '<=', now())->orderByDesc('effective_at')->orderByDesc('id')->first() : null;
        if ($policy === null || $policy->status !== 'active') {
            return null;
        }

        return ['model' => $policy, 'terms' => new DepositPolicyTerms($policy->version, $policy->synthetic, WalletMoney::of((string) $policy->fee),
            $policy->minimum === null ? null : WalletMoney::of($policy->minimum), $policy->maximum === null ? null : WalletMoney::of($policy->maximum))];
    }

    /** @return Collection<int, InvestorFundingMethod> */
    private function verifiedMethods(string $partyId): Collection
    {
        return InvestorFundingMethod::query()->where('party_id', $partyId)->whereNotNull('verified_at')->where('verified_at', '<=', now())
            ->whereNull('revoked_at')->orderBy('id')->get();
    }

    /**
     * The current applicable account case, if any: in effect and not expired. When several apply,
     * one that covers deposits wins, then the earliest.
     */
    private function currentRestriction(string $partyId): ?AccountRestriction
    {
        $cases = InvestorAccountRestriction::query()->where('party_id', $partyId)->where('effective_at', '<=', now())
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))->orderBy('effective_at')->orderBy('id')->get()
            ->map(fn (InvestorAccountRestriction $case): AccountRestriction => new AccountRestriction($case->cause, $case->scope));

        return $cases->first(fn (AccountRestriction $case): bool => $case->blocksDeposit()) ?? $cases->first();
    }

    /** @param Policy $policy */
    private function recordIntent(InvestorWallet $wallet, InvestorFundingMethod $method, array $policy, WalletMoney $amount, string $operationId, string $requestId, int $userId): WalletDepositIntent
    {
        $terms = $policy['terms'];
        $reference = 'syn_'.bin2hex(random_bytes(20));
        $intent = new WalletDepositIntent;
        $intent->id = strtolower((string) Str::ulid());
        $recordedAt = now('UTC')->toImmutable()->startOfSecond();
        $payload = ['intent_id' => $intent->id, 'wallet_id' => $wallet->id, 'party_id' => $wallet->party_id, 'operation_id' => $operationId,
            'request_id' => strtolower($requestId), 'method_id' => $method->id, 'policy_id' => $policy['model']->id, 'policy_version' => $terms->version,
            'amount' => $amount->amount(), 'fee' => $terms->fee->amount(), 'credited' => $terms->credited($amount)->amount(), 'currency' => 'RWF',
            'provider' => $this->provider->name(), 'actor_user_id' => $userId, 'recorded_at' => $recordedAt->toIso8601String()];
        $intent->forceFill(['wallet_id' => $wallet->id, 'party_id' => $wallet->party_id, 'operation_id' => $operationId, 'request_id' => $payload['request_id'],
            'method_id' => $method->id, 'policy_id' => $policy['model']->id, 'amount' => $payload['amount'], 'fee' => $payload['fee'],
            'credited' => $payload['credited'], 'currency' => 'RWF', 'provider' => $payload['provider'], 'provider_reference' => $reference,
            'provider_reference_sha256' => hash('sha256', $reference), 'payload' => $payload,
            'sha256' => hash('sha256', $this->json->encode($payload)), 'created_at' => $recordedAt])->save();
        (new WalletDepositDispatch)->forceFill(['intent_id' => $intent->id, 'phase' => 'queued', 'created_at' => $recordedAt])->save();

        return $intent;
    }

    /**
     * The intent's sealed facts. A row whose payload does not match its hash and columns is an
     * integrity failure, never a readable deposit.
     *
     * @return array<string, mixed>
     */
    private function intentPayload(WalletDepositIntent $intent): array
    {
        $payload = $intent->payload;
        $columns = ['intent_id' => $intent->id, 'wallet_id' => $intent->wallet_id, 'party_id' => $intent->party_id, 'operation_id' => $intent->operation_id,
            'request_id' => $intent->request_id, 'method_id' => $intent->method_id, 'policy_id' => $intent->policy_id, 'amount' => $intent->amount,
            'fee' => $intent->fee, 'credited' => $intent->credited, 'provider' => $intent->provider];
        if (! hash_equals($intent->sha256, hash('sha256', $this->json->encode($payload))) || array_intersect_key($payload, $columns) !== $columns) {
            throw new RuntimeException('WALLET_DEPOSIT_INTEGRITY_FAILED');
        }

        return $payload;
    }

    /**
     * DEPOSIT_INTENT_RECORDED: the request was durably recorded, not that any money moved.
     *
     * @return array<string, mixed>
     */
    private function intentReceipt(WalletDepositIntent $intent): array
    {
        $payload = $this->intentPayload($intent);

        return ['receipt_id' => $intent->id, 'operation_id' => $intent->operation_id, 'request_id' => $intent->request_id,
            'code' => 'DEPOSIT_INTENT_RECORDED', 'recorded_at' => $payload['recorded_at'], 'amount' => ['currency' => 'RWF', 'amount' => $intent->amount],
            'units' => null, 'reference' => 'RZD-'.strtoupper(substr($intent->id, -10)), 'revision' => 1,
            'policy_version' => $payload['policy_version'], 'disclosure_version' => null];
    }
}
