<?php

declare(strict_types=1);

namespace App\Infrastructure\Wallet;

use App\Application\Identity\AuthorizeActiveRole;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Application\Operations\Contracts\OperationJournal;
use App\Application\Wallet\Contracts\DepositProvider;
use App\Application\Wallet\Contracts\WalletStore;
use App\Application\Wallet\DepositInstruction;
use App\Application\Wallet\SyntheticWalletGuard;
use App\Application\Wallet\VerifiedDepositEvent;
use App\Domain\Operations\CommandRejection;
use App\Domain\Operations\OperationResult;
use App\Domain\Wallet\AccountRestriction;
use App\Domain\Wallet\DepositOutcome;
use App\Domain\Wallet\DepositPolicyTerms;
use App\Domain\Wallet\JournalEntry;
use App\Domain\Wallet\WalletMoney;
use App\Models\DepositPolicy;
use App\Models\InvestorAccountRestriction;
use App\Models\InvestorFundingMethod;
use App\Models\InvestorWallet;
use App\Models\LedgerEntry;
use App\Models\LedgerLine;
use App\Models\WalletDepositCredit;
use App\Models\WalletDepositDispatch;
use App\Models\WalletDepositIntent;
use App\Models\WalletProviderEvent;
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

    /** @return array{disposition: string, state: string, credited: bool, replayed: bool} */
    public function applyOutcome(VerifiedDepositEvent $event, string $environment): array
    {
        return DB::transaction(function () use ($event, $environment): array {
            DB::select('SELECT pg_advisory_xact_lock(hashtextextended(?, 0))', ['wallet-provider-event|'.$event->provider.'|'.$event->eventId]);
            $walletId = WalletDepositIntent::query()->where('provider', $event->provider)
                ->where('provider_reference_sha256', hash('sha256', $event->providerReference))->value('wallet_id')
                ?? throw new CommandRejection('DEPOSIT_REFERENCE_UNKNOWN', 404);
            InvestorWallet::query()->whereKey($walletId)->lockForUpdate()->sole();
            $intent = WalletDepositIntent::query()->where('provider_reference_sha256', hash('sha256', $event->providerReference))->lockForUpdate()->sole();
            $this->intentPayload($intent);
            $existing = WalletProviderEvent::query()->where('provider', $event->provider)->where('provider_event_id', $event->eventId)->get();
            $replay = $existing->firstWhere('content_sha256', $event->contentSha256);
            if ($replay !== null) {
                return ['disposition' => $replay->disposition, 'state' => $this->currentState($intent->id), 'credited' => false, 'replayed' => true];
            }
            $current = $this->currentState($intent->id);
            $outcome = DepositOutcome::transition($current, $event->state);
            $disposition = match (true) {
                $existing->isNotEmpty() => 'key_conflict',
                $event->environment !== $environment || $event->currency !== $intent->currency || $event->amount !== $intent->amount => 'mismatch',
                default => $outcome->disposition,
            };
            $record = new WalletProviderEvent;
            $record->forceFill(['provider' => $event->provider, 'provider_event_id' => $event->eventId, 'intent_id' => $intent->id,
                'content_sha256' => $event->contentSha256, 'state' => $event->state, 'amount' => $event->amount, 'currency' => $event->currency,
                'environment' => $event->environment, 'observed_at' => $event->observedAt, 'disposition' => $disposition, 'evidence' => $event->evidence,
                'created_at' => now('UTC')])->save();
            $credited = $disposition === 'applied' && $outcome->credits();
            if ($credited) {
                $this->postCredit($intent, $record);
            }

            return ['disposition' => $disposition, 'state' => $disposition === 'applied' ? $outcome->state : $current, 'credited' => $credited, 'replayed' => false];
        }, 3);
    }

    /** @return list<DepositInstruction> */
    public function claimDispatches(?string $intentId, int $limit, bool $resendInterrupted, string $environment): array
    {
        return DB::transaction(function () use ($intentId, $limit, $resendInterrupted, $environment): array {
            $claimed = WalletDepositDispatch::query()->where('phase', 'claimed')->select('intent_id');
            $outcomes = WalletDepositDispatch::query()->whereIn('phase', ['acknowledged', 'unacknowledged'])->select('intent_id');
            $queued = WalletDepositDispatch::query()->where('phase', 'queued')->whereNotIn('intent_id', $claimed)
                ->when($intentId !== null, fn ($query) => $query->where('intent_id', $intentId))
                ->orderBy('id')->limit($limit)->lock('FOR UPDATE SKIP LOCKED')->pluck('intent_id')->all();
            $interrupted = $resendInterrupted ? WalletDepositDispatch::query()->where('phase', 'claimed')->whereNotIn('intent_id', $outcomes)
                ->where('created_at', '<', now()->subMinutes(5))->when($intentId !== null, fn ($query) => $query->where('intent_id', $intentId))
                ->orderBy('id')->limit($limit)->lock('FOR UPDATE SKIP LOCKED')->pluck('intent_id')->all() : [];
            foreach ($queued as $queuedIntent) {
                (new WalletDepositDispatch)->forceFill(['intent_id' => $queuedIntent, 'phase' => 'claimed', 'created_at' => now('UTC')])->save();
            }

            return array_values(WalletDepositIntent::query()->whereIn('id', [...$queued, ...$interrupted])->orderBy('id')->get()
                ->map(fn (WalletDepositIntent $intent): DepositInstruction => new DepositInstruction($intent->id, $intent->operation_id,
                    $intent->provider_reference, $this->intentPayload($intent)['amount'], $intent->currency, $environment))->all());
        }, 3);
    }

    public function recordDispatch(string $intentId, bool $acknowledged): void
    {
        DB::insert("INSERT INTO wallet_deposit_dispatches (id, intent_id, phase, created_at) VALUES (?, ?, ?, now())
            ON CONFLICT (intent_id) WHERE phase IN ('acknowledged', 'unacknowledged') DO NOTHING",
            [strtolower((string) Str::ulid()), $intentId, $acknowledged ? 'acknowledged' : 'unacknowledged']);
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
     * The intent's provider state: its applied final outcome, else its latest applied non-final
     * outcome, else pending. Duplicates, conflicts, mismatches and after-final events never move it.
     */
    private function currentState(string $intentId): string
    {
        $applied = WalletProviderEvent::query()->where('intent_id', $intentId)->where('disposition', 'applied');

        return (clone $applied)->whereIn('state', ['succeeded', 'failed'])->value('state')
            ?? $applied->orderByDesc('created_at')->orderByDesc('id')->value('state') ?? 'pending';
    }

    /**
     * Posts the one balanced credit for an applied success and its separate DEPOSIT_CREDITED receipt.
     * The receipt keeps the originating operation; the entry and event are internal provenance.
     */
    private function postCredit(WalletDepositIntent $intent, WalletProviderEvent $event): void
    {
        $payload = $this->intentPayload($intent);
        $journal = JournalEntry::depositCredit(WalletMoney::of($intent->amount), WalletMoney::of($intent->fee));
        $recordedAt = now('UTC')->toImmutable()->startOfSecond();
        $entry = new LedgerEntry;
        $entry->id = strtolower((string) Str::ulid());
        $lines = array_map(fn ($line): array => ['account' => $line->account, 'direction' => $line->direction, 'amount' => $line->amount->amount()], $journal->lines);
        $entryPayload = ['entry_id' => $entry->id, 'wallet_id' => $intent->wallet_id, 'kind' => $journal->kind, 'source_type' => 'wallet_deposit_intent',
            'source_id' => $intent->id, 'operation_id' => $intent->operation_id, 'request_id' => $intent->request_id, 'provider_event_id' => $event->id,
            'policy_version' => $payload['policy_version'], 'lines' => $lines, 'recorded_at' => $recordedAt->toIso8601String()];
        $entry->forceFill(['wallet_id' => $intent->wallet_id, 'kind' => $journal->kind, 'source_type' => 'wallet_deposit_intent', 'source_id' => $intent->id,
            'currency' => 'RWF', 'payload' => $entryPayload, 'sha256' => hash('sha256', $this->json->encode($entryPayload)), 'created_at' => $recordedAt])->save();
        foreach ($journal->lines as $line) {
            (new LedgerLine)->forceFill(['entry_id' => $entry->id, 'account_id' => $this->account($line->account, $intent->wallet_id),
                'direction' => $line->direction, 'amount' => $line->amount->amount(), 'created_at' => $recordedAt])->save();
        }
        DB::statement('SET CONSTRAINTS ledger_entries_balanced IMMEDIATE');
        DB::statement('SET CONSTRAINTS ledger_entries_balanced DEFERRED');
        $credit = new WalletDepositCredit;
        $credit->id = strtolower((string) Str::ulid());
        $creditPayload = ['receipt_id' => $credit->id, 'intent_id' => $intent->id, 'wallet_id' => $intent->wallet_id, 'ledger_entry_id' => $entry->id,
            'provider_event_id' => $event->id, 'operation_id' => $intent->operation_id, 'request_id' => $intent->request_id, 'amount' => $intent->credited,
            'policy_version' => $payload['policy_version'], 'recorded_at' => $recordedAt->toIso8601String()];
        $credit->forceFill(['intent_id' => $intent->id, 'wallet_id' => $intent->wallet_id, 'ledger_entry_id' => $entry->id, 'provider_event_id' => $event->id,
            'operation_id' => $intent->operation_id, 'request_id' => $intent->request_id, 'amount' => $intent->credited, 'payload' => $creditPayload,
            'sha256' => hash('sha256', $this->json->encode($creditPayload)), 'created_at' => $recordedAt])->save();
    }

    /** A ledger account's id, created on first use. Investor buckets belong to the wallet; the rest are platform accounts. */
    private function account(string $kind, string $walletId): string
    {
        $owned = str_starts_with($kind, 'investor_');
        DB::insert('INSERT INTO ledger_accounts (id, wallet_id, kind, currency, created_at) VALUES (?, ?, ?, \'RWF\', now())
            ON CONFLICT '.($owned ? '(wallet_id, kind) WHERE wallet_id IS NOT NULL' : '(kind) WHERE wallet_id IS NULL').' DO NOTHING',
            [strtolower((string) Str::ulid()), $owned ? $walletId : null, $kind]);

        return (string) DB::table('ledger_accounts')->where('kind', $kind)
            ->when($owned, fn ($query) => $query->where('wallet_id', $walletId), fn ($query) => $query->whereNull('wallet_id'))->value('id');
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
