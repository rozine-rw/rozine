<?php

declare(strict_types=1);

use App\Application\Operations\Contracts\CanonicalJson;
use App\Models\BusinessDepositCredit;
use App\Models\BusinessDepositDispatch;
use App\Models\BusinessDepositIntent;
use App\Models\BusinessFundingMethod;
use App\Models\BusinessProfile;
use App\Models\BusinessProviderEvent;
use App\Models\BusinessWallet;
use App\Models\CommandOperation;
use App\Models\DepositPolicy;
use App\Models\LedgerAccount;
use App\Models\LedgerEntry;
use App\Models\LedgerLine;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use Symfony\Component\HttpFoundation\Response;
use Tests\Support\BusinessAuthorityFixture;

/**
 * A configured Business with two mandate holders: the first may deposit, the second may only view.
 *
 * @return array{business: BusinessProfile, depositor: User, viewer: User, depositor_party: string, method: BusinessFundingMethod, policy: DepositPolicy}
 */
function businessWalletFixture(bool $policy = true): array
{
    $authority = BusinessAuthorityFixture::make('organization', 2, 'COMPANY-'.Str::ulid(), 1);
    $viewer = $authority['people'][1]->id;
    $authority['terms']['people'] = array_map(fn (array $person): array => $person['party_id'] === $viewer ? [...$person, 'permissions' => ['business.view']] : $person,
        $authority['terms']['people']);
    BusinessAuthorityFixture::configure($authority);
    $business = BusinessProfile::query()->where('entity_party_id', $authority['entity'])->firstOrFail();
    $active = $policy ? DepositPolicy::factory()->create(['fee' => '0', 'minimum' => '1000', 'maximum' => '1000000']) : DepositPolicy::factory()->withdrawn()->create();

    return ['business' => $business, 'depositor' => $authority['users'][0], 'viewer' => $authority['users'][1], 'depositor_party' => $authority['people'][0]->id,
        'method' => BusinessFundingMethod::factory()->create(['business_id' => $business->id]), 'policy' => $active];
}

/**
 * @param  array{method: BusinessFundingMethod}  $fixture
 * @return array<string, mixed>
 */
function businessWalletDepositBody(array $fixture, string $amount = '50000', ?string $requestId = null, ?BusinessFundingMethod $method = null): array
{
    return ['request_id' => $requestId ?? (string) Str::uuid(), 'identity_context_revision' => 1,
        'amount' => ['currency' => 'RWF', 'amount' => $amount], 'method_id' => ($method ?? $fixture['method'])->id];
}

/**
 * @param  TestResponse<Response>  $response
 * @return array<string, mixed>
 */
function businessWalletPage(TestResponse $response): array
{
    $page = $response->assertOk()->viewData('page');

    expect($page['component'])->toBe('business/wallet');

    return $page['props'];
}

it('renders the Business wallet with real routes, the literal lookup token and no wallet created by reading', function (): void {
    $fixture = businessWalletFixture();
    $business = $fixture['business']->id;
    $props = businessWalletPage($this->actingAs($fixture['depositor'])->get(route('business.wallet.show', $business)));

    expect($props['contract_version'])->toBe('business-servicing-v1')->and($props['identity_context_revision'])->toBe(1)
        ->and($props['allowed_actions'])->toBe(['business.wallet.deposit'])
        ->and($props['business'])->toBe(['id' => $business, 'name' => 'Synthetic business'])
        ->and($props['wallet'])->toBe(['revision' => 0, 'status' => 'active', 'restriction' => null,
            'available' => ['currency' => 'RWF', 'amount' => '0'], 'pending_deposits' => ['currency' => 'RWF', 'amount' => '0']])
        ->and($props['funding']['policy']['version'])->toBe($fixture['policy']->version)
        ->and($props['funding']['methods'])->toBe([['id' => $fixture['method']->id, 'kind' => 'mtn', 'label' => 'MTN MoMo', 'masked' => '+250 788 ···· 456']])
        ->and([$props['deposits'], $props['receipt'], $props['history']['items']])->toBe([[], null, []])
        ->and($props['links'])->toMatchArray(['close' => ['url' => '/business', 'method' => 'get'], 'repayments' => null,
            'deposit' => ['url' => '/business/'.$business.'/wallet?kind=deposit', 'method' => 'get'],
            'operation' => ['url' => '/business/'.$business.'/wallet-operations/{request_id}?command=business.wallet.deposit&identity_context_revision=1', 'method' => 'get']])
        ->and($props['actions'])->toBe(['deposit' => ['url' => '/business/'.$business.'/wallet/deposits', 'method' => 'post']])
        ->and($props['shell_links']['launcher'])->toBe(['url' => '/dashboard', 'method' => 'get'])
        ->and(json_encode($props, JSON_THROW_ON_ERROR))->not->toContain('/preview/')->not->toContain('syn_')
        ->and(BusinessWallet::query()->where('business_id', $business)->exists())->toBeFalse();
});

it('offers no deposit without an active policy or the mandate permission, and refuses the command accordingly', function (): void {
    $fixture = businessWalletFixture(policy: false);
    $business = $fixture['business']->id;

    expect(businessWalletPage($this->actingAs($fixture['depositor'])->get(route('business.wallet.show', $business)))['allowed_actions'])->toBe([])
        ->and(businessWalletPage($this->actingAs($fixture['viewer'])->get(route('business.wallet.show', $business)))['allowed_actions'])->toBe([]);
    $this->actingAs($fixture['depositor'])->postJson(route('business.wallet.deposit', $business), businessWalletDepositBody($fixture))
        ->assertStatus(409)->assertJsonPath('code', 'POLICY_INPUT_REQUIRED');
    $this->actingAs($fixture['viewer'])->postJson(route('business.wallet.deposit', $business), businessWalletDepositBody($fixture))
        ->assertForbidden()->assertJsonPath('code', 'ACTION_FORBIDDEN');
    expect(BusinessDepositIntent::query()->count())->toBe(0);
});

it('records a deposit intent under the acting Party, registers its reference and dispatches it after commit without crediting', function (): void {
    $fixture = businessWalletFixture();
    $business = $fixture['business']->id;
    $body = businessWalletDepositBody($fixture);
    $response = $this->actingAs($fixture['depositor'])->postJson(route('business.wallet.deposit', $business), $body)->assertOk();
    $intent = BusinessDepositIntent::query()->sole();
    $operation = CommandOperation::query()->where('command', 'business.wallet.deposit')->sole();

    expect($response->json('code'))->toBe('DEPOSIT_INTENT_RECORDED')->and($response->json('data.receipt.code'))->toBe('DEPOSIT_INTENT_RECORDED')
        ->and($response->json('data.current.state'))->toBe('pending')->and($response->json('data.next'))->toBeNull()
        ->and([$operation->actor_key, $operation->command, $operation->target_type, $operation->target_id])
        ->toBe(['party:'.$fixture['depositor_party'], 'business.wallet.deposit', 'business_wallet', $intent->wallet_id])
        ->and([$intent->business_id, $intent->party_id, $intent->amount, $intent->credited])->toBe([$business, $fixture['depositor_party'], '50000', '50000'])
        ->and(DB::table('provider_references')->where('intent_id', $intent->id)->value('owner'))->toBe('business')
        ->and(BusinessDepositDispatch::query()->where('intent_id', $intent->id)->orderBy('id')->pluck('phase')->all())->toBe(['queued', 'claimed', 'acknowledged']);
    $props = businessWalletPage($this->actingAs($fixture['depositor'])->get(route('business.wallet.show', $business)));
    expect($props['wallet']['available'])->toBe(['currency' => 'RWF', 'amount' => '0'])
        ->and($props['wallet']['pending_deposits'])->toBe(['currency' => 'RWF', 'amount' => '50000'])
        ->and($props['deposits'][0]['state'])->toBe('pending');

    $this->actingAs($fixture['depositor'])->postJson(route('business.wallet.deposit', $business), $body)->assertOk()
        ->assertJsonPath('data.receipt.receipt_id', $intent->id);
    expect(BusinessDepositIntent::query()->count())->toBe(1);
});

it('finds a deposit only for the Party that requested it, under current authority', function (): void {
    $fixture = businessWalletFixture();
    $business = $fixture['business']->id;
    $body = businessWalletDepositBody($fixture);
    $this->actingAs($fixture['depositor'])->postJson(route('business.wallet.deposit', $business), $body)->assertOk();
    $lookup = route('business.wallet.operations.show', ['business' => $business, 'request_id' => $body['request_id'],
        'command' => 'business.wallet.deposit', 'identity_context_revision' => 1]);

    $this->actingAs($fixture['depositor'])->getJson($lookup)->assertOk()->assertJsonPath('code', 'DEPOSIT_INTENT_RECORDED');
    $this->actingAs($fixture['viewer'])->getJson($lookup)->assertNotFound()->assertJsonPath('code', 'OPERATION_NOT_FOUND');
    $this->actingAs($fixture['depositor'])->getJson(str_replace('business.wallet.deposit', 'wallet.deposit', $lookup))
        ->assertNotFound()->assertJsonPath('code', 'OPERATION_NOT_FOUND');
});

it('refuses a stranger, another Business method and an amount outside the policy', function (): void {
    $fixture = businessWalletFixture();
    $business = $fixture['business']->id;
    $other = BusinessFundingMethod::factory()->create();

    $stranger = BusinessAuthorityFixture::make();
    BusinessAuthorityFixture::configure($stranger);

    $this->actingAs($stranger['users'][0])->get(route('business.wallet.show', $business))->assertNotFound();
    $this->actingAs(User::factory()->create())->get(route('business.wallet.show', $business))->assertForbidden();
    $this->actingAs($fixture['depositor'])->postJson(route('business.wallet.deposit', $business), businessWalletDepositBody($fixture, method: $other))
        ->assertStatus(409)->assertJsonPath('code', 'DEPOSIT_METHOD_UNVERIFIED');
    $this->actingAs($fixture['depositor'])->postJson(route('business.wallet.deposit', $business), businessWalletDepositBody($fixture, '500'))
        ->assertUnprocessable()->assertJsonPath('code', 'VALIDATION_FAILED');
    expect(BusinessDepositIntent::query()->count())->toBe(0);
});

it('serves the API with its abilities: no command without business:command', function (): void {
    $fixture = businessWalletFixture();
    $business = $fixture['business']->id;
    Sanctum::actingAs($fixture['depositor'], ['business:read']);

    $this->getJson(route('api.v1.business.wallet.show', $business))->assertOk()->assertJsonPath('data.allowed_actions', [])
        ->assertJsonPath('data.actions.deposit.url', '/api/v1/business/'.$business.'/wallet/deposits');
    $this->postJson(route('api.v1.business.wallet.deposit', $business), businessWalletDepositBody($fixture))->assertForbidden();
    Sanctum::actingAs($fixture['depositor'], ['business:read', 'business:command']);
    $this->postJson(route('api.v1.business.wallet.deposit', $business), businessWalletDepositBody($fixture))->assertOk()
        ->assertJsonPath('code', 'DEPOSIT_INTENT_RECORDED');
});

it('withholds the deposit action from a mandate holder without the permission while still pricing the quote', function (array $query, ?array $quote): void {
    $fixture = businessWalletFixture();
    $props = businessWalletPage($this->actingAs($fixture['viewer'])->get(route('business.wallet.show', [$fixture['business']->id, ...$query])));

    expect($props['allowed_actions'])->toBe([])->and($props['funding']['quote'])->toEqual($quote);
})->with([
    'priced' => [['kind' => 'deposit', 'amount' => '005000'], ['amount' => ['currency' => 'RWF', 'amount' => '5000'], 'fee' => ['currency' => 'RWF', 'amount' => '0'],
        'credited' => ['currency' => 'RWF', 'amount' => '5000'], 'refusal' => null]],
    'below minimum' => [['kind' => 'deposit', 'amount' => '100'], ['amount' => ['currency' => 'RWF', 'amount' => '100'], 'fee' => ['currency' => 'RWF', 'amount' => '0'],
        'credited' => ['currency' => 'RWF', 'amount' => '100'], 'refusal' => 'VALIDATION_FAILED']],
    'zero' => [['kind' => 'deposit', 'amount' => '0'], ['amount' => ['currency' => 'RWF', 'amount' => '0'], 'fee' => ['currency' => 'RWF', 'amount' => '0'],
        'credited' => ['currency' => 'RWF', 'amount' => '0'], 'refusal' => 'VALIDATION_FAILED']],
    'empty' => [['kind' => 'deposit', 'amount' => ''], null],
]);

it('refuses the quote without a verified method of this Business', function (): void {
    $fixture = businessWalletFixture();
    BusinessFundingMethod::query()->whereKey($fixture['method']->id)->update(['revoked_at' => now()]);
    $props = businessWalletPage($this->actingAs($fixture['depositor'])->get(route('business.wallet.show', [$fixture['business']->id, 'kind' => 'deposit', 'amount' => '5000'])));

    expect($props['allowed_actions'])->toBe([])->and($props['funding']['quote']['refusal'])->toBe('DEPOSIT_METHOD_UNVERIFIED');
});

/**
 * Records a verified success and its sealed credit for a recorded intent, as the settlement adapter will.
 */
function businessWalletCredit(BusinessDepositIntent $intent): BusinessDepositCredit
{
    $event = new BusinessProviderEvent;
    $event->forceFill(['provider' => $intent->provider, 'provider_event_id' => 'synthetic-event-'.Str::lower(Str::random(12)), 'intent_id' => $intent->id,
        'content_sha256' => hash('sha256', Str::random(16)), 'state' => 'succeeded', 'amount' => $intent->amount, 'currency' => 'RWF', 'environment' => 'testing',
        'observed_at' => now(), 'disposition' => 'applied', 'evidence' => ['source' => 'test'], 'created_at' => now()])->save();
    $entry = LedgerEntry::factory()->create(['wallet_id' => $intent->wallet_id, 'kind' => 'business_deposit_credit', 'source_type' => 'business_deposit_intent',
        'source_id' => $intent->id, 'origin_operation_id' => $intent->operation_id]);
    $clearing = LedgerAccount::factory()->system()->create();
    $available = LedgerAccount::factory()->create(['wallet_id' => $intent->wallet_id, 'kind' => 'business_available']);
    LedgerLine::factory()->create(['entry_id' => $entry->id, 'account_id' => $clearing->id, 'direction' => 'debit', 'amount' => $intent->amount]);
    LedgerLine::factory()->create(['entry_id' => $entry->id, 'account_id' => $available->id, 'direction' => 'credit', 'amount' => $intent->credited]);
    $credit = new BusinessDepositCredit;
    $credit->id = strtolower((string) Str::ulid());
    $payload = ['receipt_id' => $credit->id, 'intent_id' => $intent->id, 'wallet_id' => $intent->wallet_id, 'ledger_entry_id' => $entry->id,
        'provider_event_id' => $event->id, 'operation_id' => $intent->operation_id, 'request_id' => $intent->request_id, 'amount' => $intent->credited,
        'policy_version' => 'synthetic', 'recorded_at' => now('UTC')->startOfSecond()->toIso8601String()];
    $credit->forceFill(['intent_id' => $intent->id, 'wallet_id' => $intent->wallet_id, 'ledger_entry_id' => $entry->id, 'provider_event_id' => $event->id,
        'operation_id' => $intent->operation_id, 'request_id' => $intent->request_id, 'amount' => $intent->credited, 'payload' => $payload,
        'sha256' => hash('sha256', app(CanonicalJson::class)->encode($payload)), 'created_at' => now()])->save();
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');

    return $credit;
}

it('reads a credited deposit as available balance, external history and an openable credit receipt', function (): void {
    $fixture = businessWalletFixture();
    $business = $fixture['business']->id;
    $this->actingAs($fixture['depositor'])->postJson(route('business.wallet.deposit', $business), businessWalletDepositBody($fixture))->assertOk();
    $credit = businessWalletCredit(BusinessDepositIntent::query()->sole());
    $props = businessWalletPage($this->actingAs($fixture['depositor'])->get(route('business.wallet.show', $business)));
    $opened = businessWalletPage($this->actingAs($fixture['depositor'])->get(route('business.wallet.show', [$business, 'receipt' => $credit->id])));

    expect($props['wallet']['available'])->toEqual(['currency' => 'RWF', 'amount' => '50000'])
        ->and($props['history']['items'][0])->toMatchArray(['id' => $credit->id, 'movement' => 'external', 'kind' => 'deposit', 'direction' => 'in',
            'counterparty' => 'MTN MoMo +250 788 ···· 456'])
        ->and($props['deposits'][0]['credit_receipt']['code'])->toBe('DEPOSIT_CREDITED')
        ->and($opened['receipt']['receipt']['receipt_id'])->toBe($credit->id)->and($opened['receipt']['kind'])->toBe('deposit')
        ->and(businessWalletPage($this->actingAs($fixture['depositor'])->get(route('business.wallet.show', [$business, 'receipt' => strtolower((string) Str::ulid())])))['receipt'])
        ->toBeNull();
});

it('refuses to read a deposit intent or credit whose sealed facts no longer match', function (string $table, string $message): void {
    $fixture = businessWalletFixture();
    $business = $fixture['business']->id;
    $this->actingAs($fixture['depositor'])->postJson(route('business.wallet.deposit', $business), businessWalletDepositBody($fixture))->assertOk();
    businessWalletCredit(BusinessDepositIntent::query()->sole());
    DB::statement('ALTER TABLE '.$table.' DISABLE TRIGGER USER');
    DB::table($table)->update(['sha256' => str_repeat('0', 64)]);
    DB::statement('ALTER TABLE '.$table.' ENABLE TRIGGER USER');

    $this->withoutExceptionHandling();
    expect(fn () => $this->actingAs($fixture['depositor'])->get(route('business.wallet.show', $business)))->toThrow(RuntimeException::class, $message);
})->with([
    'intent' => ['business_deposit_intents', 'WALLET_DEPOSIT_INTEGRITY_FAILED'],
    'credit' => ['business_deposit_credits', 'WALLET_CREDIT_INTEGRITY_FAILED'],
]);
