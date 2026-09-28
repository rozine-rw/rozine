<?php

declare(strict_types=1);

use App\Models\InvestorAccountRestriction;
use App\Models\InvestorFundingMethod;
use App\Models\InvestorWallet;
use App\Models\LedgerEntry;
use App\Models\User;
use App\Models\WalletDepositCredit;
use App\Models\WalletDepositIntent;
use App\Models\WalletProviderEvent;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;
use Symfony\Component\HttpFoundation\Response;
use Tests\Support\InvestorWalletFixture;

/**
 * @param  TestResponse<Response>  $response
 * @return array<string, mixed>
 */
function walletPage(TestResponse $response): array
{
    $page = $response->assertOk()->viewData('page');

    expect($page['component'])->toBe('investor/wallet');

    return $page['props'];
}

/**
 * @param  array{method: InvestorFundingMethod}  $fixture
 * @return array<string, mixed>
 */
function walletDepositBody(array $fixture, string $amount = '50000', ?string $requestId = null): array
{
    return ['request_id' => $requestId ?? (string) Str::uuid(), 'identity_context_revision' => 1,
        'amount' => ['currency' => 'RWF', 'amount' => $amount], 'method_id' => $fixture['method']->id];
}

it('renders the live wallet with real routes, the literal lookup token and nothing from preview', function (): void {
    $fixture = InvestorWalletFixture::ready();
    $response = $this->actingAs($fixture['user'])->get(route('investor.wallet'));
    $props = walletPage($response);
    $json = json_encode($props, JSON_THROW_ON_ERROR);

    expect($response->headers->get('Cache-Control'))->toContain('no-store')->toContain('private')
        ->and($props['contract_version'])->toBe('investor-primary-v1')->and($props['identity_context_revision'])->toBe(1)
        ->and($props['allowed_actions'])->toBe(['wallet.deposit'])->and($props)->not->toHaveKey('preview_outcome')
        ->and($json)->not->toContain('/preview/')->not->toContain('syn_')
        ->and($props['links'])->toMatchArray(['deals' => null, 'portfolio' => null, 'profile' => null, 'notifications' => null, 'link_account' => null,
            'wallet' => ['url' => '/investor/wallet', 'method' => 'get'], 'launcher' => ['url' => '/dashboard', 'method' => 'get'],
            'close' => ['url' => '/investor/wallet', 'method' => 'get'], 'deposit' => ['url' => '/investor/wallet?kind=deposit', 'method' => 'get'],
            'operation' => ['url' => '/investor/wallet-operations/{request_id}?command=wallet.deposit&identity_context_revision=1', 'method' => 'get']])
        ->and($props['actions'])->toBe(['deposit' => ['url' => '/investor/wallet/deposits', 'method' => 'post']])
        ->and($props['wallet'])->toBe(['revision' => 0, 'status' => 'active', 'restriction' => null, 'total' => ['currency' => 'RWF', 'amount' => '0'],
            'breakdown' => ['available' => ['currency' => 'RWF', 'amount' => '0'], 'held' => ['currency' => 'RWF', 'amount' => '0'], 'committed' => ['currency' => 'RWF', 'amount' => '0']],
            'pending_deposits' => ['currency' => 'RWF', 'amount' => '0']])
        ->and($props['funding'])->toBe(['kind' => null, 'policy' => ['version' => $fixture['policy']->version, 'synthetic' => true,
            'fee' => ['currency' => 'RWF', 'amount' => '0'], 'minimum' => ['currency' => 'RWF', 'amount' => '1000'], 'maximum' => ['currency' => 'RWF', 'amount' => '1000000']],
            'methods' => [['id' => $fixture['method']->id, 'kind' => 'mtn', 'label' => 'MTN MoMo', 'masked' => '+250 788 ···· 456']], 'picks' => [], 'quote' => null])
        ->and([$props['holds'], $props['deposits'], $props['receipt'], $props['earnings'], $props['exports']])->toBe([[], [], null, null, null])
        ->and(InvestorWallet::query()->where('party_id', $fixture['party']->id)->exists())->toBeFalse();
});

it('prices the entered amount from the query, as the page reloads only funding', function (array $query, ?array $quote): void {
    $fixture = InvestorWalletFixture::ready('150');
    $response = $this->actingAs($fixture['user'])->get(route('investor.wallet', $query));

    expect(walletPage($response)['funding']['quote'])->toBe($quote);
})->with([
    'priced' => [['kind' => 'deposit', 'amount' => '5000'], ['amount' => ['currency' => 'RWF', 'amount' => '5000'], 'fee' => ['currency' => 'RWF', 'amount' => '150'],
        'credited' => ['currency' => 'RWF', 'amount' => '4850'], 'refusal' => null]],
    'leading zeros' => [['kind' => 'deposit', 'amount' => '005000'], ['amount' => ['currency' => 'RWF', 'amount' => '5000'], 'fee' => ['currency' => 'RWF', 'amount' => '150'],
        'credited' => ['currency' => 'RWF', 'amount' => '4850'], 'refusal' => null]],
    'below minimum' => [['kind' => 'deposit', 'amount' => '100'], ['amount' => ['currency' => 'RWF', 'amount' => '100'], 'fee' => ['currency' => 'RWF', 'amount' => '150'],
        'credited' => ['currency' => 'RWF', 'amount' => '0'], 'refusal' => 'VALIDATION_FAILED']],
    'zero' => [['kind' => 'deposit', 'amount' => '0'], ['amount' => ['currency' => 'RWF', 'amount' => '0'], 'fee' => ['currency' => 'RWF', 'amount' => '150'],
        'credited' => ['currency' => 'RWF', 'amount' => '0'], 'refusal' => 'VALIDATION_FAILED']],
    'empty' => [['kind' => 'deposit', 'amount' => ''], null],
    'no kind' => [['amount' => '5000'], null],
]);

it('refuses the quote without a verified method and offers no deposit then', function (): void {
    InvestorWalletFixture::policy();
    $investor = InvestorWalletFixture::investor();
    InvestorFundingMethod::factory()->revoked()->create(['party_id' => $investor['party']->id]);
    $props = walletPage($this->actingAs($investor['user'])->get(route('investor.wallet', ['kind' => 'deposit', 'amount' => '5000'])));

    expect($props['allowed_actions'])->toBe([])->and($props['funding']['methods'])->toBe([])->and($props['funding']['kind'])->toBe('deposit')
        ->and($props['funding']['quote']['refusal'])->toBe('DEPOSIT_METHOD_UNVERIFIED');
});

it('offers no deposit form without a policy and refuses a direct post POLICY_INPUT_REQUIRED', function (): void {
    $investor = InvestorWalletFixture::investor();
    $fixture = [...$investor, 'method' => InvestorWalletFixture::method($investor['party'])];
    $props = walletPage($this->actingAs($fixture['user'])->get(route('investor.wallet', ['kind' => 'deposit', 'amount' => '5000'])));
    expect($props['allowed_actions'])->toBe([])->and($props['funding']['policy'])->toBeNull()->and($props['funding']['quote'])->toBeNull();

    $this->postJson(route('investor.wallet.deposit'), walletDepositBody($fixture))->assertStatus(409)
        ->assertJsonPath('status', 'rejected')->assertJsonPath('code', 'POLICY_INPUT_REQUIRED')
        ->assertJsonPath('data', ['receipt' => null, 'current' => null, 'next' => null]);
    expect(WalletDepositIntent::query()->count())->toBe(0);
});

it('records a deposit over HTTP and answers the immutable receipt beside the fresh pending intent', function (): void {
    $fixture = InvestorWalletFixture::ready();
    $body = walletDepositBody($fixture);
    $response = $this->actingAs($fixture['user'])->postJson(route('investor.wallet.deposit'), $body)->assertOk()
        ->assertJsonPath('status', 'completed')->assertJsonPath('code', 'DEPOSIT_INTENT_RECORDED')->assertJsonPath('data.next', null)
        ->assertJsonPath('allowed_actions', ['wallet.deposit']);
    $intent = WalletDepositIntent::query()->sole();
    $data = $response->json('data');

    expect($data['receipt'])->toMatchArray(['receipt_id' => $intent->id, 'operation_id' => $intent->operation_id, 'request_id' => $body['request_id'],
        'code' => 'DEPOSIT_INTENT_RECORDED', 'link' => ['url' => '/investor/wallet-operations/'.$body['request_id'].'?command=wallet.deposit&identity_context_revision=1', 'method' => 'get']])
        ->and($data['current'])->toMatchArray(['id' => $intent->id, 'request_id' => $body['request_id'], 'state' => 'pending', 'credit_receipt' => null,
            'amount' => ['currency' => 'RWF', 'amount' => '50000'], 'link' => ['url' => '/investor/wallet?receipt='.$intent->id, 'method' => 'get']])
        ->and($response->getContent())->not->toContain($intent->provider_reference)->not->toContain('/preview/')
        ->and(LedgerEntry::query()->count())->toBe(0);

    $this->postJson(route('investor.wallet.deposit'), $body)->assertOk()->assertJsonPath('operation_id', $response->json('operation_id'));
    $this->postJson(route('investor.wallet.deposit'), [...$body, 'amount' => ['currency' => 'RWF', 'amount' => '60000']])->assertStatus(409)
        ->assertJsonPath('code', 'IDEMPOTENCY_CONFLICT');
    $this->postJson(route('investor.wallet.deposit'), walletDepositBody($fixture, '999'))->assertStatus(422)
        ->assertJsonPath('code', 'VALIDATION_FAILED')->assertJsonPath('errors.amount', ['Deposit at least RWF 1000.']);
    $this->postJson(route('investor.wallet.deposit'), [...$body, 'request_id' => (string) Str::uuid(), 'amount' => ['currency' => 'USD', 'amount' => 50000]])
        ->assertStatus(422)->assertJsonValidationErrors(['amount.currency', 'amount.amount']);
    $this->postJson(route('investor.wallet.deposit'), [...$body, 'request_id' => (string) Str::uuid(), 'party_id' => 'x', 'provider_reference' => 'y',
        'amount' => ['currency' => 'RWF', 'amount' => '5000', 'fee' => '0']])->assertStatus(422)->assertJsonValidationErrors(['amount']);
    expect(WalletDepositIntent::query()->count())->toBe(1);
});

it('resolves a lost answer by lookup without resending, keeping the original receipt and adding the credit receipt to current only', function (): void {
    $fixture = InvestorWalletFixture::ready();
    $body = walletDepositBody($fixture);
    $recorded = $this->actingAs($fixture['user'])->postJson(route('investor.wallet.deposit'), $body)->json();
    $lookup = route('investor.wallet.operations.show', ['request_id' => $body['request_id'], 'command' => 'wallet.deposit', 'identity_context_revision' => 1]);
    $before = $this->getJson($lookup)->assertOk()->json();
    InvestorWalletFixture::settle($recorded['data']['receipt']['receipt_id']);
    $after = $this->getJson($lookup)->assertOk()->json();
    $credit = WalletDepositCredit::query()->sole();

    expect($before['operation_id'])->toBe($recorded['operation_id'])->and($before['data']['receipt'])->toBe($recorded['data']['receipt'])
        ->and($after['data']['receipt'])->toBe($recorded['data']['receipt'])->and($after['code'])->toBe('DEPOSIT_INTENT_RECORDED')
        ->and($after['data']['current']['state'])->toBe('succeeded')
        ->and($after['data']['current']['credit_receipt'])->toMatchArray(['receipt_id' => $credit->id, 'code' => 'DEPOSIT_CREDITED',
            'operation_id' => $recorded['operation_id'], 'request_id' => $body['request_id'], 'amount' => ['currency' => 'RWF', 'amount' => '50000'],
            'link' => ['url' => '/investor/wallet?receipt='.$credit->id, 'method' => 'get']])
        ->and($credit->id)->not->toBe($recorded['data']['receipt']['receipt_id'])->not->toBe($credit->ledger_entry_id)
        ->and(json_encode($after))->not->toContain($credit->ledger_entry_id)->not->toContain($credit->provider_event_id)
        ->and(WalletDepositIntent::query()->count())->toBe(1);

    $other = InvestorWalletFixture::ready();
    $this->actingAs($other['user'])->getJson($lookup)->assertNotFound()->assertJsonPath('code', 'OPERATION_NOT_FOUND');
    $this->actingAs($fixture['user'])->getJson(route('investor.wallet.operations.show', ['request_id' => (string) Str::uuid(), 'command' => 'wallet.deposit', 'identity_context_revision' => 1]))
        ->assertNotFound()->assertJsonPath('code', 'OPERATION_NOT_FOUND');
    $this->getJson(route('investor.wallet.operations.show', ['request_id' => $body['request_id'], 'command' => 'campaign.cancel', 'identity_context_revision' => 1]))
        ->assertNotFound();
    $this->getJson(route('investor.wallet.operations.show', ['request_id' => $body['request_id'], 'command' => 'wallet.deposit', 'identity_context_revision' => 0]))
        ->assertStatus(409)->assertJsonPath('code', 'ACTIVE_ROLE_REVISION_CONFLICT');
    $this->getJson(route('investor.wallet.operations.show', ['request_id' => $body['request_id']]))->assertStatus(422);
});

it('shows credited money in the total, the credit in history and owner-scoped receipts', function (): void {
    $fixture = InvestorWalletFixture::ready();
    $pending = InvestorWalletFixture::deposit($fixture, '20000');
    $credited = InvestorWalletFixture::deposit($fixture, '50000');
    InvestorWalletFixture::settle($credited['data']['intent_id']);
    $failed = InvestorWalletFixture::deposit($fixture, '30000');
    InvestorWalletFixture::settle($failed['data']['intent_id'], 'failed');
    $credit = WalletDepositCredit::query()->sole();
    $props = walletPage($this->actingAs($fixture['user'])->get(route('investor.wallet')));

    expect($props['wallet']['total'])->toBe(['currency' => 'RWF', 'amount' => '50000'])
        ->and($props['wallet']['breakdown']['available'])->toBe(['currency' => 'RWF', 'amount' => '50000'])
        ->and($props['wallet']['pending_deposits'])->toBe(['currency' => 'RWF', 'amount' => '20000'])->and($props['wallet']['revision'])->toBe(1)
        ->and(array_column($props['deposits'], 'state'))->toBe(['pending', 'failed', 'succeeded'])
        ->and($props['deposits'][0]['id'])->toBe($pending['data']['intent_id'])
        ->and($props['history']['items'])->toBe([['id' => $credit->id, 'movement' => 'external', 'kind' => 'deposit', 'direction' => 'in',
            'amount' => ['currency' => 'RWF', 'amount' => '50000'], 'counterparty' => 'MTN MoMo', 'occurred_at' => $credit->payload['recorded_at'],
            'link' => ['url' => '/investor/wallet?receipt='.$credit->id, 'method' => 'get']]]);

    $entry = walletPage($this->get(route('investor.wallet', ['receipt' => $credit->id])))['receipt'];
    $intent = walletPage($this->get(route('investor.wallet', ['receipt' => strtoupper($pending['data']['intent_id'])])))['receipt'];
    expect($entry['id'])->toBe($credit->id)->and($entry['receipt']['code'])->toBe('DEPOSIT_CREDITED')
        ->and($intent['id'])->toBe($pending['data']['intent_id'])->and($intent['intent_receipt']['code'])->toBe('DEPOSIT_INTENT_RECORDED');

    $other = InvestorWalletFixture::ready();
    InvestorWalletFixture::deposit($other);
    foreach ([$credit->id, $pending['data']['intent_id']] as $foreign) {
        expect(walletPage($this->actingAs($other['user'])->get(route('investor.wallet', ['receipt' => $foreign])))['receipt'])->toBeNull();
    }
    expect(walletPage($this->actingAs($fixture['user'])->get(route('investor.wallet', ['receipt' => strtolower((string) Str::ulid())])))['receipt'])->toBeNull();
});

it('pages external history and keeps internal movements apart', function (): void {
    $fixture = InvestorWalletFixture::ready();
    foreach (range(1, 21) as $index) {
        InvestorWalletFixture::settle(InvestorWalletFixture::deposit($fixture, (string) (1000 + $index))['data']['intent_id']);
    }
    $first = walletPage($this->actingAs($fixture['user'])->get(route('investor.wallet')));
    $next = $first['history']['pagination']['next'];
    $second = walletPage($this->get($next['url']));
    $internal = walletPage($this->get(route('investor.wallet', ['movement' => 'internal'])));

    expect($first['history']['items'])->toHaveCount(20)->and($next['url'])->toStartWith('/investor/wallet?movement=external&before=')
        ->and($second['history']['items'])->toHaveCount(1)->and($second['history']['pagination']['next'])->toBeNull()
        ->and($second['history']['items'][0]['amount']['amount'])->toBe('1001')
        ->and($internal['history']['movement'])->toBe('internal')->and($internal['history']['items'])->toBe([])
        ->and($internal['history']['filters'])->toBe([['key' => 'external', 'active' => false, 'link' => ['url' => '/investor/wallet?movement=external', 'method' => 'get']],
            ['key' => 'internal', 'active' => true, 'link' => ['url' => '/investor/wallet?movement=internal', 'method' => 'get']]])
        ->and($first['deposits'])->toHaveCount(20)->and($first['wallet']['total']['amount'])->toBe((string) array_sum(range(1001, 1021)));
});

it('keeps deposit open for a restricted wallet under the 11.4 hold, and closes it for an order covering deposits', function (): void {
    $fixture = InvestorWalletFixture::ready();
    $hold = InvestorAccountRestriction::factory()->create(['party_id' => $fixture['party']->id, 'effective_at' => now()->subHour()->startOfSecond()]);
    $props = walletPage($this->actingAs($fixture['user'])->get(route('investor.wallet')));
    expect($props['wallet']['status'])->toBe('restricted')
        ->and($props['wallet']['restriction'])->toBe(['code' => 'RESTRICTION_ACTIVE', 'since' => $hold->effective_at->toIso8601String()])
        ->and($props['allowed_actions'])->toBe(['wallet.deposit']);
    $this->postJson(route('investor.wallet.deposit'), walletDepositBody($fixture))->assertOk()->assertJsonPath('code', 'DEPOSIT_INTENT_RECORDED');
    expect(LedgerEntry::query()->count())->toBe(0);

    InvestorAccountRestriction::factory()->externalOrder()->create(['party_id' => $fixture['party']->id]);
    expect(walletPage($this->get(route('investor.wallet')))['allowed_actions'])->toBe([]);
    $this->postJson(route('investor.wallet.deposit'), walletDepositBody($fixture))->assertStatus(409)->assertJsonPath('code', 'RESTRICTION_ACTIVE');
});

it('shows the current case only: an expired restriction leaves the wallet active', function (): void {
    $fixture = InvestorWalletFixture::ready();
    InvestorAccountRestriction::factory()->create(['party_id' => $fixture['party']->id, 'effective_at' => now()->subDays(2), 'expires_at' => now()->subDay()]);
    expect(walletPage($this->actingAs($fixture['user'])->get(route('investor.wallet')))['wallet'])->toMatchArray(['status' => 'active', 'restriction' => null]);
});

it('serves the same Resource over the API under investor token abilities only', function (): void {
    $fixture = InvestorWalletFixture::ready();
    Sanctum::actingAs($fixture['user'], ['investor:read']);
    $read = $this->getJson(route('api.v1.investor.wallet'))->assertOk()->json('data');
    expect($read['allowed_actions'])->toBe([])->and($read['links']['launcher'])->toBe(['url' => '/api/v1/identity', 'method' => 'get'])
        ->and($read['links']['operation']['url'])->toBe('/api/v1/investor/wallet-operations/{request_id}?command=wallet.deposit&identity_context_revision=1')
        ->and($read['actions']['deposit']['url'])->toBe('/api/v1/investor/wallet/deposits');
    $this->postJson(route('api.v1.investor.wallet.deposit'), walletDepositBody($fixture))->assertForbidden();

    Sanctum::actingAs($fixture['user'], ['investor:read', 'investor:command']);
    $body = walletDepositBody($fixture);
    $this->postJson(route('api.v1.investor.wallet.deposit'), $body)->assertOk()->assertJsonPath('allowed_actions', ['wallet.deposit'])
        ->assertJsonPath('data.receipt.link.url', '/api/v1/investor/wallet-operations/'.$body['request_id'].'?command=wallet.deposit&identity_context_revision=1');
    expect($this->getJson(route('api.v1.investor.wallet'))->json('data.allowed_actions'))->toBe(['wallet.deposit']);

    Sanctum::actingAs($fixture['user'], ['investor:read']);
    $this->getJson(route('api.v1.investor.wallet.operations.show', ['request_id' => $body['request_id'], 'command' => 'wallet.deposit', 'identity_context_revision' => 1]))
        ->assertOk()->assertJsonPath('allowed_actions', []);
    Sanctum::actingAs($fixture['user'], ['business:read']);
    $this->getJson(route('api.v1.investor.wallet'))->assertForbidden();
    $this->getJson(route('api.v1.investor.wallet.operations.show', ['request_id' => $body['request_id'], 'command' => 'wallet.deposit', 'identity_context_revision' => 1]))->assertForbidden();
});

it('denies the wallet page to someone without current investor authority', function (): void {
    $fixture = InvestorWalletFixture::ready();
    $this->actingAs(User::factory()->create())->get(route('investor.wallet'))->assertForbidden()
        ->assertInertia(fn (Assert $page) => $page->component('identity/access-denied'));
    $this->actingAs($fixture['user'])->get(route('investor.wallet', ['identity_context_revision' => 0]))->assertStatus(409)
        ->assertInertia(fn (Assert $page) => $page->component('identity/access-denied')->where('code', 'ACTIVE_ROLE_REVISION_CONFLICT'));
    $this->getJson(route('investor.wallet', ['identity_context_revision' => 0]))->assertStatus(409)->assertJsonPath('code', 'ACTIVE_ROLE_REVISION_CONFLICT');
    $this->postJson(route('investor.wallet.deposit'), [...walletDepositBody($fixture), 'identity_context_revision' => 0])->assertStatus(409)
        ->assertJsonPath('code', 'ACTIVE_ROLE_REVISION_CONFLICT');
    auth()->logout();
    $this->get(route('investor.wallet'))->assertRedirect(route('login'));
});

it('fails closed on an intent or credit whose sealed facts do not match', function (bool $credit): void {
    $fixture = InvestorWalletFixture::ready();
    if ($credit) {
        $intent = WalletDepositIntent::query()->whereKey((string) InvestorWalletFixture::deposit($fixture)['data']['intent_id'])->sole();
        WalletDepositCredit::factory()->create(['intent_id' => $intent->id,
            'provider_event_id' => WalletProviderEvent::factory()->create(['intent_id' => $intent->id, 'state' => 'succeeded'])->id]);
    } else {
        WalletDepositIntent::factory()->create(['wallet_id' => InvestorWallet::factory()->create(['party_id' => $fixture['party']->id])->id]);
    }
    $this->withoutExceptionHandling();
    expect(fn () => $this->actingAs($fixture['user'])->get(route('investor.wallet')))
        ->toThrow(RuntimeException::class, $credit ? 'WALLET_CREDIT_INTEGRITY_FAILED' : 'WALLET_DEPOSIT_INTEGRITY_FAILED');
})->with(['intent' => false, 'credit' => true]);
