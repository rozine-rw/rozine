<?php

declare(strict_types=1);

use App\Application\Wallet\ApplyProviderOutcome;
use App\Application\Wallet\Contracts\DepositProvider;
use App\Application\Wallet\Contracts\SyntheticEventSigner;
use App\Application\Wallet\Contracts\WalletStore;
use App\Application\Wallet\DepositInstruction;
use App\Application\Wallet\DispatchDepositIntents;
use App\Application\Wallet\VerifiedDepositEvent;
use App\Domain\Operations\CommandRejection;
use App\Models\CommandOperation;
use App\Models\LedgerAccount;
use App\Models\LedgerEntry;
use App\Models\LedgerLine;
use App\Models\WalletDepositCredit;
use App\Models\WalletDepositDispatch;
use App\Models\WalletDepositIntent;
use App\Models\WalletProviderEvent;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\InvestorWalletFixture;

/**
 * @param  array<string, string>  $overrides
 * @return array<string, string>
 */
function walletEvent(WalletDepositIntent $intent, string $state, ?string $eventId = null, array $overrides = []): array
{
    return app(SyntheticEventSigner::class)->sign([...['provider' => 'synthetic', 'event_id' => $eventId ?? 'synthetic-event-'.Str::lower(Str::random(12)),
        'reference' => $intent->provider_reference, 'state' => $state, 'amount' => $intent->amount, 'currency' => 'RWF', 'environment' => 'testing',
        'observed_at' => now('UTC')->startOfSecond()->format(DATE_ATOM)], ...$overrides]);
}

/**
 * @param  array<string, string>  $message
 * @return array{disposition: string, state: string, credited: bool, replayed: bool}
 */
function applyWalletEvent(array $message): array
{
    return app(ApplyProviderOutcome::class)->handle($message);
}

/** @return array{string, string} available balance and the clearing account's net debit */
function walletBalances(WalletDepositIntent $intent): array
{
    $sum = fn (string $kind, ?string $wallet, string $direction): int => (int) LedgerLine::query()->where('direction', $direction)
        ->whereIn('account_id', LedgerAccount::query()->where('kind', $kind)->where('wallet_id', $wallet)->select('id'))->sum('amount');

    return [(string) ($sum('investor_available', $intent->wallet_id, 'credit') - $sum('investor_available', $intent->wallet_id, 'debit')),
        (string) ($sum('deposit_clearing', null, 'debit') - $sum('deposit_clearing', null, 'credit'))];
}

/** @return array{fixture: array<string, mixed>, intent: WalletDepositIntent, result: array<string, mixed>} */
function walletIntent(string $amount = '50000', string $fee = '0'): array
{
    $fixture = InvestorWalletFixture::ready($fee);
    $result = InvestorWalletFixture::deposit($fixture, $amount);

    return ['fixture' => $fixture, 'intent' => WalletDepositIntent::query()->whereKey((string) $result['data']['intent_id'])->sole(), 'result' => $result];
}

it('credits a verified matching success exactly once with its own receipt and leaves the intent result untouched', function (): void {
    ['intent' => $intent, 'result' => $result] = walletIntent();
    $operation = CommandOperation::query()->findOrFail($intent->operation_id)->result;
    $message = walletEvent($intent, 'succeeded', 'synthetic-event-success');

    expect(applyWalletEvent($message))->toBe(['disposition' => 'applied', 'state' => 'succeeded', 'credited' => true, 'replayed' => false])
        ->and(applyWalletEvent($message))->toBe(['disposition' => 'applied', 'state' => 'succeeded', 'credited' => false, 'replayed' => true])
        ->and(applyWalletEvent(walletEvent($intent, 'succeeded')))->toBe(['disposition' => 'duplicate', 'state' => 'succeeded', 'credited' => false, 'replayed' => false]);

    $credit = WalletDepositCredit::query()->sole();
    $entry = LedgerEntry::query()->sole();
    expect($credit->id)->not->toBe($entry->id)->not->toBe($intent->id)->not->toBe($intent->operation_id)
        ->and([$credit->operation_id, $credit->request_id, $credit->amount, $credit->ledger_entry_id])->toBe([$intent->operation_id, $intent->request_id, '50000', $entry->id])
        ->and($credit->provider_event_id)->toBe(WalletProviderEvent::query()->where('provider_event_id', 'synthetic-event-success')->sole()->id)
        ->and([$entry->source_type, $entry->source_id])->toBe(['wallet_deposit_intent', $intent->id])
        ->and(LedgerLine::query()->where('entry_id', $entry->id)->orderBy('direction', 'desc')->get(['direction', 'amount'])->toArray())
        ->toBe([['direction' => 'debit', 'amount' => '50000'], ['direction' => 'credit', 'amount' => '50000']])
        ->and(walletBalances($intent))->toBe(['50000', '50000'])
        ->and(CommandOperation::query()->findOrFail($intent->operation_id)->result)->toBe($operation)
        ->and($result['data']['receipt']['receipt_id'])->toBe($intent->id)
        ->and(WalletProviderEvent::query()->count())->toBe(2);
});

it('splits a synthetic nonzero fee so gross clearing equals the net credit plus the fee credit', function (): void {
    ['intent' => $intent] = walletIntent('5000', '150');
    applyWalletEvent(walletEvent($intent, 'succeeded'));
    $lines = LedgerLine::query()->join('ledger_accounts', 'ledger_accounts.id', '=', 'ledger_lines.account_id')
        ->orderBy('ledger_accounts.kind')->get(['ledger_accounts.kind', 'ledger_lines.direction', 'ledger_lines.amount'])->toArray();
    expect($lines)->toBe([['kind' => 'deposit_clearing', 'direction' => 'debit', 'amount' => '5000'], ['kind' => 'deposit_fee_revenue', 'direction' => 'credit', 'amount' => '150'],
        ['kind' => 'investor_available', 'direction' => 'credit', 'amount' => '4850']])
        ->and(WalletDepositCredit::query()->sole()->amount)->toBe('4850');
});

it('records a changed body under a used event identity as a key conflict and never applies it', function (): void {
    ['intent' => $intent] = walletIntent();
    ['intent' => $other] = walletIntent();
    applyWalletEvent(walletEvent($intent, 'pending', 'synthetic-event-shared'));
    expect(applyWalletEvent(walletEvent($intent, 'succeeded', 'synthetic-event-shared')))->toMatchArray(['disposition' => 'key_conflict', 'state' => 'pending', 'credited' => false])
        ->and(applyWalletEvent(walletEvent($other, 'succeeded', 'synthetic-event-shared')))->toMatchArray(['disposition' => 'key_conflict', 'state' => 'pending', 'credited' => false])
        ->and(WalletProviderEvent::query()->where('provider_event_id', 'synthetic-event-shared')->pluck('disposition')->all())->toEqualCanonicalizing(['duplicate', 'key_conflict', 'key_conflict'])
        ->and(LedgerEntry::query()->count())->toBe(0)->and(WalletDepositCredit::query()->count())->toBe(0);
});

it('never credits pending, unknown or failed outcomes, and never lets a later success override a failure', function (): void {
    ['intent' => $intent] = walletIntent();
    expect(applyWalletEvent(walletEvent($intent, 'pending'))['disposition'])->toBe('duplicate')
        ->and(applyWalletEvent(walletEvent($intent, 'unknown')))->toMatchArray(['disposition' => 'applied', 'state' => 'unknown', 'credited' => false])
        ->and(applyWalletEvent(walletEvent($intent, 'pending')))->toMatchArray(['disposition' => 'applied', 'state' => 'pending'])
        ->and(applyWalletEvent(walletEvent($intent, 'failed')))->toMatchArray(['disposition' => 'applied', 'state' => 'failed', 'credited' => false])
        ->and(applyWalletEvent(walletEvent($intent, 'succeeded')))->toMatchArray(['disposition' => 'conflict', 'state' => 'failed', 'credited' => false])
        ->and(applyWalletEvent(walletEvent($intent, 'unknown')))->toMatchArray(['disposition' => 'after_final', 'state' => 'failed'])
        ->and(LedgerEntry::query()->count())->toBe(0)->and(walletBalances($intent))->toBe(['0', '0']);
});

it('keeps a failure after success as after-final evidence without reversing the credit', function (): void {
    ['intent' => $intent] = walletIntent();
    applyWalletEvent(walletEvent($intent, 'unknown'));
    applyWalletEvent(walletEvent($intent, 'succeeded'));
    expect(applyWalletEvent(walletEvent($intent, 'failed')))->toMatchArray(['disposition' => 'after_final', 'state' => 'succeeded', 'credited' => false])
        ->and(WalletProviderEvent::query()->where('disposition', 'after_final')->sole()->state)->toBe('failed')
        ->and(walletBalances($intent))->toBe(['50000', '50000'])->and(LedgerEntry::query()->count())->toBe(1);
});

it('records a signed event whose facts do not match the intent as a mismatch that never credits', function (array $overrides): void {
    ['intent' => $intent] = walletIntent();
    expect(applyWalletEvent(walletEvent($intent, 'succeeded', overrides: $overrides)))->toMatchArray(['disposition' => 'mismatch', 'state' => 'pending', 'credited' => false])
        ->and(LedgerEntry::query()->count())->toBe(0)
        ->and(applyWalletEvent(walletEvent($intent, 'succeeded'))['credited'])->toBeTrue();
})->with([
    'amount' => [['amount' => '49999']], 'currency' => [['currency' => 'USD']], 'environment' => [['environment' => 'uat']],
]);

it('refuses unknown references and unauthenticated messages without recording anything', function (): void {
    ['intent' => $intent] = walletIntent();
    expect(fn () => applyWalletEvent(walletEvent($intent, 'succeeded', overrides: ['reference' => 'syn_unknown'])))->toThrow(CommandRejection::class, 'DEPOSIT_REFERENCE_UNKNOWN')
        ->and(fn () => applyWalletEvent([...walletEvent($intent, 'succeeded'), 'amount' => '999999']))->toThrow(CommandRejection::class, 'PROVIDER_EVENT_UNVERIFIED')
        ->and(WalletProviderEvent::query()->count())->toBe(0)->and(LedgerEntry::query()->count())->toBe(0);
});

it('dispatches after commit from the outbox and records one acknowledgement', function (): void {
    ['intent' => $intent] = walletIntent();
    expect(WalletDepositDispatch::query()->where('intent_id', $intent->id)->orderBy('id')->pluck('phase')->all())->toBe(['queued', 'claimed', 'acknowledged']);
    expect(Artisan::call('wallet:dispatch-deposits'))->toBe(0)->and(Artisan::output())->toContain('Claimed 0 deposit dispatches; 0 acknowledged.');
    expect(Artisan::call('wallet:dispatch-deposits', ['--limit' => '0']))->toBe(2)->and(Artisan::output())->toContain('The limit must be an integer from 1 to 500.');
});

it('recovers an intent whose dispatch never ran, and resends an interrupted send only when that is safe', function (): void {
    $fixture = InvestorWalletFixture::ready();
    $store = app(WalletStore::class);
    $queued = $store->deposit($fixture['user']->id, 1, (string) Str::uuid(), ['currency' => 'RWF', 'amount' => '50000'], $fixture['method']->id);
    $interrupted = $store->deposit($fixture['user']->id, 1, (string) Str::uuid(), ['currency' => 'RWF', 'amount' => '60000'], $fixture['method']->id);
    $claims = $store->claimDispatches($interrupted['data']['intent_id'], 10, true, 'testing');
    expect($claims)->toHaveCount(1)
        ->and([$claims[0]->amount, $claims[0]->environment, $claims[0]->operationId])->toBe(['60000', 'testing', $interrupted['operation_id']]);

    expect(Artisan::call('wallet:dispatch-deposits'))->toBe(0)->and(Artisan::output())->toContain('Claimed 1 deposit dispatches; 1 acknowledged.');
    expect(WalletDepositDispatch::query()->where('intent_id', $queued['data']['intent_id'])->pluck('phase')->all())->toEqualCanonicalizing(['queued', 'claimed', 'acknowledged'])
        ->and(WalletDepositDispatch::query()->where('intent_id', $interrupted['data']['intent_id'])->pluck('phase')->all())->toEqualCanonicalizing(['queued', 'claimed']);

    $this->travel(6)->minutes();
    expect($store->claimDispatches(null, 10, false, 'testing'))->toBe([]);
    expect(Artisan::call('wallet:dispatch-deposits'))->toBe(0)->and(Artisan::output())->toContain('Claimed 1 deposit dispatches; 1 acknowledged.');
    $store->recordDispatch($interrupted['data']['intent_id'], false);
    expect(WalletDepositDispatch::query()->where('intent_id', $interrupted['data']['intent_id'])->pluck('phase')->all())->toEqualCanonicalizing(['queued', 'claimed', 'acknowledged']);
});

it('records a send that throws as unacknowledged and never retries it automatically', function (): void {
    app()->instance(DepositProvider::class, new class implements DepositProvider
    {
        public function name(): string
        {
            return 'synthetic';
        }

        public function idempotentSends(): bool
        {
            return false;
        }

        public function initiate(DepositInstruction $instruction): bool
        {
            throw new RuntimeException('provider timeout');
        }

        public function verify(array $message): VerifiedDepositEvent
        {
            throw new CommandRejection('PROVIDER_EVENT_UNVERIFIED', 401);
        }
    });
    ['intent' => $intent] = walletIntent();
    $this->travel(6)->minutes();
    expect(Artisan::call('wallet:dispatch-deposits'))->toBe(0)->and(Artisan::output())->toContain('Claimed 0 deposit dispatches; 0 acknowledged.');
    expect(WalletDepositDispatch::query()->where('intent_id', $intent->id)->pluck('phase')->all())->toEqualCanonicalizing(['queued', 'claimed', 'unacknowledged']);
});

it('refuses the worker and the dispatcher where synthetic support is not allowed', function (): void {
    config(['isolation.live_money_enabled' => true]);
    expect(fn () => Artisan::call('wallet:dispatch-deposits'))->toThrow(LogicException::class, 'WALLET_SYNTHETIC_ONLY')
        ->and(fn () => app(DispatchDepositIntents::class)->handle())->toThrow(LogicException::class, 'WALLET_SYNTHETIC_ONLY');
});

it('refuses to dispatch or call the provider inside an open transaction', function (): void {
    ['intent' => $intent] = walletIntent();
    expect(fn () => DB::transaction(fn () => app(DispatchDepositIntents::class)->handle()))
        ->toThrow(LogicException::class, 'WALLET_DISPATCH_TRANSACTION_OPEN')
        ->and(fn () => DB::transaction(fn () => app(DepositProvider::class)->initiate(new DepositInstruction($intent->id, $intent->operation_id,
            $intent->provider_reference, $intent->amount, 'RWF', 'testing'))))->toThrow(LogicException::class, 'WALLET_DISPATCH_TRANSACTION_OPEN');
});
