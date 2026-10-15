<?php

declare(strict_types=1);

use App\Application\Environment\EnvironmentIsolation;
use App\Application\Wallet\ApplyBusinessProviderOutcome;
use App\Application\Wallet\ApplyBusinessWithdrawalOutcome;
use App\Application\Wallet\Contracts\SyntheticEventSigner;
use App\Application\Wallet\Contracts\SyntheticSettlement;
use App\Application\Wallet\Contracts\SyntheticWithdrawalSigner;
use App\Application\Wallet\DispatchBusinessWithdrawals;
use App\Application\Wallet\RecordBusinessDeposit;
use App\Application\Wallet\RecordBusinessWithdrawal;
use App\Domain\Operations\CommandRejection;
use App\Models\BusinessDepositIntent;
use App\Models\BusinessFundingMethod;
use App\Models\BusinessProfile;
use App\Models\BusinessWithdrawal;
use App\Models\BusinessWithdrawalDispatch;
use App\Models\BusinessWithdrawalEvent;
use App\Models\DepositPolicy;
use App\Models\LedgerAccount;
use App\Models\LedgerEntry;
use App\Models\LedgerLine;
use App\Models\PartyNotice;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Support\BusinessAuthorityFixture;

/**
 * A funded Business with two mandate holders: the first holds every permission, the second may only view.
 *
 * @return array{business: BusinessProfile, user: User, viewer: User, entity: string, method: BusinessFundingMethod}
 */
function businessWithdrawalFixture(string $balance = '500000'): array
{
    $authority = BusinessAuthorityFixture::make('organization', 2, 'COMPANY-'.Str::ulid(), 1);
    $viewer = $authority['people'][1]->id;
    $authority['terms']['people'] = array_map(fn (array $person): array => $person['party_id'] === $viewer ? [...$person, 'permissions' => ['business.view']] : $person,
        $authority['terms']['people']);
    BusinessAuthorityFixture::configure($authority);
    $business = BusinessProfile::query()->where('entity_party_id', $authority['entity'])->firstOrFail();
    DepositPolicy::query()->exists() || DepositPolicy::factory()->create(['fee' => '0', 'minimum' => '1000', 'maximum' => '1000000']);
    $fixture = ['business' => $business, 'user' => $authority['users'][0], 'viewer' => $authority['users'][1], 'entity' => $authority['entity'],
        'method' => BusinessFundingMethod::factory()->create(['business_id' => $business->id])];
    if ($balance !== '0') {
        $deposit = app(RecordBusinessDeposit::class)->handle($fixture['user']->id, 1, $business->id, (string) Str::uuid(),
            ['currency' => 'RWF', 'amount' => $balance], $fixture['method']->id);
        $intent = BusinessDepositIntent::query()->whereKey((string) $deposit['data']['intent_id'])->sole();
        app(ApplyBusinessProviderOutcome::class)->handle(app(SyntheticEventSigner::class)->sign(['provider' => 'synthetic',
            'event_id' => 'synthetic-event-'.Str::lower(Str::random(12)), 'reference' => $intent->provider_reference, 'state' => 'succeeded',
            'amount' => $balance, 'currency' => 'RWF', 'environment' => 'testing', 'observed_at' => now('UTC')->startOfSecond()->format(DATE_ATOM)]));
    }

    return $fixture;
}

/**
 * @param  array{business: BusinessProfile, user: User, method: BusinessFundingMethod}  $fixture
 * @return array<string, mixed>
 */
function businessWithdraw(array $fixture, string $amount = '100000', ?string $requestId = null, ?string $methodId = null): array
{
    $result = app(RecordBusinessWithdrawal::class)->handle($fixture['user']->id, 1, $fixture['business']->id, $requestId ?? (string) Str::uuid(),
        ['currency' => 'RWF', 'amount' => $amount], $methodId ?? $fixture['method']->id);
    businessWithdrawalCommits();

    return $result;
}

/**
 * Delivers a signed synthetic provider outcome for a Business withdrawal.
 *
 * @param  array<string, string>  $overrides
 * @return array{disposition: string, state: string, settled: bool, replayed: bool}
 */
function businessWithdrawalOutcome(string $withdrawalId, string $state = 'succeeded', ?string $eventId = null, array $overrides = []): array
{
    $withdrawal = BusinessWithdrawal::query()->whereKey($withdrawalId)->sole();

    return app(ApplyBusinessWithdrawalOutcome::class)->handle(app(SyntheticWithdrawalSigner::class)->sign([...['provider' => 'synthetic',
        'event_id' => $eventId ?? 'synthetic-event-'.Str::lower(Str::random(12)), 'reference' => $withdrawal->provider_reference, 'state' => $state,
        'amount' => $withdrawal->received, 'currency' => 'RWF', 'environment' => 'testing', 'observed_at' => now('UTC')->startOfSecond()->format(DATE_ATOM)],
        ...$overrides]));
}

/** A Business bucket's balance: credits less debits. */
function businessWithdrawalBucket(string $kind, ?string $walletId): int
{
    $sum = fn (string $direction): int => (int) LedgerLine::query()->where('direction', $direction)
        ->whereIn('account_id', LedgerAccount::query()->where('kind', $kind)->where('wallet_id', $walletId)->select('id'))->sum('amount');

    return $sum('credit') - $sum('debit');
}

/** Fires every deferred check as a commit would, then defers them again for the rest of the test. */
function businessWithdrawalCommits(): void
{
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
}

/** Runs the change in its own savepoint and fires deferred checks, as a commit would. */
function businessWithdrawalRejects(Closure $change, string $message): void
{
    expect(fn () => DB::transaction(function () use ($change): void {
        $change();
        DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    }))->toThrow(QueryException::class, $message);
}

it('records a Business withdrawal with its hold, receipt and dispatch, moving the amount out of the available balance', function (): void {
    $fixture = businessWithdrawalFixture();
    $result = businessWithdraw($fixture);
    $withdrawal = BusinessWithdrawal::query()->sole();

    expect($result['status'])->toBe('completed')->and($result['data']['receipt'])->toMatchArray(['code' => 'WITHDRAWAL_RECORDED',
        'amount' => ['currency' => 'RWF', 'amount' => '100000'], 'policy_version' => 'LED-R04/2026-10'])
        ->and($result['data']['receipt']['reference'])->toStartWith('RZX-')
        ->and([$withdrawal->business_id, $withdrawal->amount, $withdrawal->provider_charge, $withdrawal->received, $withdrawal->provider])
        ->toBe([$fixture['business']->id, '100000', '0', '100000', 'synthetic'])
        ->and(businessWithdrawalBucket('business_available', $withdrawal->wallet_id))->toBe(400000)
        ->and(businessWithdrawalBucket('business_withdrawing', $withdrawal->wallet_id))->toBe(100000)
        ->and(LedgerEntry::query()->where('source_type', 'business_withdrawal')->pluck('kind')->all())->toBe(['business_withdrawal_hold'])
        ->and(BusinessWithdrawalDispatch::query()->where('withdrawal_id', $withdrawal->id)->orderBy('id')->pluck('phase')->all())
        ->toBe(['queued', 'claimed', 'acknowledged']);
});

it('refuses amounts outside the limits or above the available balance, and accounts not the Business\'s own', function (): void {
    $fixture = businessWithdrawalFixture('50000');
    $other = BusinessFundingMethod::factory()->create(['business_id' => businessWithdrawalFixture('0')['business']->id]);
    $revoked = BusinessFundingMethod::factory()->create(['business_id' => $fixture['business']->id, 'revoked_at' => now()->subMinute()]);

    expect(businessWithdraw($fixture, '999')['field_errors'])->toBe(['amount' => ['Withdraw at least RWF 1000.']])
        ->and(businessWithdraw($fixture, '50001')['field_errors'])->toBe(['amount' => ['Amount exceeds your available balance.']])
        ->and(businessWithdraw($fixture, '12x')['code'])->toBe('VALIDATION_FAILED')
        ->and(businessWithdraw($fixture, '10000', methodId: $other->id)['code'])->toBe('WITHDRAWAL_METHOD_UNVERIFIED')
        ->and(businessWithdraw($fixture, '10000', methodId: $revoked->id)['code'])->toBe('WITHDRAWAL_METHOD_UNVERIFIED')
        ->and(BusinessWithdrawal::query()->count())->toBe(0)
        ->and(businessWithdraw($fixture, '50000')['status'])->toBe('completed');
});

it('replays the recorded result for the same request and holds the cash once', function (): void {
    $fixture = businessWithdrawalFixture();
    $requestId = (string) Str::uuid();
    $first = businessWithdraw($fixture, '20000', $requestId);

    expect(businessWithdraw($fixture, '20000', $requestId))->toBe($first)
        ->and(BusinessWithdrawal::query()->count())->toBe(1)
        ->and(businessWithdrawalBucket('business_withdrawing', BusinessWithdrawal::query()->sole()->wallet_id))->toBe(20000);
});

it('pays a verified success out exactly once and tells the Business where it went', function (): void {
    $fixture = businessWithdrawalFixture();
    businessWithdraw($fixture);
    $withdrawal = BusinessWithdrawal::query()->sole();

    expect(businessWithdrawalOutcome($withdrawal->id, 'succeeded', 'synthetic-business-paid'))
        ->toBe(['disposition' => 'applied', 'state' => 'succeeded', 'settled' => true, 'replayed' => false])
        ->and(businessWithdrawalOutcome($withdrawal->id, 'succeeded', 'synthetic-business-paid'))
        ->toBe(['disposition' => 'applied', 'state' => 'succeeded', 'settled' => false, 'replayed' => true])
        ->and(businessWithdrawalOutcome($withdrawal->id, 'failed'))
        ->toBe(['disposition' => 'after_final', 'state' => 'succeeded', 'settled' => false, 'replayed' => false]);
    businessWithdrawalCommits();

    $payout = LedgerEntry::query()->where('kind', 'business_withdrawal_payout')->sole();
    expect([$payout->source_id, $payout->origin_operation_id])->toBe([$withdrawal->id, $withdrawal->operation_id])
        ->and(businessWithdrawalBucket('business_withdrawing', $withdrawal->wallet_id))->toBe(0)
        ->and(businessWithdrawalBucket('business_available', $withdrawal->wallet_id))->toBe(400000)
        ->and(LedgerEntry::query()->where('kind', 'business_withdrawal_return')->count())->toBe(0)
        ->and(PartyNotice::query()->where('kind', 'business.withdrawal_sent')->get(['party_id', 'facts'])->toArray())->toBe([['party_id' => $fixture['entity'],
            'facts' => ['amount' => '100000', 'account' => 'MTN MoMo +250 788 ···· 456', 'currency' => 'RWF', 'received' => '100000']]]);
});

it('returns the held cash on a verified failure, and keeps a mismatched or reused event as evidence', function (): void {
    $fixture = businessWithdrawalFixture();
    businessWithdraw($fixture);
    $withdrawal = BusinessWithdrawal::query()->sole();
    $deposit = BusinessDepositIntent::query()->sole();
    $shared = DB::table('business_provider_events')->where('intent_id', $deposit->id)->value('provider_event_id');

    expect(businessWithdrawalOutcome($withdrawal->id, 'succeeded', null, ['amount' => '99999'])['disposition'])->toBe('mismatch')
        ->and(businessWithdrawalOutcome($withdrawal->id, 'succeeded', (string) $shared)['disposition'])->toBe('key_conflict')
        ->and(businessWithdrawalOutcome($withdrawal->id, 'unknown'))->toBe(['disposition' => 'applied', 'state' => 'unknown', 'settled' => false, 'replayed' => false])
        ->and(businessWithdrawalOutcome($withdrawal->id, 'failed'))->toBe(['disposition' => 'applied', 'state' => 'failed', 'settled' => true, 'replayed' => false])
        ->and(businessWithdrawalBucket('business_available', $withdrawal->wallet_id))->toBe(500000)
        ->and(businessWithdrawalBucket('business_withdrawing', $withdrawal->wallet_id))->toBe(0)
        ->and(BusinessWithdrawalEvent::query()->pluck('disposition')->sort()->values()->all())->toBe(['applied', 'applied', 'key_conflict', 'mismatch'])
        ->and(PartyNotice::query()->where('party_id', $fixture['entity'])->where('kind', 'business.withdrawal_returned')->sole()->facts)
        ->toEqual(['amount' => '100000', 'currency' => 'RWF', 'received' => '100000', 'account' => 'MTN MoMo +250 788 ···· 456']);
    businessWithdrawalCommits();
});

it('refuses an unknown reference and an unsigned outcome', function (): void {
    $fixture = businessWithdrawalFixture();
    businessWithdraw($fixture);
    $withdrawal = BusinessWithdrawal::query()->sole();
    $signed = fn (array $fields): array => app(SyntheticWithdrawalSigner::class)->sign(['provider' => 'synthetic', 'event_id' => 'synthetic-event-x',
        'reference' => $withdrawal->provider_reference, 'state' => 'succeeded', 'amount' => '100000', 'currency' => 'RWF', 'environment' => 'testing',
        'observed_at' => now('UTC')->startOfSecond()->format(DATE_ATOM), ...$fields]);

    expect(fn () => app(ApplyBusinessWithdrawalOutcome::class)->handle($signed(['reference' => 'syw_unknown'])))
        ->toThrow(CommandRejection::class, 'WITHDRAWAL_REFERENCE_UNKNOWN')
        ->and(fn () => app(ApplyBusinessWithdrawalOutcome::class)->handle([...$signed([]), 'signature' => str_repeat('0', 64)]))
        ->toThrow(CommandRejection::class, 'PROVIDER_EVENT_UNVERIFIED')
        ->and(BusinessWithdrawalEvent::query()->count())->toBe(0);
});

it('refuses ledger postings not bound to a held Business withdrawal, and a withdrawal recorded without its hold', function (): void {
    $fixture = businessWithdrawalFixture();
    businessWithdraw($fixture);
    $withdrawal = BusinessWithdrawal::query()->sole();
    $post = function (string $kind, string $from, ?string $fromWallet, string $to, ?string $toWallet) use ($withdrawal): void {
        $id = strtolower((string) Str::ulid());
        DB::table('ledger_entries')->insert(['id' => $id, 'wallet_id' => $withdrawal->wallet_id, 'kind' => $kind, 'source_type' => 'business_withdrawal',
            'source_id' => $withdrawal->id, 'origin_operation_id' => $withdrawal->operation_id, 'currency' => 'RWF', 'payload' => '{}', 'sha256' => str_repeat('a', 64),
            'created_at' => now()]);
        foreach ([[$from, $fromWallet, 'debit'], [$to, $toWallet, 'credit']] as [$account, $wallet, $direction]) {
            DB::table('ledger_lines')->insert(['id' => strtolower((string) Str::ulid()), 'entry_id' => $id, 'direction' => $direction, 'amount' => $withdrawal->amount,
                'account_id' => LedgerAccount::query()->where('kind', $account)->where('wallet_id', $wallet)->value('id')
                    ?? tap(strtolower((string) Str::ulid()), fn (string $new) => DB::table('ledger_accounts')->insert(['id' => $new, 'wallet_id' => $wallet,
                        'kind' => $account, 'currency' => 'RWF', 'created_at' => now()])), 'created_at' => now()]);
        }
    };

    businessWithdrawalRejects(fn () => $post('business_withdrawal_payout', 'business_withdrawing', $withdrawal->wallet_id, 'withdrawal_clearing', null),
        'must follow its applied provider outcome');
    businessWithdrawalRejects(fn () => DB::table('business_withdrawals')->insert(['id' => strtolower((string) Str::ulid()), 'wallet_id' => $withdrawal->wallet_id,
        'business_id' => $withdrawal->business_id, 'party_id' => $withdrawal->party_id, 'method_id' => $withdrawal->method_id,
        'operation_id' => DB::table('command_operations')->where('command', 'business.wallet.deposit')->value('id'), 'request_id' => (string) Str::uuid(),
        'terms_version' => 'LED-R04/2026-10', 'amount' => 1000, 'provider_charge' => 0, 'received' => 1000, 'currency' => 'RWF', 'provider' => 'synthetic',
        'provider_reference' => 'x', 'provider_reference_sha256' => hash('sha256', Str::random()), 'payload' => '{}', 'sha256' => str_repeat('a', 64),
        'created_at' => now()]), 'exactly one hold');
});

it('sends queued Business withdrawals from the worker and never inside a transaction', function (): void {
    $fixture = businessWithdrawalFixture();
    businessWithdraw($fixture);
    $withdrawal = BusinessWithdrawal::query()->sole();

    expect(app(DispatchBusinessWithdrawals::class)->handle())->toBe(['claimed' => 0, 'acknowledged' => 0])
        ->and(app(DispatchBusinessWithdrawals::class)->backlog())->toBe(0)
        ->and(fn () => DB::transaction(fn () => app(DispatchBusinessWithdrawals::class)->handle($withdrawal->id)))
        ->toThrow(LogicException::class, 'WALLET_DISPATCH_TRANSACTION_OPEN');
});

it('offers withdrawal on the Business wallet only under the mandate permission, and quotes an entered amount', function (): void {
    $fixture = businessWithdrawalFixture('50000');
    $business = $fixture['business']->id;
    $props = fn (User $user, string $amount) => $this->actingAs($user)->get(route('business.wallet.show', ['business' => $business, 'kind' => 'withdraw', 'amount' => $amount]))
        ->assertOk()->viewData('page')['props'];

    $page = $props($fixture['user'], '20000');
    expect($page['allowed_actions'])->toBe(['business.wallet.deposit', 'business.wallet.withdraw'])
        ->and($page['funding']['kind'])->toBe('withdraw')->and($page['funding']['quote'])->toBeNull()
        ->and($page['funding']['withdrawal'])->toBe(['version' => 'LED-R04/2026-10', 'minimum' => ['currency' => 'RWF', 'amount' => '1000'],
            'maximum' => ['currency' => 'RWF', 'amount' => '2000000'], 'charge' => ['currency' => 'RWF', 'amount' => '0'],
            'quote' => ['amount' => ['currency' => 'RWF', 'amount' => '20000'], 'charge' => ['currency' => 'RWF', 'amount' => '0'],
                'received' => ['currency' => 'RWF', 'amount' => '20000'], 'refusal' => null]])
        ->and($props($fixture['user'], '60000')['funding']['withdrawal']['quote']['refusal'])->toBe('INSUFFICIENT_AVAILABLE_FUNDS')
        ->and($props($fixture['user'], '500')['funding']['withdrawal']['quote']['refusal'])->toBe('VALIDATION_FAILED')
        ->and($props($fixture['viewer'], '20000')['allowed_actions'])->toBe([]);

    $this->actingAs($fixture['viewer'])->postJson(route('business.wallet.withdraw', $business), ['request_id' => (string) Str::uuid(), 'identity_context_revision' => 1,
        'amount' => ['currency' => 'RWF', 'amount' => '1000'], 'method_id' => $fixture['method']->id])->assertForbidden()->assertJsonPath('code', 'ACTION_FORBIDDEN');
    expect(BusinessWithdrawal::query()->count())->toBe(0);
});

it('withdraws over HTTP, finds the recorded result by its request and shows the withdrawal until it is paid out', function (): void {
    $fixture = businessWithdrawalFixture();
    $business = $fixture['business']->id;
    $requestId = (string) Str::uuid();
    $body = ['request_id' => $requestId, 'identity_context_revision' => 1, 'amount' => ['currency' => 'RWF', 'amount' => '120000'], 'method_id' => $fixture['method']->id];

    $posted = $this->actingAs($fixture['user'])->postJson(route('business.wallet.withdraw', $business), $body)->assertOk()->json();
    $withdrawal = BusinessWithdrawal::query()->sole();
    $lookup = '/business/'.$business.'/wallet-operations/'.$requestId.'?command=business.wallet.withdraw&identity_context_revision=1';
    expect($posted['status'])->toBe('completed')->and($posted['data']['receipt']['code'])->toBe('WITHDRAWAL_RECORDED')
        ->and($posted['data']['receipt']['link']['url'])->toBe($lookup)
        ->and($posted['data']['current'])->toMatchArray(['id' => $withdrawal->id, 'state' => 'pending', 'settled_receipt' => null])
        ->and($posted['allowed_actions'])->toBe(['business.wallet.deposit', 'business.wallet.withdraw'])
        ->and($this->getJson($lookup)->assertOk()->json('data.receipt.receipt_id'))->toBe($withdrawal->id);
    $this->getJson(str_replace($requestId, (string) Str::uuid(), $lookup))->assertNotFound();

    $page = $this->get(route('business.wallet.show', $business))->viewData('page')['props'];
    expect($page['wallet']['available'])->toBe(['currency' => 'RWF', 'amount' => '380000'])
        ->and($page['wallet']['pending_withdrawals'])->toBe(['currency' => 'RWF', 'amount' => '120000'])
        ->and($page['withdrawals'][0])->toMatchArray(['id' => $withdrawal->id, 'state' => 'pending',
            'link' => ['url' => '/business/'.$business.'/wallet?receipt='.$withdrawal->id, 'method' => 'get']])
        ->and($page['withdrawals'][0]['request_receipt']['link']['url'])->toBe($lookup)
        ->and(json_encode($page, JSON_THROW_ON_ERROR))->not->toContain('syw_');

    businessWithdrawalOutcome($withdrawal->id);
    $payout = LedgerEntry::query()->where('kind', 'business_withdrawal_payout')->sole();
    $page = $this->get(route('business.wallet.show', $business))->viewData('page')['props'];
    expect($page['wallet']['pending_withdrawals']['amount'])->toBe('0')
        ->and($page['withdrawals'][0]['settled_receipt'])->toMatchArray(['receipt_id' => $payout->id, 'code' => 'WITHDRAWAL_PAID',
            'reference' => 'RZW-'.strtoupper(substr($payout->id, -10))]);

    $opened = $this->get(route('business.wallet.show', ['business' => $business, 'receipt' => $withdrawal->id]))->viewData('page')['props']['receipt'];
    expect($opened['request_receipt']['code'])->toBe('WITHDRAWAL_RECORDED')->and($opened['settled_receipt']['code'])->toBe('WITHDRAWAL_PAID');
    $foreign = businessWithdrawalFixture();
    businessWithdraw($foreign);
    $elsewhere = BusinessWithdrawal::query()->where('business_id', $foreign['business']->id)->sole();
    expect($this->get(route('business.wallet.show', ['business' => $business, 'receipt' => $elsewhere->id]))->viewData('page')['props']['receipt'])->toBeNull();

    $this->actingAs($fixture['user'])->postJson(route('business.wallet.withdraw', $business), [...$body, 'amount' => ['currency' => 'RWF', 'amount' => '1000']])
        ->assertStatus(409)->assertJsonPath('code', 'IDEMPOTENCY_CONFLICT');
});

it('refuses a Business withdrawal from an API token without the command ability', function (): void {
    $fixture = businessWithdrawalFixture();
    Sanctum::actingAs($fixture['user'], ['business:read']);

    $this->postJson(route('api.v1.business.wallet.withdraw', $fixture['business']->id), ['request_id' => (string) Str::uuid(), 'identity_context_revision' => 1,
        'amount' => ['currency' => 'RWF', 'amount' => '1000'], 'method_id' => $fixture['method']->id])->assertForbidden();
    expect($this->getJson(route('api.v1.business.wallet.show', $fixture['business']->id))->assertOk()->json('data.allowed_actions'))->toBe([])
        ->and(BusinessWithdrawal::query()->count())->toBe(0);
});

it('settles a Business withdrawal accepted before the staging switch through the outbox worker', function (): void {
    $fixture = businessWithdrawalFixture();
    businessWithdraw($fixture, '30000');
    $isolation = Mockery::mock(EnvironmentIsolation::class.'[profile]', [app(), app('config')]);
    $isolation->allows(['profile' => 'uat']);
    app()->instance(EnvironmentIsolation::class, $isolation);
    app()->forgetInstance(SyntheticSettlement::class);

    expect(Artisan::call('wallet:dispatch-deposits'))->toBe(0)->and(Artisan::output())->toContain('Claimed 0 Business withdrawal dispatches; 0 acknowledged.')
        ->toContain('Settled 1 synthetic deposits; 0 left for the next run.')
        ->and(BusinessWithdrawalEvent::query()->sole()->only(['state', 'disposition', 'environment', 'amount']))
        ->toBe(['state' => 'succeeded', 'disposition' => 'applied', 'environment' => 'uat', 'amount' => '30000'])
        ->and(LedgerEntry::query()->where('kind', 'business_withdrawal_payout')->count())->toBe(1);
});
