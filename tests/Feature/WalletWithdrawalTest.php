<?php

declare(strict_types=1);

use App\Application\Wallet\ApplyWithdrawalOutcome;
use App\Application\Wallet\Contracts\SyntheticEventSigner;
use App\Application\Wallet\Contracts\SyntheticWalletFixtures;
use App\Application\Wallet\DispatchWithdrawals;
use App\Domain\Operations\CommandRejection;
use App\Models\InvestorAccountRestriction;
use App\Models\LedgerAccount;
use App\Models\LedgerEntry;
use App\Models\LedgerLine;
use App\Models\PartyNotice;
use App\Models\WalletWithdrawal;
use App\Models\WalletWithdrawalDispatch;
use App\Models\WalletWithdrawalEvent;
use App\Notifications\Investor\WalletTransfer;
use App\Notifications\Investor\WalletTransferCompleted;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Support\InvestorWalletFixture;

/** A bucket's balance: credits less debits for a wallet's account, or debits less credits for a platform account. */
function withdrawalBucket(string $kind, ?string $walletId): int
{
    $sum = fn (string $direction): int => (int) LedgerLine::query()->where('direction', $direction)
        ->whereIn('account_id', LedgerAccount::query()->where('kind', $kind)->where('wallet_id', $walletId)->select('id'))->sum('amount');

    return $walletId === null ? $sum('credit') - $sum('debit') : $sum('credit') - $sum('debit');
}

/** Fires every deferred check as a commit would, then defers them again for the rest of the test. */
function withdrawalCommits(): void
{
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
}

/** Runs the change in its own savepoint and fires deferred checks, as a commit would. */
function withdrawalSchemaRejects(Closure $change, string $message): void
{
    expect(fn () => DB::transaction(function () use ($change): void {
        $change();
        DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    }))->toThrow(QueryException::class, $message);
}

/** @return array{fixture: array<string, mixed>, withdrawal: WalletWithdrawal, result: array<string, mixed>} */
function recordedWithdrawal(string $amount = '100000', string $balance = '500000'): array
{
    $fixture = InvestorWalletFixture::funded($balance);
    $result = InvestorWalletFixture::withdraw($fixture, $amount);
    withdrawalCommits();

    return ['fixture' => $fixture, 'withdrawal' => WalletWithdrawal::query()->whereKey((string) $result['data']['withdrawal_id'])->sole(), 'result' => $result];
}

it('records a withdrawal with its hold, receipt and dispatch, moving the amount out of the available balance', function (): void {
    ['withdrawal' => $withdrawal, 'result' => $result] = recordedWithdrawal();

    expect($result['status'])->toBe('completed')->and($result['data']['receipt'])->toMatchArray(['code' => 'WITHDRAWAL_RECORDED',
        'amount' => ['currency' => 'RWF', 'amount' => '100000'], 'policy_version' => 'LED-R04/2026-10'])
        ->and($result['data']['receipt']['reference'])->toStartWith('RZX-')
        ->and([$withdrawal->amount, $withdrawal->provider_charge, $withdrawal->received, $withdrawal->provider])->toBe(['100000', '0', '100000', 'synthetic'])
        ->and(withdrawalBucket('investor_available', $withdrawal->wallet_id))->toBe(400000)
        ->and(withdrawalBucket('investor_withdrawing', $withdrawal->wallet_id))->toBe(100000)
        ->and(LedgerEntry::query()->where('source_type', 'wallet_withdrawal')->pluck('kind')->all())->toBe(['withdrawal_hold'])
        ->and(WalletWithdrawalDispatch::query()->where('withdrawal_id', $withdrawal->id)->orderBy('id')->pluck('phase')->all())
        ->toBe(['queued', 'claimed', 'acknowledged']);
});

it('refuses amounts outside RWF 1,000 to 2,000,000, recording nothing', function (string $amount, string $message): void {
    $fixture = InvestorWalletFixture::funded('3000000');
    $refused = InvestorWalletFixture::withdraw($fixture, $amount);

    expect($refused['status'])->toBe('rejected')->and($refused['code'])->toBe('VALIDATION_FAILED')->and($refused['http_status'])->toBe(422)
        ->and($refused['field_errors'])->toBe(['amount' => [$message]])
        ->and(WalletWithdrawal::query()->count())->toBe(0)->and(LedgerEntry::query()->where('source_type', 'wallet_withdrawal')->count())->toBe(0);
})->with([
    'BRS acceptance: 2,000,001' => ['2000001', 'Withdraw at most RWF 2000000.'],
    'below the minimum' => ['999', 'Withdraw at least RWF 1000.'],
]);

it('refuses a withdrawal larger than the available balance', function (): void {
    $fixture = InvestorWalletFixture::funded('50000');

    expect(InvestorWalletFixture::withdraw($fixture, '50001')['field_errors'])->toBe(['amount' => ['Amount exceeds your available balance.']])
        ->and(InvestorWalletFixture::withdraw($fixture, '50000')['status'])->toBe('completed');
    withdrawalCommits();
});

it('pays only to the Investor\'s own verified account and never under a withdrawal hold', function (): void {
    $fixture = InvestorWalletFixture::funded();
    $other = InvestorWalletFixture::method(InvestorWalletFixture::investor()['party']);

    expect(InvestorWalletFixture::withdraw($fixture, '10000', methodId: $other->id)['code'])->toBe('WITHDRAWAL_METHOD_UNVERIFIED');

    app(SyntheticWalletFixtures::class)->restrict($fixture['party']->id);
    expect(InvestorWalletFixture::withdraw($fixture, '10000')['code'])->toBe('RESTRICTION_ACTIVE')
        ->and(InvestorAccountRestriction::query()->count())->toBe(1)->and(WalletWithdrawal::query()->count())->toBe(0);
});

it('replays the recorded result for the same request and holds the cash once', function (): void {
    $fixture = InvestorWalletFixture::funded();
    $requestId = (string) Str::uuid();
    $first = InvestorWalletFixture::withdraw($fixture, '20000', $requestId);

    expect(InvestorWalletFixture::withdraw($fixture, '20000', $requestId))->toBe($first)
        ->and(WalletWithdrawal::query()->count())->toBe(1)
        ->and(withdrawalBucket('investor_withdrawing', WalletWithdrawal::query()->sole()->wallet_id))->toBe(20000);
});

it('pays a verified success out exactly once, e-mails the Investor and records a sent notice', function (): void {
    Notification::fake();
    ['fixture' => $fixture, 'withdrawal' => $withdrawal] = recordedWithdrawal();
    $message = InvestorWalletFixture::withdrawalEvent($withdrawal->id, 'succeeded', 'synthetic-withdrawal-paid');

    expect(app(ApplyWithdrawalOutcome::class)->handle($message))
        ->toBe(['disposition' => 'applied', 'state' => 'succeeded', 'settled' => true, 'replayed' => false])
        ->and(app(ApplyWithdrawalOutcome::class)->handle($message))
        ->toBe(['disposition' => 'applied', 'state' => 'succeeded', 'settled' => false, 'replayed' => true])
        ->and(InvestorWalletFixture::settleWithdrawal($withdrawal->id, 'failed'))
        ->toBe(['disposition' => 'after_final', 'state' => 'succeeded', 'settled' => false, 'replayed' => false]);
    withdrawalCommits();

    $payout = LedgerEntry::query()->where('kind', 'withdrawal_payout')->sole();
    expect([$payout->source_id, $payout->origin_operation_id])->toBe([$withdrawal->id, $withdrawal->operation_id])
        ->and(withdrawalBucket('investor_withdrawing', $withdrawal->wallet_id))->toBe(0)
        ->and(withdrawalBucket('investor_available', $withdrawal->wallet_id))->toBe(400000)
        ->and(withdrawalBucket('withdrawal_clearing', null))->toBe(100000)
        ->and(LedgerEntry::query()->where('kind', 'withdrawal_return')->count())->toBe(0);

    $notice = PartyNotice::query()->where('kind', 'wallet.withdrawal_sent')->sole();
    expect($notice->party_id)->toBe($fixture['party']->id)->and($notice->facts)->toMatchArray(['amount' => '100000', 'received' => '100000',
        'currency' => 'RWF', 'account' => 'MTN MoMo', 'available' => '400000', 'reference' => 'RZW-'.strtoupper(substr($payout->id, -10))]);
    Notification::assertSentToTimes($fixture['user'], WalletTransferCompleted::class, 1);
    Notification::assertSentTo($fixture['user'], WalletTransferCompleted::class, fn (WalletTransferCompleted $mail): bool => $mail->transfer === WalletTransfer::Withdrawal
        && [$mail->amount, $mail->account, $mail->availableBalance] === ['100000', 'MTN MoMo', '400000']);
});

it('returns the held cash on a verified failure without paying anything out', function (): void {
    Notification::fake();
    ['fixture' => $fixture, 'withdrawal' => $withdrawal] = recordedWithdrawal();

    expect(InvestorWalletFixture::settleWithdrawal($withdrawal->id, 'unknown'))->toBe(['disposition' => 'applied', 'state' => 'unknown', 'settled' => false, 'replayed' => false])
        ->and(InvestorWalletFixture::settleWithdrawal($withdrawal->id, 'failed'))->toBe(['disposition' => 'applied', 'state' => 'failed', 'settled' => true, 'replayed' => false])
        ->and(InvestorWalletFixture::settleWithdrawal($withdrawal->id, 'succeeded'))->toBe(['disposition' => 'conflict', 'state' => 'failed', 'settled' => false, 'replayed' => false])
        ->and(withdrawalBucket('investor_available', $withdrawal->wallet_id))->toBe(500000)
        ->and(withdrawalBucket('investor_withdrawing', $withdrawal->wallet_id))->toBe(0)
        ->and(LedgerEntry::query()->where('kind', 'withdrawal_payout')->count())->toBe(0)
        ->and(PartyNotice::query()->where('party_id', $fixture['party']->id)->where('kind', 'wallet.withdrawal_returned')->sole()->facts)
        ->toEqual(['amount' => '100000', 'currency' => 'RWF', 'account' => 'MTN MoMo']);
    withdrawalCommits();
    Notification::assertNotSentTo($fixture['user'], WalletTransferCompleted::class);
});

it('keeps a mismatched or reused event as evidence and settles nothing', function (): void {
    ['withdrawal' => $withdrawal] = recordedWithdrawal();
    $deposit = InvestorWalletFixture::deposit(InvestorWalletFixture::funded(), '5000');
    InvestorWalletFixture::settle((string) $deposit['data']['intent_id'], 'succeeded', 'synthetic-event-shared');

    expect(InvestorWalletFixture::settleWithdrawal($withdrawal->id, 'succeeded', null, ['amount' => '99999'])['disposition'])->toBe('mismatch')
        ->and(InvestorWalletFixture::settleWithdrawal($withdrawal->id, 'succeeded', null, ['environment' => 'uat'])['disposition'])->toBe('mismatch')
        ->and(InvestorWalletFixture::settleWithdrawal($withdrawal->id, 'succeeded', 'synthetic-event-shared')['disposition'])->toBe('key_conflict')
        ->and(WalletWithdrawalEvent::query()->where('withdrawal_id', $withdrawal->id)->pluck('disposition')->sort()->values()->all())
        ->toBe(['key_conflict', 'mismatch', 'mismatch'])
        ->and(LedgerEntry::query()->whereIn('kind', ['withdrawal_payout', 'withdrawal_return'])->count())->toBe(0);
});

it('refuses an unsigned withdrawal outcome and a deposit signature', function (): void {
    ['withdrawal' => $withdrawal] = recordedWithdrawal();
    $message = InvestorWalletFixture::withdrawalEvent($withdrawal->id);
    $forged = app(SyntheticEventSigner::class)->sign(array_diff_key($message, ['signature' => true]));

    expect(fn () => app(ApplyWithdrawalOutcome::class)->handle([...$message, 'signature' => str_repeat('0', 64)]))
        ->toThrow(CommandRejection::class, 'PROVIDER_EVENT_UNVERIFIED')
        ->and(fn () => app(ApplyWithdrawalOutcome::class)->handle($forged))
        ->toThrow(CommandRejection::class, 'PROVIDER_EVENT_UNVERIFIED')
        ->and(WalletWithdrawalEvent::query()->count())->toBe(0);
});

it('refuses ledger postings that are not bound to a held withdrawal and its applied outcome', function (): void {
    ['withdrawal' => $withdrawal] = recordedWithdrawal();
    $post = function (string $kind, string $from, ?string $fromWallet, string $to, ?string $toWallet) use ($withdrawal): void {
        (function () use ($withdrawal, $kind, $from, $fromWallet, $to, $toWallet): void {
            $id = strtolower((string) Str::ulid());
            DB::table('ledger_entries')->insert(['id' => $id, 'wallet_id' => $withdrawal->wallet_id, 'kind' => $kind, 'source_type' => 'wallet_withdrawal',
                'source_id' => $withdrawal->id, 'origin_operation_id' => $withdrawal->operation_id, 'currency' => 'RWF', 'payload' => '{}', 'sha256' => str_repeat('a', 64),
                'created_at' => now()]);
            foreach ([[$from, $fromWallet, 'debit'], [$to, $toWallet, 'credit']] as [$account, $wallet, $direction]) {
                DB::table('ledger_lines')->insert(['id' => strtolower((string) Str::ulid()), 'entry_id' => $id, 'direction' => $direction, 'amount' => $withdrawal->amount,
                    'account_id' => LedgerAccount::query()->where('kind', $account)->where('wallet_id', $wallet)->value('id')
                        ?? tap(strtolower((string) Str::ulid()), fn (string $new) => DB::table('ledger_accounts')->insert(['id' => $new, 'wallet_id' => $wallet,
                            'kind' => $account, 'currency' => 'RWF', 'created_at' => now()])), 'created_at' => now()]);
            }
        })();
    };

    withdrawalSchemaRejects(fn () => $post('withdrawal_payout', 'investor_withdrawing', $withdrawal->wallet_id, 'withdrawal_clearing', null),
        'must follow its applied provider outcome');
    withdrawalSchemaRejects(fn () => $post('withdrawal_return', 'investor_withdrawing', $withdrawal->wallet_id, 'investor_held', $withdrawal->wallet_id),
        'must move exactly its withdrawal amount');

    InvestorWalletFixture::settleWithdrawal($withdrawal->id, 'succeeded');
    withdrawalCommits();
    withdrawalSchemaRejects(fn () => $post('withdrawal_return', 'investor_withdrawing', $withdrawal->wallet_id, 'investor_available', $withdrawal->wallet_id),
        'would overdraw an Investor bucket');
});

it('refuses a withdrawal recorded without its hold', function (): void {
    $fixture = InvestorWalletFixture::funded();
    $wallet = DB::table('investor_wallets')->where('party_id', $fixture['party']->id)->value('id');

    withdrawalCommits();

    withdrawalSchemaRejects(fn () => DB::table('wallet_withdrawals')->insert(['id' => strtolower((string) Str::ulid()), 'wallet_id' => $wallet,
        'party_id' => $fixture['party']->id, 'method_id' => $fixture['method']->id, 'operation_id' => DB::table('command_operations')->value('id'),
        'request_id' => (string) Str::uuid(), 'terms_version' => 'LED-R04/2026-10', 'amount' => 1000, 'provider_charge' => 0, 'received' => 1000,
        'currency' => 'RWF', 'provider' => 'synthetic', 'provider_reference' => 'x', 'provider_reference_sha256' => hash('sha256', Str::random()),
        'payload' => '{}', 'sha256' => str_repeat('a', 64), 'created_at' => now()]), 'exactly one hold');
});

it('sends queued withdrawals from the worker and never inside a transaction', function (): void {
    ['withdrawal' => $withdrawal] = recordedWithdrawal();

    expect(app(DispatchWithdrawals::class)->handle())->toBe(['claimed' => 0, 'acknowledged' => 0])
        ->and(app(DispatchWithdrawals::class)->backlog())->toBe(0)
        ->and(fn () => DB::transaction(fn () => app(DispatchWithdrawals::class)->handle($withdrawal->id)))->toThrow(LogicException::class, 'WALLET_DISPATCH_TRANSACTION_OPEN');
});

it('offers withdrawal on the wallet page and quotes an entered amount against the available balance', function (): void {
    $fixture = InvestorWalletFixture::funded('50000');
    $props = fn (string $amount) => $this->actingAs($fixture['user'])->get(route('investor.wallet', ['kind' => 'withdraw', 'amount' => $amount]))
        ->assertOk()->viewData('page')['props'];

    $page = $props('20000');
    expect($page['allowed_actions'])->toBe(['wallet.deposit', 'wallet.withdraw'])
        ->and($page['funding']['kind'])->toBe('withdraw')->and($page['funding']['quote'])->toBeNull()
        ->and($page['funding']['withdrawal'])->toBe(['version' => 'LED-R04/2026-10', 'minimum' => ['currency' => 'RWF', 'amount' => '1000'],
            'maximum' => ['currency' => 'RWF', 'amount' => '2000000'], 'charge' => ['currency' => 'RWF', 'amount' => '0'],
            'quote' => ['amount' => ['currency' => 'RWF', 'amount' => '20000'], 'charge' => ['currency' => 'RWF', 'amount' => '0'],
                'received' => ['currency' => 'RWF', 'amount' => '20000'], 'refusal' => null]])
        ->and($page['actions']['withdraw'])->toBe(['url' => '/investor/wallet/withdrawals', 'method' => 'post'])
        ->and($page['links']['withdraw'])->toBe(['url' => '/investor/wallet?kind=withdraw', 'method' => 'get'])
        ->and($page['links']['withdraw_operation']['url'])->toBe('/investor/wallet-operations/{request_id}?command=wallet.withdraw&identity_context_revision=1')
        ->and($props('60000')['funding']['withdrawal']['quote']['refusal'])->toBe('INSUFFICIENT_AVAILABLE_FUNDS')
        ->and($props('2000001')['funding']['withdrawal']['quote']['refusal'])->toBe('VALIDATION_FAILED')
        ->and($props('500')['funding']['withdrawal']['quote']['refusal'])->toBe('VALIDATION_FAILED');

    app(SyntheticWalletFixtures::class)->restrict($fixture['party']->id);
    expect($props('20000')['allowed_actions'])->toBe(['wallet.deposit']);
});

it('withdraws over HTTP, finds the recorded result by its request and shows the withdrawal until it is paid out', function (): void {
    $fixture = InvestorWalletFixture::funded();
    $requestId = (string) Str::uuid();
    $body = ['request_id' => $requestId, 'identity_context_revision' => 1, 'amount' => ['currency' => 'RWF', 'amount' => '120000'], 'method_id' => $fixture['method']->id];

    $posted = $this->actingAs($fixture['user'])->postJson(route('investor.wallet.withdraw'), $body)->assertOk()->json();
    $withdrawal = WalletWithdrawal::query()->sole();
    expect($posted['status'])->toBe('completed')->and($posted['data']['receipt']['code'])->toBe('WITHDRAWAL_RECORDED')
        ->and($posted['data']['receipt']['link']['url'])->toBe('/investor/wallet-operations/'.$requestId.'?command=wallet.withdraw&identity_context_revision=1')
        ->and($posted['data']['current'])->toMatchArray(['id' => $withdrawal->id, 'state' => 'pending', 'settled_receipt' => null])
        ->and($this->getJson($posted['data']['receipt']['link']['url'])->assertOk()->json('data.receipt.receipt_id'))->toBe($withdrawal->id);

    $page = $this->get(route('investor.wallet'))->viewData('page')['props'];
    expect($page['wallet']['total'])->toBe(['currency' => 'RWF', 'amount' => '380000'])
        ->and($page['wallet']['pending_withdrawals'])->toBe(['currency' => 'RWF', 'amount' => '120000'])
        ->and($page['withdrawals'][0])->toMatchArray(['id' => $withdrawal->id, 'state' => 'pending', 'link' => ['url' => '/investor/wallet?receipt='.$withdrawal->id, 'method' => 'get']])
        ->and(json_encode($page, JSON_THROW_ON_ERROR))->not->toContain('syw_');

    InvestorWalletFixture::settleWithdrawal($withdrawal->id);
    $payout = LedgerEntry::query()->where('kind', 'withdrawal_payout')->sole();
    $page = $this->get(route('investor.wallet'))->viewData('page')['props'];
    expect($page['wallet']['pending_withdrawals']['amount'])->toBe('0')
        ->and($page['withdrawals'][0]['settled_receipt'])->toMatchArray(['receipt_id' => $payout->id, 'code' => 'WITHDRAWAL_PAID'])
        ->and($page['history']['items'][0])->toMatchArray(['id' => $payout->id, 'movement' => 'external', 'kind' => 'withdrawal', 'direction' => 'out',
            'amount' => ['currency' => 'RWF', 'amount' => '120000'], 'counterparty' => 'MTN MoMo'])
        ->and($page['history']['items'][1]['kind'])->toBe('deposit');

    $opened = $this->get(route('investor.wallet', ['receipt' => $payout->id]))->viewData('page')['props']['receipt'];
    expect($opened['kind'])->toBe('withdrawal')->and($opened['receipt']['code'])->toBe('WITHDRAWAL_PAID');
    $opened = $this->get(route('investor.wallet', ['receipt' => $withdrawal->id]))->viewData('page')['props']['receipt'];
    expect($opened['request_receipt']['code'])->toBe('WITHDRAWAL_RECORDED');

    $statement = $this->get(route('investor.wallet.statement', ['format' => 'csv']))->assertOk()->getContent();
    expect($statement)->toContain('Withdrawal to MTN MoMo')->toContain('RZW-'.strtoupper(substr($payout->id, -10)));
});

it('refuses a withdrawal from an API token without the command ability', function (): void {
    $fixture = InvestorWalletFixture::funded();
    Sanctum::actingAs($fixture['user'], ['investor:read']);

    $this->postJson(route('api.v1.investor.wallet.withdraw'), ['request_id' => (string) Str::uuid(), 'identity_context_revision' => 1,
        'amount' => ['currency' => 'RWF', 'amount' => '1000'], 'method_id' => $fixture['method']->id])->assertForbidden();
    expect($this->getJson(route('api.v1.investor.wallet'))->assertOk()->json('data.allowed_actions'))->toBe([]);
});
