<?php

declare(strict_types=1);

namespace App\Infrastructure\Wallet;

use App\Application\Business\WithBusinessAuthority;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Application\Operations\Contracts\OperationJournal;
use App\Application\Wallet\Contracts\BusinessWalletStore;
use App\Application\Wallet\Contracts\DepositProvider;
use App\Application\Wallet\DepositInstruction;
use App\Application\Wallet\SyntheticWalletGuard;
use App\Application\Wallet\VerifiedDepositEvent;
use App\Domain\Operations\CommandRejection;
use App\Domain\Operations\OperationResult;
use App\Domain\Wallet\DepositOutcome;
use App\Domain\Wallet\DepositPolicyTerms;
use App\Domain\Wallet\JournalEntry;
use App\Domain\Wallet\WalletBalance;
use App\Domain\Wallet\WalletMoney;
use App\Models\BusinessDepositCredit;
use App\Models\BusinessDepositDispatch;
use App\Models\BusinessDepositIntent;
use App\Models\BusinessFundingMethod;
use App\Models\BusinessProviderEvent;
use App\Models\BusinessWallet;
use App\Models\DepositPolicy;
use App\Models\LedgerEntry;
use App\Models\LedgerLine;
use Closure;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * The Business servicing wallet adapter (S4-A1). Lock order: the Business profile and the actor's
 * current authority, then the Business wallet, then an intent. Every journal entry is keyed by the
 * acting Party, never by the Business, and targets the Business wallet. A deposit writes its intent,
 * queued dispatch row and receipt in one transaction and never touches the ledger.
 *
 * @phpstan-import-type Business from \App\Application\Business\Contracts\BusinessAuthorityStore
 *
 * @phpstan-type Policy array{model: DepositPolicy, terms: DepositPolicyTerms}
 */
final class EloquentBusinessWalletStore implements BusinessWalletStore
{
    public function __construct(private WithBusinessAuthority $authority, private OperationJournal $journal, private CanonicalJson $json,
        private DepositProvider $provider, private SyntheticWalletGuard $guard) {}

    /**
     * @param  array{kind?: string|null, amount?: string|null, movement?: string|null, before?: string|null, receipt?: string|null}  $query
     * @return array<string, mixed>
     */
    public function page(int $userId, int $contextRevision, string $businessId, array $query): array
    {
        return $this->authority->handle($userId, $contextRevision, $businessId, 'business.view', null,
            function (array $business, array $identity) use ($query): array {
                $wallet = BusinessWallet::query()->where('business_id', $business['id'])->first();
                $policy = $this->applicablePolicy();
                $methods = $this->verifiedMethods($business['id']);
                $canDeposit = $policy !== null && $methods->isNotEmpty() && $this->permits($business, (string) ($identity['party']['id'] ?? ''));
                $movement = ($query['movement'] ?? null) === 'internal' ? 'internal' : 'external';
                $history = $wallet === null || $movement === 'internal' ? ['items' => [], 'next_before' => null] : $this->history($wallet, $query['before'] ?? null);

                return ['identity_context_revision' => $identity['context_revision'], 'allowed_actions' => $canDeposit ? ['business.wallet.deposit'] : [],
                    'business' => ['id' => $business['id'], 'name' => $business['profile']['name']], 'wallet' => $this->balances($wallet),
                    'funding' => ['kind' => ($query['kind'] ?? null) === 'deposit' ? 'deposit' : null, 'policy' => $policy === null ? null : [
                        'version' => $policy['terms']->version, 'synthetic' => $policy['terms']->synthetic, 'fee' => $policy['terms']->fee->money(),
                        'minimum' => $policy['terms']->minimum?->money(), 'maximum' => $policy['terms']->maximum?->money()],
                        'methods' => $methods->map(fn (BusinessFundingMethod $method): array => $this->method($method))->values()->all(), 'picks' => [],
                        'quote' => $this->quote($policy, $methods->isNotEmpty(), $query['kind'] ?? null, $query['amount'] ?? null)],
                    'deposits' => $wallet === null ? [] : $this->deposits($wallet), 'history' => ['movement' => $movement, ...$history],
                    'receipt' => $wallet === null ? null : $this->openedReceipt($wallet, $query['receipt'] ?? null)];
            });
    }

    /**
     * @param  array{currency: string, amount: string}  $amount
     * @return array<string, mixed>
     */
    public function deposit(int $userId, int $contextRevision, string $businessId, string $requestId, array $amount, string $methodId): array
    {
        return $this->authority->handle($userId, $contextRevision, $businessId, 'business.wallet.deposit', null,
            function (array $business, array $identity) use ($userId, $contextRevision, $businessId, $requestId, $amount, $methodId): array {
                $partyId = (string) ($identity['party']['id'] ?? throw new CommandRejection('BUSINESS_NOT_FOUND', 404));
                $wallet = $this->lockWallet($business['id']);
                $input = ['identity_context_revision' => $contextRevision, 'amount' => ['currency' => $amount['currency'], 'amount' => $amount['amount']], 'method_id' => $methodId];

                return $this->journal->execute('party:'.$partyId, $userId, 'business.wallet.deposit', $requestId, 'business_wallet', $wallet->id, $input,
                    function () use ($userId, $contextRevision, $businessId): void {
                        $this->authority->handle($userId, $contextRevision, $businessId, 'business.wallet.deposit', null, fn (): bool => true);
                    },
                    function (string $operationId) use ($wallet, $business, $partyId, $userId, $requestId, $amount, $methodId): OperationResult {
                        $data = ['wallet_id' => $wallet->id];
                        $policy = $this->applicablePolicy() ?? throw new CommandRejection('POLICY_INPUT_REQUIRED', data: $data);
                        $method = $this->verifiedMethods($business['id'])->firstWhere('id', $methodId)
                            ?? throw new CommandRejection('DEPOSIT_METHOD_UNVERIFIED', data: $data);
                        $money = $amount['currency'] === 'RWF' ? WalletMoney::fromInput($amount['amount']) : null;
                        $error = $money === null ? 'Enter a whole RWF amount of at most 12 digits.' : $policy['terms']->boundsError($money);
                        if ($money === null || $error !== null) {
                            throw new CommandRejection('VALIDATION_FAILED', 422, fieldErrors: ['amount' => [(string) $error]], data: $data);
                        }
                        $intent = $this->recordIntent($wallet, $partyId, $method, $policy, $money, $operationId, $requestId, $userId);

                        return new OperationResult('DEPOSIT_INTENT_RECORDED', [...$data, 'intent_id' => $intent->id,
                            'receipt' => $this->intentReceipt($intent)], 1, policyVersion: $policy['terms']->version);
                    });
            });
    }

    /** @return array<string, mixed> */
    public function findDeposit(int $userId, int $contextRevision, string $businessId, string $requestId): array
    {
        return $this->authority->handle($userId, $contextRevision, $businessId, 'business.view', null,
            function (array $business, array $identity) use ($requestId): array {
                $partyId = (string) ($identity['party']['id'] ?? throw new CommandRejection('OPERATION_NOT_FOUND', 404));
                $walletId = BusinessWallet::query()->where('business_id', $business['id'])->value('id');

                return $this->journal->find('party:'.$partyId, 'business.wallet.deposit', $requestId, function (string $type, string $id) use ($walletId): void {
                    ($type === 'business_wallet' && $id === $walletId) || throw new CommandRejection('OPERATION_NOT_FOUND', 404);
                });
            });
    }

    /** @return list<DepositInstruction> */
    public function claimDispatches(?string $intentId, int $limit, bool $resendInterrupted, string $environment): array
    {
        return DB::transaction(function () use ($intentId, $limit, $resendInterrupted, $environment): array {
            $claimed = BusinessDepositDispatch::query()->where('phase', 'claimed')->select('intent_id');
            $outcomes = BusinessDepositDispatch::query()->whereIn('phase', ['acknowledged', 'unacknowledged'])->select('intent_id');
            $queued = BusinessDepositDispatch::query()->where('phase', 'queued')->whereNotIn('intent_id', $claimed)
                ->when($intentId !== null, fn ($query) => $query->where('intent_id', $intentId))
                ->orderBy('id')->limit($limit)->lock('FOR UPDATE SKIP LOCKED')->pluck('intent_id')->all();
            $interrupted = $resendInterrupted ? BusinessDepositDispatch::query()->where('phase', 'claimed')->whereNotIn('intent_id', $outcomes)
                ->where('created_at', '<', now()->subMinutes(5))->when($intentId !== null, fn ($query) => $query->where('intent_id', $intentId))
                ->orderBy('id')->limit($limit)->lock('FOR UPDATE SKIP LOCKED')->pluck('intent_id')->all() : [];
            foreach ($queued as $queuedIntent) {
                (new BusinessDepositDispatch)->forceFill(['intent_id' => $queuedIntent, 'phase' => 'claimed', 'created_at' => now('UTC')])->save();
            }

            return array_values(BusinessDepositIntent::query()->whereIn('id', [...$queued, ...$interrupted])->orderBy('id')->get()
                ->map(fn (BusinessDepositIntent $intent): DepositInstruction => new DepositInstruction($intent->id, $intent->operation_id,
                    $intent->provider_reference, $this->intentPayload($intent)['amount'], $intent->currency, $environment))->all());
        }, 3);
    }

    public function recordDispatch(string $intentId, bool $acknowledged): void
    {
        DB::insert("INSERT INTO business_deposit_dispatches (id, intent_id, phase, created_at) VALUES (?, ?, ?, now())
            ON CONFLICT (intent_id) WHERE phase IN ('acknowledged', 'unacknowledged') DO NOTHING",
            [strtolower((string) Str::ulid()), $intentId, $acknowledged ? 'acknowledged' : 'unacknowledged']);
    }

    /** @return array{disposition: string, state: string, credited: bool, replayed: bool} */
    public function applyOutcome(VerifiedDepositEvent $event, string $environment): array
    {
        return DB::transaction(function () use ($event, $environment): array {
            DB::select('SELECT pg_advisory_xact_lock(hashtextextended(?, 0))', ['wallet-provider-event|'.$event->provider.'|'.$event->eventId]);
            $intentId = DB::table('provider_references')->where('provider', $event->provider)
                ->where('reference_sha256', hash('sha256', $event->providerReference))->where('owner', 'business')->value('intent_id')
                ?? throw new CommandRejection('DEPOSIT_REFERENCE_UNKNOWN', 404);
            $walletId = BusinessDepositIntent::query()->whereKey($intentId)->value('wallet_id') ?? throw new CommandRejection('DEPOSIT_REFERENCE_UNKNOWN', 404);
            BusinessWallet::query()->whereKey($walletId)->lockForUpdate()->sole();
            $intent = BusinessDepositIntent::query()->whereKey($intentId)->lockForUpdate()->sole();
            $this->intentPayload($intent);
            $existing = BusinessProviderEvent::query()->where('provider', $event->provider)->where('provider_event_id', $event->eventId)->get();
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
            $record = new BusinessProviderEvent;
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

    public function afterCommit(Closure $callback): void
    {
        DB::afterCommit($callback);
    }

    public function transactionOpen(): bool
    {
        return app('db.transactions')->callbackApplicableTransactions()->isNotEmpty();
    }

    /**
     * Whether the actor's current mandate grants the deposit permission. The page never throws on a
     * missing permission: it only withholds the action.
     *
     * @param  Business  $business
     */
    private function permits(array $business, string $partyId): bool
    {
        foreach ($business['mandate']['people'] as $person) {
            if ($person['party_id'] === $partyId && in_array('business.wallet.deposit', $person['permissions'], true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Ledger-derived spendable balance. Pending deposits are recorded intents with no final outcome,
     * outside `available` because nothing has been credited for them.
     *
     * @return array<string, mixed>
     */
    private function balances(?BusinessWallet $wallet): array
    {
        $sums = $wallet === null ? collect() : DB::table('ledger_lines')->join('ledger_accounts', 'ledger_accounts.id', '=', 'ledger_lines.account_id')
            ->where('ledger_accounts.wallet_id', $wallet->id)->where('ledger_accounts.kind', 'business_available')->groupBy('ledger_lines.direction')
            ->selectRaw('ledger_lines.direction AS direction, sum(ledger_lines.amount)::text AS total')->get()
            ->mapWithKeys(fn (object $row): array => [(string) $row->direction => (string) $row->total]);
        $available = WalletBalance::bucket(WalletMoney::of($sums['credit'] ?? '0'), WalletMoney::of($sums['debit'] ?? '0'));
        $final = BusinessProviderEvent::query()->where('disposition', 'applied')->whereIn('state', ['succeeded', 'failed'])->select('intent_id');
        $pending = $wallet === null ? '0' : (string) BusinessDepositIntent::query()->where('wallet_id', $wallet->id)->whereNotIn('id', $final)
            ->selectRaw('coalesce(sum(amount), 0)::text AS pending')->value('pending');

        return ['revision' => $wallet === null ? 0 : LedgerEntry::query()->where('wallet_id', $wallet->id)->count(), 'status' => 'active',
            'restriction' => null, 'available' => $available->money(), 'pending_deposits' => WalletMoney::of($pending)->money()];
    }

    /**
     * The server's reading of an entered amount under the applicable policy. No policy, no quote.
     *
     * @param  Policy|null  $policy
     * @return array<string, mixed>|null
     */
    private function quote(?array $policy, bool $hasMethod, ?string $kind, ?string $amount): ?array
    {
        if ($kind !== 'deposit' || $amount === null || $amount === '' || $policy === null) {
            return null;
        }
        $terms = $policy['terms'];
        $money = WalletMoney::fromInput(ltrim($amount, '0'));
        if ($money === null) {
            return ['amount' => WalletMoney::zero()->money(), 'fee' => $terms->fee->money(), 'credited' => WalletMoney::zero()->money(), 'refusal' => 'VALIDATION_FAILED'];
        }
        $refusal = ! $hasMethod ? 'DEPOSIT_METHOD_UNVERIFIED' : ($terms->boundsError($money) === null ? null : 'VALIDATION_FAILED');

        return ['amount' => $money->money(), 'fee' => $terms->fee->money(),
            'credited' => ($money->compareTo($terms->fee) > 0 ? $terms->credited($money) : WalletMoney::zero())->money(), 'refusal' => $refusal];
    }

    /**
     * The latest deposits, pending and unknown first, each with its intent receipt and, only after a
     * verified success, its separate credit receipt.
     *
     * @return list<array<string, mixed>>
     */
    private function deposits(BusinessWallet $wallet, ?string $only = null): array
    {
        $intents = BusinessDepositIntent::query()->where('wallet_id', $wallet->id)->when($only !== null, fn ($query) => $query->whereKey($only))
            ->orderByDesc('id')->limit(20)->get();
        $credits = BusinessDepositCredit::query()->whereIn('intent_id', $intents->pluck('id'))->get()->keyBy('intent_id');
        $methods = BusinessFundingMethod::query()->whereIn('id', $intents->pluck('method_id'))->get()->keyBy('id');
        $deposits = $intents->map(function (BusinessDepositIntent $intent) use ($credits, $methods): array {
            $credit = $credits->get($intent->id);

            return ['id' => $intent->id, 'request_id' => $intent->request_id, 'amount' => WalletMoney::of($intent->amount)->money(),
                'method' => $this->method($methods->get($intent->method_id) ?? throw new RuntimeException('WALLET_DEPOSIT_INTEGRITY_FAILED')),
                'created_at' => $this->intentPayload($intent)['recorded_at'], 'state' => $this->currentState($intent->id),
                'intent_receipt' => $this->intentReceipt($intent), 'credit_receipt' => $credit === null ? null : $this->creditReceipt($credit)];
        });

        return array_values($deposits->sortBy(fn (array $deposit): int => DepositOutcome::isFinal($deposit['state']) ? 1 : 0)->values()->all());
    }

    /**
     * External cash movements: each credited deposit, newest first, twenty to a page.
     *
     * @return array{items: list<array<string, mixed>>, next_before: string|null}
     */
    private function history(BusinessWallet $wallet, ?string $before): array
    {
        $credits = BusinessDepositCredit::query()->where('wallet_id', $wallet->id)->when($before !== null, fn ($query) => $query->where('id', '<', $before))
            ->orderByDesc('id')->limit(21)->get();
        $page = $credits->take(20);

        return ['items' => array_values($page->map(fn (BusinessDepositCredit $credit): array => $this->historyEntry($credit))->all()),
            'next_before' => $credits->count() > 20 ? $page->last()?->id : null];
    }

    /** @return array<string, mixed> */
    private function historyEntry(BusinessDepositCredit $credit): array
    {
        $intent = BusinessDepositIntent::query()->whereKey($credit->intent_id)->sole();
        $method = BusinessFundingMethod::query()->whereKey($intent->method_id)->sole();

        return ['id' => $credit->id, 'movement' => 'external', 'kind' => 'deposit', 'direction' => 'in', 'amount' => WalletMoney::of($credit->amount)->money(),
            'psp_fee' => WalletMoney::of($intent->fee)->money(), 'counterparty' => $method->label.' '.$method->masked,
            'occurred_at' => $this->creditReceipt($credit)['recorded_at']];
    }

    /**
     * The receipt the page was asked to open, if it is this wallet's. Anyone else's id opens nothing.
     *
     * @return array<string, mixed>|null
     */
    private function openedReceipt(BusinessWallet $wallet, ?string $id): ?array
    {
        if ($id === null) {
            return null;
        }
        $deposit = $this->deposits($wallet, strtolower($id));
        if ($deposit !== []) {
            return ['type' => 'deposit', 'deposit' => $deposit[0]];
        }
        $credit = BusinessDepositCredit::query()->where('wallet_id', $wallet->id)->whereKey(strtolower($id))->first();

        return $credit === null ? null : ['type' => 'entry', 'entry' => $this->historyEntry($credit), 'receipt' => $this->creditReceipt($credit)];
    }

    /** @return array{id: string, kind: string, label: string, masked: string} */
    private function method(BusinessFundingMethod $method): array
    {
        return ['id' => $method->id, 'kind' => $method->kind, 'label' => $method->label, 'masked' => $method->masked];
    }

    /** The Business wallet, created on first use, locked for the rest of the transaction. */
    private function lockWallet(string $businessId): BusinessWallet
    {
        DB::insert("INSERT INTO business_wallets (id, business_id, currency, created_at) VALUES (?, ?, 'RWF', now()) ON CONFLICT (business_id) DO NOTHING",
            [strtolower((string) Str::ulid()), $businessId]);

        return BusinessWallet::query()->where('business_id', $businessId)->lockForUpdate()->sole();
    }

    /**
     * The current applicable deposit policy: the latest version in effect, unless it withdrew deposits.
     * Synthetic versions apply only where the synthetic provider does.
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

    /** @return Collection<int, BusinessFundingMethod> */
    private function verifiedMethods(string $businessId): Collection
    {
        return BusinessFundingMethod::query()->where('business_id', $businessId)->whereNotNull('verified_at')->where('verified_at', '<=', now())
            ->whereNull('revoked_at')->orderBy('id')->get();
    }

    /** @param Policy $policy */
    private function recordIntent(BusinessWallet $wallet, string $partyId, BusinessFundingMethod $method, array $policy, WalletMoney $amount,
        string $operationId, string $requestId, int $userId): BusinessDepositIntent
    {
        $terms = $policy['terms'];
        $reference = 'syn_'.bin2hex(random_bytes(20));
        $intent = new BusinessDepositIntent;
        $intent->id = strtolower((string) Str::ulid());
        $recordedAt = now('UTC')->toImmutable()->startOfSecond();
        $payload = ['intent_id' => $intent->id, 'wallet_id' => $wallet->id, 'business_id' => $wallet->business_id, 'party_id' => $partyId,
            'operation_id' => $operationId, 'request_id' => strtolower($requestId), 'method_id' => $method->id, 'policy_id' => $policy['model']->id,
            'policy_version' => $terms->version, 'amount' => $amount->amount(), 'fee' => $terms->fee->amount(), 'credited' => $terms->credited($amount)->amount(),
            'currency' => 'RWF', 'provider' => $this->provider->name(), 'actor_user_id' => $userId, 'recorded_at' => $recordedAt->toIso8601String()];
        $intent->forceFill(['wallet_id' => $wallet->id, 'business_id' => $wallet->business_id, 'party_id' => $partyId, 'operation_id' => $operationId,
            'request_id' => $payload['request_id'], 'method_id' => $method->id, 'policy_id' => $policy['model']->id, 'amount' => $payload['amount'],
            'fee' => $payload['fee'], 'credited' => $payload['credited'], 'currency' => 'RWF', 'provider' => $payload['provider'],
            'provider_reference' => $reference, 'provider_reference_sha256' => hash('sha256', $reference), 'payload' => $payload,
            'sha256' => hash('sha256', $this->json->encode($payload)), 'created_at' => $recordedAt])->save();
        (new BusinessDepositDispatch)->forceFill(['intent_id' => $intent->id, 'phase' => 'queued', 'created_at' => $recordedAt])->save();

        return $intent;
    }

    /**
     * The intent's provider state: its applied final outcome, else its latest applied non-final
     * outcome, else pending.
     */
    private function currentState(string $intentId): string
    {
        $applied = BusinessProviderEvent::query()->where('intent_id', $intentId)->where('disposition', 'applied');

        return (clone $applied)->whereIn('state', ['succeeded', 'failed'])->value('state')
            ?? $applied->orderByDesc('created_at')->orderByDesc('id')->value('state') ?? 'pending';
    }

    /**
     * Posts the one balanced Business credit for an applied success and its separate DEPOSIT_CREDITED
     * receipt. The receipt keeps the originating operation; the entry and event are internal provenance.
     */
    private function postCredit(BusinessDepositIntent $intent, BusinessProviderEvent $event): void
    {
        $payload = $this->intentPayload($intent);
        $journal = JournalEntry::businessDepositCredit(WalletMoney::of($intent->amount), WalletMoney::of($intent->fee));
        $recordedAt = now('UTC')->toImmutable()->startOfSecond();
        $entry = new LedgerEntry;
        $entry->id = strtolower((string) Str::ulid());
        $lines = array_map(fn ($line): array => ['account' => $line->account, 'direction' => $line->direction, 'amount' => $line->amount->amount()], $journal->lines);
        $entryPayload = ['entry_id' => $entry->id, 'wallet_id' => $intent->wallet_id, 'kind' => $journal->kind, 'source_type' => 'business_deposit_intent',
            'source_id' => $intent->id, 'operation_id' => $intent->operation_id, 'request_id' => $intent->request_id, 'provider_event_id' => $event->id,
            'policy_version' => $payload['policy_version'], 'lines' => $lines, 'recorded_at' => $recordedAt->toIso8601String()];
        $entry->forceFill(['wallet_id' => $intent->wallet_id, 'kind' => $journal->kind, 'source_type' => 'business_deposit_intent', 'source_id' => $intent->id,
            'origin_operation_id' => $intent->operation_id, 'currency' => 'RWF', 'payload' => $entryPayload,
            'sha256' => hash('sha256', $this->json->encode($entryPayload)), 'created_at' => $recordedAt])->save();
        foreach ($journal->lines as $line) {
            (new LedgerLine)->forceFill(['entry_id' => $entry->id, 'account_id' => $this->account($line->account, $intent->wallet_id),
                'direction' => $line->direction, 'amount' => $line->amount->amount(), 'created_at' => $recordedAt])->save();
        }
        $credit = new BusinessDepositCredit;
        $credit->id = strtolower((string) Str::ulid());
        $creditPayload = ['receipt_id' => $credit->id, 'intent_id' => $intent->id, 'wallet_id' => $intent->wallet_id, 'ledger_entry_id' => $entry->id,
            'provider_event_id' => $event->id, 'operation_id' => $intent->operation_id, 'request_id' => $intent->request_id, 'amount' => $intent->credited,
            'policy_version' => $payload['policy_version'], 'recorded_at' => $recordedAt->toIso8601String()];
        $credit->forceFill(['intent_id' => $intent->id, 'wallet_id' => $intent->wallet_id, 'ledger_entry_id' => $entry->id, 'provider_event_id' => $event->id,
            'operation_id' => $intent->operation_id, 'request_id' => $intent->request_id, 'amount' => $intent->credited, 'payload' => $creditPayload,
            'sha256' => hash('sha256', $this->json->encode($creditPayload)), 'created_at' => $recordedAt])->save();
        // Surface an unbalanced or unbound entry here rather than at commit; the balance check seals the entry.
        DB::statement('SET CONSTRAINTS ledger_entries_balanced, ledger_lines_entry_balanced, ledger_entries_business_deposit_credit_bound, ledger_lines_entry_business_deposit_credit_bound IMMEDIATE');
        DB::statement('SET CONSTRAINTS ledger_entries_balanced, ledger_lines_entry_balanced, ledger_entries_business_deposit_credit_bound, ledger_lines_entry_business_deposit_credit_bound DEFERRED');
    }

    /** A ledger account's id, created on first use. The Business bucket belongs to the wallet; the rest are platform accounts. */
    private function account(string $kind, string $walletId): string
    {
        $owned = $kind === 'business_available';
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
    private function intentPayload(BusinessDepositIntent $intent): array
    {
        $payload = $intent->payload;
        $columns = ['intent_id' => $intent->id, 'wallet_id' => $intent->wallet_id, 'business_id' => $intent->business_id, 'party_id' => $intent->party_id,
            'operation_id' => $intent->operation_id, 'request_id' => $intent->request_id, 'method_id' => $intent->method_id, 'policy_id' => $intent->policy_id,
            'amount' => $intent->amount, 'fee' => $intent->fee, 'credited' => $intent->credited, 'provider' => $intent->provider];
        if (! hash_equals($intent->sha256, hash('sha256', $this->json->encode($payload))) || array_intersect_key($payload, $columns) !== $columns) {
            throw new RuntimeException('WALLET_DEPOSIT_INTEGRITY_FAILED');
        }

        return $payload;
    }

    /**
     * DEPOSIT_CREDITED: its own receipt identity, with the originating command's operation and request.
     *
     * @return array<string, mixed>
     */
    private function creditReceipt(BusinessDepositCredit $credit): array
    {
        $payload = $credit->payload;
        $columns = ['receipt_id' => $credit->id, 'intent_id' => $credit->intent_id, 'wallet_id' => $credit->wallet_id, 'ledger_entry_id' => $credit->ledger_entry_id,
            'provider_event_id' => $credit->provider_event_id, 'operation_id' => $credit->operation_id, 'request_id' => $credit->request_id, 'amount' => $credit->amount];
        if (! hash_equals($credit->sha256, hash('sha256', $this->json->encode($payload))) || array_intersect_key($payload, $columns) !== $columns) {
            throw new RuntimeException('WALLET_CREDIT_INTEGRITY_FAILED');
        }

        return ['receipt_id' => $credit->id, 'operation_id' => $credit->operation_id, 'request_id' => $credit->request_id, 'code' => 'DEPOSIT_CREDITED',
            'recorded_at' => $payload['recorded_at'], 'amount' => WalletMoney::of($credit->amount)->money(), 'units' => null,
            'reference' => 'RZC-'.strtoupper(substr($credit->id, -10)), 'revision' => 1, 'policy_version' => $payload['policy_version'], 'disclosure_version' => null];
    }

    /**
     * DEPOSIT_INTENT_RECORDED: the request was durably recorded, not that any money moved.
     *
     * @return array<string, mixed>
     */
    private function intentReceipt(BusinessDepositIntent $intent): array
    {
        $payload = $this->intentPayload($intent);

        return ['receipt_id' => $intent->id, 'operation_id' => $intent->operation_id, 'request_id' => $intent->request_id,
            'code' => 'DEPOSIT_INTENT_RECORDED', 'recorded_at' => $payload['recorded_at'], 'amount' => ['currency' => 'RWF', 'amount' => $intent->amount],
            'units' => null, 'reference' => 'RZD-'.strtoupper(substr($intent->id, -10)), 'revision' => 1,
            'policy_version' => $payload['policy_version'], 'disclosure_version' => null];
    }
}
