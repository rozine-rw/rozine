<?php

declare(strict_types=1);

namespace App\Infrastructure\Wallet;

use App\Application\Business\Contracts\BusinessNoticeAudience;
use App\Application\Business\WithBusinessAuthority;
use App\Application\Identity\Contracts\PartyNotices;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Application\Operations\Contracts\OperationJournal;
use App\Application\Wallet\Contracts\BusinessWithdrawals;
use App\Application\Wallet\Contracts\WithdrawalProvider;
use App\Application\Wallet\SyntheticDepositAdmission;
use App\Application\Wallet\SyntheticWalletGuard;
use App\Application\Wallet\VerifiedWithdrawalEvent;
use App\Application\Wallet\WithdrawalInstruction;
use App\Domain\Identity\PartyNoticeKind;
use App\Domain\Operations\CommandRejection;
use App\Domain\Operations\OperationResult;
use App\Domain\Wallet\DepositOutcome;
use App\Domain\Wallet\JournalEntry;
use App\Domain\Wallet\WalletBalance;
use App\Domain\Wallet\WalletMoney;
use App\Domain\Wallet\WithdrawalTerms;
use App\Models\BusinessFundingMethod;
use App\Models\BusinessWallet;
use App\Models\BusinessWithdrawal;
use App\Models\BusinessWithdrawalDispatch;
use App\Models\BusinessWithdrawalEvent;
use App\Models\LedgerEntry;
use App\Models\LedgerLine;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * The Business withdrawal adapter (APP-B01, LED-R04). Lock order: the Business profile and the actor's
 * current authority, then the Business wallet, then the withdrawal. Every journal entry is keyed by the
 * acting Party and targets the Business wallet, as deposits are. A withdrawal writes itself, its hold on
 * the available cash and its queued dispatch in one transaction; only a verified provider outcome pays
 * it out or returns it, each at most once.
 */
final class EloquentBusinessWithdrawals implements BusinessWithdrawals
{
    public function __construct(private WithBusinessAuthority $authority, private OperationJournal $journal, private CanonicalJson $json,
        private WithdrawalProvider $payouts, private SyntheticWalletGuard $guard, private SyntheticDepositAdmission $admission,
        private PartyNotices $notices, private BusinessNoticeAudience $audience) {}

    /**
     * @param  array{currency: string, amount: string}  $amount
     * @return array<string, mixed>
     */
    public function withdraw(int $userId, int $contextRevision, string $businessId, string $requestId, array $amount, string $methodId): array
    {
        return $this->authority->handle($userId, $contextRevision, $businessId, 'business.wallet.withdraw', null,
            function (array $business, array $identity) use ($userId, $contextRevision, $businessId, $requestId, $amount, $methodId): array {
                $partyId = (string) ($identity['party']['id'] ?? throw new CommandRejection('BUSINESS_NOT_FOUND', 404));
                $wallet = $this->lockWallet($business['id']);
                $input = ['identity_context_revision' => $contextRevision, 'amount' => ['currency' => $amount['currency'], 'amount' => $amount['amount']], 'method_id' => $methodId];

                return $this->journal->execute('party:'.$partyId, $userId, 'business.wallet.withdraw', $requestId, 'business_wallet', $wallet->id, $input,
                    function () use ($userId, $contextRevision, $businessId): void {
                        $this->authority->handle($userId, $contextRevision, $businessId, 'business.wallet.withdraw', null, fn (): bool => true);
                    },
                    function (string $operationId) use ($wallet, $partyId, $userId, $requestId, $amount, $methodId): OperationResult {
                        $data = ['wallet_id' => $wallet->id];
                        $terms = $this->terms() ?? throw new CommandRejection('POLICY_INPUT_REQUIRED', data: $data);
                        if (! $this->admission->admitsBusiness()) {
                            throw new CommandRejection('SYNTHETIC_BUSINESS_FUNDING_OFF', 403, data: $data);
                        }
                        $method = BusinessFundingMethod::query()->where('business_id', $wallet->business_id)->whereKey(strtolower($methodId))
                            ->whereNotNull('verified_at')->where('verified_at', '<=', now())->whereNull('revoked_at')->first()
                            ?? throw new CommandRejection('WITHDRAWAL_METHOD_UNVERIFIED', data: $data);
                        $money = $amount['currency'] === 'RWF' ? WalletMoney::fromInput($amount['amount']) : null;
                        $error = $money === null ? 'Enter a whole RWF amount of at most 12 digits.' : $terms->boundsError($money, $this->bucket($wallet->id, 'business_available'));
                        if ($money === null || $error !== null) {
                            throw new CommandRejection('VALIDATION_FAILED', 422, fieldErrors: ['amount' => [(string) $error]], data: $data);
                        }
                        $withdrawal = $this->record($wallet, $partyId, $method, $terms, $money, $operationId, $requestId, $userId);

                        return new OperationResult('WITHDRAWAL_RECORDED', [...$data, 'withdrawal_id' => $withdrawal->id,
                            'receipt' => $this->requestReceipt($withdrawal)], 1, policyVersion: WithdrawalTerms::VERSION);
                    });
            });
    }

    /** @return array<string, mixed> */
    public function findWithdrawal(int $userId, int $contextRevision, string $businessId, string $requestId): array
    {
        return $this->authority->handle($userId, $contextRevision, $businessId, 'business.view', null,
            function (array $business, array $identity) use ($requestId): array {
                $partyId = (string) ($identity['party']['id'] ?? throw new CommandRejection('OPERATION_NOT_FOUND', 404));
                $walletId = BusinessWallet::query()->where('business_id', $business['id'])->value('id');

                return $this->journal->find('party:'.$partyId, 'business.wallet.withdraw', $requestId, function (string $type, string $id) use ($walletId): void {
                    ($type === 'business_wallet' && $id === $walletId) || throw new CommandRejection('OPERATION_NOT_FOUND', 404);
                });
            });
    }

    public function view(?string $walletId, bool $hasMethod, bool $quoting, ?string $amount, ?string $receipt): array
    {
        $terms = $this->terms();
        $opened = $walletId === null || $receipt === null ? [] : $this->withdrawals($walletId, strtolower($receipt));

        return ['withdrawal' => $terms === null ? null : ['version' => WithdrawalTerms::VERSION, 'minimum' => $terms->minimum()->money(),
            'maximum' => $terms->maximum()->money(), 'charge' => $terms->providerCharge->money(),
            'quote' => $quoting ? $this->quote($terms, $walletId, $hasMethod, $amount) : null],
            'withdrawals' => $walletId === null ? [] : $this->withdrawals($walletId),
            'pending' => ($walletId === null ? WalletMoney::zero() : $this->bucket($walletId, 'business_withdrawing'))->money(),
            'receipt' => $opened === [] ? null : ['type' => 'withdrawal', 'withdrawal' => $opened[0]]];
    }

    public function offered(): bool
    {
        return $this->terms() !== null;
    }

    /** @return array{disposition: string, state: string, settled: bool, replayed: bool} */
    public function applyOutcome(VerifiedWithdrawalEvent $event, string $environment): array
    {
        return DB::transaction(function () use ($event, $environment): array {
            DB::select('SELECT pg_advisory_xact_lock(hashtextextended(?, 0))', ['wallet-provider-event|'.$event->provider.'|'.$event->eventId]);
            $digest = hash('sha256', $event->providerReference);
            $walletId = BusinessWithdrawal::query()->where('provider', $event->provider)->where('provider_reference_sha256', $digest)->value('wallet_id')
                ?? throw new CommandRejection('WITHDRAWAL_REFERENCE_UNKNOWN', 404);
            $wallet = BusinessWallet::query()->whereKey($walletId)->lockForUpdate()->sole();
            $withdrawal = BusinessWithdrawal::query()->where('provider_reference_sha256', $digest)->lockForUpdate()->sole();
            $this->payload($withdrawal);
            $existing = BusinessWithdrawalEvent::query()->where('provider', $event->provider)->where('provider_event_id', $event->eventId)->get();
            $replay = $existing->firstWhere('content_sha256', $event->contentSha256);
            if ($replay !== null) {
                return ['disposition' => $replay->disposition, 'state' => $this->state($withdrawal->id), 'settled' => false, 'replayed' => true];
            }
            // Provider event keys are one namespace across deposits and withdrawals of both owners.
            $consumedElsewhere = DB::table('wallet_provider_events')->where('provider', $event->provider)->where('provider_event_id', $event->eventId)->exists()
                || DB::table('business_provider_events')->where('provider', $event->provider)->where('provider_event_id', $event->eventId)->exists()
                || DB::table('wallet_withdrawal_events')->where('provider', $event->provider)->where('provider_event_id', $event->eventId)->exists();
            $current = $this->state($withdrawal->id);
            $outcome = DepositOutcome::transition($current, $event->state);
            $disposition = match (true) {
                $existing->isNotEmpty() || $consumedElsewhere => 'key_conflict',
                $event->environment !== $environment || $event->currency !== $withdrawal->currency || $event->amount !== $withdrawal->received => 'mismatch',
                default => $outcome->disposition,
            };
            $record = new BusinessWithdrawalEvent;
            $record->forceFill(['provider' => $event->provider, 'provider_event_id' => $event->eventId, 'withdrawal_id' => $withdrawal->id,
                'content_sha256' => $event->contentSha256, 'state' => $event->state, 'amount' => $event->amount, 'currency' => $event->currency,
                'environment' => $event->environment, 'observed_at' => $event->observedAt, 'disposition' => $disposition, 'evidence' => $event->evidence,
                'created_at' => now('UTC')])->save();
            $settled = $disposition === 'applied' && DepositOutcome::isFinal($outcome->state);
            if ($settled) {
                $paid = $outcome->state === 'succeeded';
                $this->post($withdrawal, $paid ? 'business_withdrawal_payout' : 'business_withdrawal_return', $record);
                $method = BusinessFundingMethod::query()->whereKey($withdrawal->method_id)->sole();
                $this->notices->record($this->audience->entityParty($wallet->business_id),
                    $paid ? PartyNoticeKind::BusinessWithdrawalSent : PartyNoticeKind::BusinessWithdrawalReturned,
                    ['amount' => $withdrawal->amount, 'currency' => $withdrawal->currency, 'received' => $withdrawal->received,
                        'account' => $method->label.' '.$method->masked], 'business-withdrawal:'.$withdrawal->id);
            }

            return ['disposition' => $disposition, 'state' => $disposition === 'applied' ? $outcome->state : $current, 'settled' => $settled, 'replayed' => false];
        }, 3);
    }

    /** @return list<WithdrawalInstruction> */
    public function claim(?string $withdrawalId, int $limit, bool $resendInterrupted, string $environment): array
    {
        return DB::transaction(function () use ($withdrawalId, $limit, $resendInterrupted, $environment): array {
            $queued = $this->queued($withdrawalId)->orderBy('id')->limit($limit)->lock('FOR UPDATE SKIP LOCKED')->pluck('withdrawal_id')->all();
            $interrupted = $resendInterrupted && count($queued) < $limit ? $this->interrupted($withdrawalId)
                ->orderBy('id')->limit($limit - count($queued))->lock('FOR UPDATE SKIP LOCKED')->pluck('withdrawal_id')->all() : [];
            foreach ($queued as $queuedWithdrawal) {
                (new BusinessWithdrawalDispatch)->forceFill(['withdrawal_id' => $queuedWithdrawal, 'phase' => 'claimed', 'created_at' => now('UTC')])->save();
            }

            return array_values(BusinessWithdrawal::query()->whereIn('id', [...$queued, ...$interrupted])->orderBy('id')->get()
                ->map(fn (BusinessWithdrawal $withdrawal): WithdrawalInstruction => new WithdrawalInstruction($withdrawal->id, $withdrawal->operation_id,
                    $withdrawal->provider_reference, $withdrawal->method_id, $this->payload($withdrawal)['received'], $withdrawal->currency,
                    $environment))->all());
        }, 3);
    }

    public function claimable(bool $resendInterrupted): int
    {
        return $this->queued(null)->count() + ($resendInterrupted ? $this->interrupted(null)->count() : 0);
    }

    public function recordDispatch(string $withdrawalId, bool $acknowledged): void
    {
        DB::insert("INSERT INTO business_withdrawal_dispatches (id, withdrawal_id, phase, created_at) VALUES (?, ?, ?, now())
            ON CONFLICT (withdrawal_id) WHERE phase IN ('acknowledged', 'unacknowledged') DO NOTHING",
            [strtolower((string) Str::ulid()), $withdrawalId, $acknowledged ? 'acknowledged' : 'unacknowledged']);
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
     * Queued dispatches no claim has taken yet.
     *
     * @return Builder<BusinessWithdrawalDispatch>
     */
    private function queued(?string $withdrawalId): Builder
    {
        return BusinessWithdrawalDispatch::query()->where('phase', 'queued')
            ->whereNotIn('withdrawal_id', BusinessWithdrawalDispatch::query()->where('phase', 'claimed')->select('withdrawal_id'))
            ->when($withdrawalId !== null, fn (Builder $query) => $query->where('withdrawal_id', $withdrawalId));
    }

    /**
     * Claims still without a send outcome five minutes on: their send was interrupted.
     *
     * @return Builder<BusinessWithdrawalDispatch>
     */
    private function interrupted(?string $withdrawalId): Builder
    {
        return BusinessWithdrawalDispatch::query()->where('phase', 'claimed')
            ->whereNotIn('withdrawal_id', BusinessWithdrawalDispatch::query()->whereIn('phase', ['acknowledged', 'unacknowledged'])->select('withdrawal_id'))
            ->where('created_at', '<', now()->subMinutes(5))->when($withdrawalId !== null, fn (Builder $query) => $query->where('withdrawal_id', $withdrawalId));
    }

    /** The withdrawal terms, while a payout provider is available here; with none, withdrawals are not offered. */
    private function terms(): ?WithdrawalTerms
    {
        return $this->guard->allowed() ? new WithdrawalTerms(WalletMoney::of($this->payouts->charge())) : null;
    }

    /** The Business wallet, created on first use, locked for the rest of the transaction. */
    private function lockWallet(string $businessId): BusinessWallet
    {
        DB::insert("INSERT INTO business_wallets (id, business_id, currency, created_at) VALUES (?, ?, 'RWF', now()) ON CONFLICT (business_id) DO NOTHING",
            [strtolower((string) Str::ulid()), $businessId]);

        return BusinessWallet::query()->where('business_id', $businessId)->lockForUpdate()->sole();
    }

    /** One of the wallet's buckets, from its postings. */
    private function bucket(string $walletId, string $kind): WalletMoney
    {
        $sums = DB::table('ledger_lines')->join('ledger_accounts', 'ledger_accounts.id', '=', 'ledger_lines.account_id')
            ->where('ledger_accounts.wallet_id', $walletId)->where('ledger_accounts.kind', $kind)
            ->selectRaw("coalesce(sum(ledger_lines.amount) FILTER (WHERE ledger_lines.direction = 'credit'), 0)::text AS credits,
                coalesce(sum(ledger_lines.amount) FILTER (WHERE ledger_lines.direction = 'debit'), 0)::text AS debits")->first();

        return WalletBalance::bucket(WalletMoney::of((string) ($sums->credits ?? '0')), WalletMoney::of((string) ($sums->debits ?? '0')));
    }

    /** Records the withdrawal with its hold and its queued dispatch; an overdraw surfaces here as a refusal. */
    private function record(BusinessWallet $wallet, string $partyId, BusinessFundingMethod $method, WithdrawalTerms $terms, WalletMoney $amount,
        string $operationId, string $requestId, int $userId): BusinessWithdrawal
    {
        $reference = 'syw_'.bin2hex(random_bytes(20));
        $withdrawal = new BusinessWithdrawal;
        $withdrawal->id = strtolower((string) Str::ulid());
        $recordedAt = now('UTC')->toImmutable()->startOfSecond();
        $payload = ['withdrawal_id' => $withdrawal->id, 'wallet_id' => $wallet->id, 'business_id' => $wallet->business_id, 'party_id' => $partyId,
            'operation_id' => $operationId, 'request_id' => strtolower($requestId), 'method_id' => $method->id, 'terms_version' => WithdrawalTerms::VERSION,
            'amount' => $amount->amount(), 'provider_charge' => $terms->providerCharge->amount(), 'received' => $terms->received($amount)->amount(),
            'currency' => 'RWF', 'provider' => $this->payouts->name(), 'actor_user_id' => $userId, 'recorded_at' => $recordedAt->toIso8601String()];
        $withdrawal->forceFill(['wallet_id' => $wallet->id, 'business_id' => $wallet->business_id, 'party_id' => $partyId, 'method_id' => $method->id,
            'operation_id' => $operationId, 'request_id' => $payload['request_id'], 'terms_version' => WithdrawalTerms::VERSION, 'amount' => $payload['amount'],
            'provider_charge' => $payload['provider_charge'], 'received' => $payload['received'], 'currency' => 'RWF', 'provider' => $payload['provider'],
            'provider_reference' => $reference, 'provider_reference_sha256' => hash('sha256', $reference), 'payload' => $payload,
            'sha256' => hash('sha256', $this->json->encode($payload)), 'created_at' => $recordedAt])->save();
        $this->post($withdrawal, 'business_withdrawal_hold', null);
        (new BusinessWithdrawalDispatch)->forceFill(['withdrawal_id' => $withdrawal->id, 'phase' => 'queued', 'created_at' => $recordedAt])->save();

        return $withdrawal;
    }

    /** Posts one balanced withdrawal movement of the withdrawal's whole amount and seals it at once. */
    private function post(BusinessWithdrawal $withdrawal, string $kind, ?BusinessWithdrawalEvent $event): LedgerEntry
    {
        $journal = JournalEntry::withdrawal($kind, WalletMoney::of($withdrawal->amount));
        $recordedAt = now('UTC')->toImmutable()->startOfSecond();
        $entry = new LedgerEntry;
        $entry->id = strtolower((string) Str::ulid());
        $lines = array_map(fn ($line): array => ['account' => $line->account, 'direction' => $line->direction, 'amount' => $line->amount->amount()], $journal->lines);
        $entryPayload = ['entry_id' => $entry->id, 'wallet_id' => $withdrawal->wallet_id, 'kind' => $kind, 'source_type' => 'business_withdrawal',
            'source_id' => $withdrawal->id, 'operation_id' => $withdrawal->operation_id, 'request_id' => $withdrawal->request_id,
            'provider_event_id' => $event?->id, 'policy_version' => $withdrawal->terms_version, 'lines' => $lines, 'recorded_at' => $recordedAt->toIso8601String()];
        $entry->forceFill(['wallet_id' => $withdrawal->wallet_id, 'kind' => $kind, 'source_type' => 'business_withdrawal', 'source_id' => $withdrawal->id,
            'origin_operation_id' => $withdrawal->operation_id, 'currency' => 'RWF', 'payload' => $entryPayload,
            'sha256' => hash('sha256', $this->json->encode($entryPayload)), 'created_at' => $recordedAt])->save();
        foreach ($journal->lines as $line) {
            (new LedgerLine)->forceFill(['entry_id' => $entry->id, 'account_id' => $this->account($line->account, $withdrawal->wallet_id),
                'direction' => $line->direction, 'amount' => $line->amount->amount(), 'created_at' => $recordedAt])->save();
        }
        // Surface an unbalanced or overdrawn movement here rather than at commit; the balance check seals the entry.
        DB::statement('SET CONSTRAINTS ledger_entries_balanced, ledger_lines_entry_balanced IMMEDIATE');
        DB::statement('SET CONSTRAINTS ledger_entries_balanced, ledger_lines_entry_balanced DEFERRED');

        return $entry;
    }

    /** A ledger account's id, created on first use. The Business buckets belong to the wallet; clearing is a platform account. */
    private function account(string $kind, string $walletId): string
    {
        $owned = $kind !== 'withdrawal_clearing';
        DB::insert('INSERT INTO ledger_accounts (id, wallet_id, kind, currency, created_at) VALUES (?, ?, ?, \'RWF\', now())
            ON CONFLICT '.($owned ? '(wallet_id, kind) WHERE wallet_id IS NOT NULL' : '(kind) WHERE wallet_id IS NULL').' DO NOTHING',
            [strtolower((string) Str::ulid()), $owned ? $walletId : null, $kind]);

        return (string) DB::table('ledger_accounts')->where('kind', $kind)
            ->when($owned, fn ($query) => $query->where('wallet_id', $walletId), fn ($query) => $query->whereNull('wallet_id'))->value('id');
    }

    /** The withdrawal's provider state: its applied final outcome, else its latest applied outcome, else pending. */
    private function state(string $withdrawalId): string
    {
        $applied = BusinessWithdrawalEvent::query()->where('withdrawal_id', $withdrawalId)->where('disposition', 'applied');

        return (clone $applied)->whereIn('state', ['succeeded', 'failed'])->value('state')
            ?? $applied->orderByDesc('created_at')->orderByDesc('id')->value('state') ?? 'pending';
    }

    /**
     * The withdrawal's sealed facts; a row whose payload does not match its hash and columns is an
     * integrity failure, never a readable withdrawal.
     *
     * @return array<string, mixed>
     */
    private function payload(BusinessWithdrawal $withdrawal): array
    {
        $payload = $withdrawal->payload;
        $columns = ['withdrawal_id' => $withdrawal->id, 'wallet_id' => $withdrawal->wallet_id, 'business_id' => $withdrawal->business_id,
            'party_id' => $withdrawal->party_id, 'operation_id' => $withdrawal->operation_id, 'request_id' => $withdrawal->request_id,
            'method_id' => $withdrawal->method_id, 'terms_version' => $withdrawal->terms_version, 'amount' => $withdrawal->amount,
            'provider_charge' => $withdrawal->provider_charge, 'received' => $withdrawal->received, 'provider' => $withdrawal->provider];
        if (! hash_equals($withdrawal->sha256, hash('sha256', $this->json->encode($payload))) || array_intersect_key($payload, $columns) !== $columns) {
            throw new RuntimeException('WALLET_WITHDRAWAL_INTEGRITY_FAILED');
        }

        return $payload;
    }

    /**
     * WITHDRAWAL_RECORDED: the request and its hold were durably recorded, not that any money left.
     *
     * @return array<string, mixed>
     */
    private function requestReceipt(BusinessWithdrawal $withdrawal): array
    {
        return ['receipt_id' => $withdrawal->id, 'operation_id' => $withdrawal->operation_id, 'request_id' => $withdrawal->request_id,
            'code' => 'WITHDRAWAL_RECORDED', 'recorded_at' => $this->payload($withdrawal)['recorded_at'], 'amount' => WalletMoney::of($withdrawal->amount)->money(),
            'units' => null, 'reference' => 'RZX-'.strtoupper(substr($withdrawal->id, -10)), 'revision' => 1,
            'policy_version' => $withdrawal->terms_version, 'disclosure_version' => null];
    }

    /**
     * WITHDRAWAL_PAID or WITHDRAWAL_RETURNED: the posting that ended the withdrawal, against the request that opened it.
     *
     * @return array<string, mixed>
     */
    private function settledReceipt(BusinessWithdrawal $withdrawal, LedgerEntry $entry): array
    {
        return ['receipt_id' => $entry->id, 'operation_id' => $withdrawal->operation_id, 'request_id' => $withdrawal->request_id,
            'code' => $entry->kind === 'business_withdrawal_payout' ? 'WITHDRAWAL_PAID' : 'WITHDRAWAL_RETURNED', 'recorded_at' => (string) $entry->payload['recorded_at'],
            'amount' => WalletMoney::of($withdrawal->amount)->money(), 'units' => null, 'reference' => 'RZW-'.strtoupper(substr($entry->id, -10)), 'revision' => 1,
            'policy_version' => $withdrawal->terms_version, 'disclosure_version' => null];
    }

    /**
     * The latest withdrawals, unsettled first, each with its request receipt and, once a verified
     * outcome ended it, the receipt of the payout or the return.
     *
     * @return list<array<string, mixed>>
     */
    private function withdrawals(string $walletId, ?string $only = null): array
    {
        $withdrawals = BusinessWithdrawal::query()->where('wallet_id', $walletId)->when($only !== null, fn ($query) => $query->whereKey($only))
            ->orderByDesc('id')->limit(20)->get();
        $endings = LedgerEntry::query()->where('source_type', 'business_withdrawal')->whereIn('source_id', $withdrawals->pluck('id'))
            ->whereIn('kind', ['business_withdrawal_payout', 'business_withdrawal_return'])->get()->keyBy('source_id');
        $methods = BusinessFundingMethod::query()->whereIn('id', $withdrawals->pluck('method_id'))->get()->keyBy('id');
        $listed = $withdrawals->map(function (BusinessWithdrawal $withdrawal) use ($endings, $methods): array {
            $ending = $endings->get($withdrawal->id);
            $method = $methods->get($withdrawal->method_id) ?? throw new RuntimeException('WALLET_WITHDRAWAL_INTEGRITY_FAILED');

            return ['id' => $withdrawal->id, 'request_id' => $withdrawal->request_id, 'amount' => WalletMoney::of($withdrawal->amount)->money(),
                'charge' => WalletMoney::of($withdrawal->provider_charge)->money(), 'received' => WalletMoney::of($withdrawal->received)->money(),
                'method' => ['id' => $method->id, 'kind' => $method->kind, 'label' => $method->label, 'masked' => $method->masked],
                'created_at' => $this->payload($withdrawal)['recorded_at'], 'state' => $this->state($withdrawal->id),
                'request_receipt' => $this->requestReceipt($withdrawal),
                'settled_receipt' => $ending === null ? null : $this->settledReceipt($withdrawal, $ending)];
        });

        return array_values($listed->sortBy(fn (array $withdrawal): int => $withdrawal['settled_receipt'] === null ? 0 : 1)->values()->all());
    }

    /**
     * The withdrawal quote for an entered amount: the provider's charge, what reaches the account,
     * and why the amount would be refused, if it would.
     *
     * @return array<string, mixed>|null
     */
    private function quote(WithdrawalTerms $terms, ?string $walletId, bool $hasMethod, ?string $amount): ?array
    {
        if ($amount === null || $amount === '') {
            return null;
        }
        $money = WalletMoney::fromInput(ltrim($amount, '0'));
        if ($money === null) {
            return ['amount' => WalletMoney::zero()->money(), 'charge' => $terms->providerCharge->money(), 'received' => WalletMoney::zero()->money(), 'refusal' => 'VALIDATION_FAILED'];
        }
        $available = $walletId === null ? WalletMoney::zero() : $this->bucket($walletId, 'business_available');
        $refusal = match (true) {
            ! $hasMethod => 'WITHDRAWAL_METHOD_UNVERIFIED',
            $terms->boundsError($money, $available) === null => null,
            $money->compareTo($available) > 0 && $money->compareTo($terms->maximum()) <= 0 && $money->compareTo($terms->minimum()) >= 0 => 'INSUFFICIENT_AVAILABLE_FUNDS',
            default => 'VALIDATION_FAILED',
        };

        return ['amount' => $money->money(), 'charge' => $terms->providerCharge->money(),
            'received' => ($money->compareTo($terms->providerCharge) > 0 ? $terms->received($money) : WalletMoney::zero())->money(), 'refusal' => $refusal];
    }
}
