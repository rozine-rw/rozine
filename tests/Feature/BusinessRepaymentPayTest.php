<?php

declare(strict_types=1);

use App\Application\Business\Contracts\NoteServicing;
use App\Application\Business\ServicingQuote;
use App\Application\Wallet\ApplyBusinessProviderOutcome;
use App\Application\Wallet\Contracts\SyntheticEventSigner;
use App\Application\Wallet\RecordBusinessDeposit;
use App\Domain\Wallet\WalletMoney;
use App\Models\BusinessDepositIntent;
use App\Models\BusinessFundingMethod;
use App\Models\BusinessProfile;
use App\Models\BusinessRepayment;
use App\Models\CommandOperation;
use App\Models\DepositPolicy;
use App\Models\LedgerEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Support\BusinessAuthorityFixture;

/** A test-only servicing note: one quote, and every applied repayment recorded. */
final class FakeNoteServicing implements NoteServicing
{
    /** @var list<array{note: string, repayment: string, option: string, amount: string, revision: int}> */
    public array $applied = [];

    public function __construct(public ?ServicingQuote $quote) {}

    public function lockForPayment(string $businessId, string $noteId): ?ServicingQuote
    {
        return $this->quote !== null && $this->quote->noteId === $noteId ? $this->quote : null;
    }

    public function applyRepayment(string $noteId, string $repaymentId, string $option, WalletMoney $amount, int $expectedRevision): int
    {
        $this->applied[] = ['note' => $noteId, 'repayment' => $repaymentId, 'option' => $option, 'amount' => $amount->amount(), 'revision' => $expectedRevision];

        return $expectedRevision + 1;
    }
}

/**
 * A Business whose first mandate holder may pay and whose second may only view, with a funded wallet.
 *
 * @return array{business: BusinessProfile, payer: User, viewer: User, payer_party: string, note: string}
 */
function repaymentPayFixture(string $funded = '200000'): array
{
    $authority = BusinessAuthorityFixture::make('organization', 2, 'COMPANY-'.Str::ulid(), 1);
    $viewer = $authority['people'][1]->id;
    $authority['terms']['people'] = array_map(fn (array $person): array => $person['party_id'] === $viewer ? [...$person, 'permissions' => ['business.view']] : $person,
        $authority['terms']['people']);
    BusinessAuthorityFixture::configure($authority);
    $business = BusinessProfile::query()->where('entity_party_id', $authority['entity'])->firstOrFail();
    DepositPolicy::factory()->create(['fee' => '0', 'minimum' => '1000', 'maximum' => '1000000']);
    $method = BusinessFundingMethod::factory()->create(['business_id' => $business->id]);
    $result = app(RecordBusinessDeposit::class)->handle($authority['users'][0]->id, 1, $business->id, (string) Str::uuid(),
        ['currency' => 'RWF', 'amount' => $funded], $method->id);
    $intent = BusinessDepositIntent::query()->whereKey((string) $result['data']['intent_id'])->sole();
    app(ApplyBusinessProviderOutcome::class)->handle(app(SyntheticEventSigner::class)->sign(['provider' => 'synthetic',
        'event_id' => 'synthetic-event-'.Str::lower(Str::random(12)), 'reference' => $intent->provider_reference, 'state' => 'succeeded',
        'amount' => $funded, 'currency' => 'RWF', 'environment' => 'testing', 'observed_at' => now('UTC')->startOfSecond()->format(DATE_ATOM)]));

    return ['business' => $business, 'payer' => $authority['users'][0], 'viewer' => $authority['users'][1], 'payer_party' => $authority['people'][0]->id,
        'note' => strtolower((string) Str::ulid())];
}

function repaymentPayServicing(string $note, string $dueNow = '112500', int $revision = 3): FakeNoteServicing
{
    $fake = new FakeNoteServicing(new ServicingQuote($note, 'Synthetic equipment purchase', $revision,
        ['due_now' => ['total' => WalletMoney::of($dueNow), 'instalment_indexes' => [2]]]));
    app()->instance(NoteServicing::class, $fake);

    return $fake;
}

/** @return array<string, mixed> */
function repaymentPayBody(string $note, string $total = '112500', int $revision = 3, string $option = 'due_now', ?string $requestId = null): array
{
    return ['request_id' => $requestId ?? (string) Str::uuid(), 'identity_context_revision' => 1, 'note_id' => $note, 'option' => $option,
        'expected_servicing_revision' => $revision, 'quoted_total' => ['currency' => 'RWF', 'amount' => $total]];
}

function repaymentPayAvailable(string $businessId): string
{
    return (string) DB::scalar("SELECT coalesce(sum(CASE WHEN line.direction = 'credit' THEN line.amount ELSE -line.amount END), 0)::text FROM ledger_lines line
        JOIN ledger_accounts account ON account.id = line.account_id JOIN business_wallets wallet ON wallet.id = account.wallet_id
        WHERE wallet.business_id = ? AND account.kind = 'business_available'", [$businessId]);
}

it('refuses every payment while no servicing domain is bound, debiting nothing', function (): void {
    $fixture = repaymentPayFixture();

    $this->actingAs($fixture['payer'])->postJson(route('business.repayments.pay', $fixture['business']->id), repaymentPayBody($fixture['note']))
        ->assertNotFound()->assertJsonPath('code', 'NOTE_NOT_SERVICING');
    expect(BusinessRepayment::query()->count())->toBe(0)->and(repaymentPayAvailable($fixture['business']->id))->toBe('200000');
});

it('pays the quoted option once from the wallet under the acting Party and hands the repayment to servicing', function (): void {
    $fixture = repaymentPayFixture();
    $servicing = repaymentPayServicing($fixture['note']);
    $body = repaymentPayBody($fixture['note']);
    $response = $this->actingAs($fixture['payer'])->postJson(route('business.repayments.pay', $fixture['business']->id), $body)->assertOk();
    $repayment = BusinessRepayment::query()->sole();
    $operation = CommandOperation::query()->where('command', 'repayment.pay')->sole();

    expect($response->json('code'))->toBe('REPAYMENT_RECEIVED')->and($response->json('data.receipt.revision'))->toBe(4)
        ->and($response->json('data.receipt.amount'))->toEqual(['currency' => 'RWF', 'amount' => '112500'])
        ->and([$repayment->note_id, $repayment->option, $repayment->amount, $repayment->servicing_revision, $repayment->party_id])
        ->toBe([$fixture['note'], 'due_now', '112500', 3, $fixture['payer_party']])
        ->and([$operation->actor_key, $operation->target_type])->toBe(['party:'.$fixture['payer_party'], 'business_wallet'])
        ->and(LedgerEntry::query()->where('kind', 'business_repayment_debit')->sole()->source_id)->toBe($repayment->id)
        ->and($servicing->applied)->toBe([['note' => $fixture['note'], 'repayment' => $repayment->id, 'option' => 'due_now', 'amount' => '112500', 'revision' => 3]])
        ->and(repaymentPayAvailable($fixture['business']->id))->toBe('87500');

    $this->actingAs($fixture['payer'])->postJson(route('business.repayments.pay', $fixture['business']->id), $body)->assertOk()
        ->assertJsonPath('data.receipt.receipt_id', $repayment->id);
    expect(BusinessRepayment::query()->count())->toBe(1)->and($servicing->applied)->toHaveCount(1)->and(repaymentPayAvailable($fixture['business']->id))->toBe('87500');
});

it('refuses a stale revision, a changed total, an option not offered and a shortfall, charging nothing', function (array $body, int $status, string $code): void {
    $fixture = repaymentPayFixture('100000');
    $servicing = repaymentPayServicing($fixture['note']);

    $this->actingAs($fixture['payer'])->postJson(route('business.repayments.pay', $fixture['business']->id), [...repaymentPayBody($fixture['note']), ...$body])
        ->assertStatus($status)->assertJsonPath('code', $code);
    expect(BusinessRepayment::query()->count())->toBe(0)->and($servicing->applied)->toBe([])->and(repaymentPayAvailable($fixture['business']->id))->toBe('100000');
})->with([
    'stale revision' => [['expected_servicing_revision' => 2], 409, 'VERSION_CONFLICT'],
    'changed total' => [['quoted_total' => ['currency' => 'RWF', 'amount' => '112499']], 409, 'VERSION_CONFLICT'],
    'not offered' => [['option' => 'next_instalment'], 409, 'NOTHING_DUE'],
    'shortfall' => [[], 422, 'INSUFFICIENT_AVAILABLE_FUNDS'],
]);

it('requires the repayment permission, scopes the lookup to the payer and honours API abilities', function (): void {
    $fixture = repaymentPayFixture();
    repaymentPayServicing($fixture['note']);
    $business = $fixture['business']->id;
    $body = repaymentPayBody($fixture['note']);

    $this->actingAs($fixture['viewer'])->postJson(route('business.repayments.pay', $business), $body)->assertForbidden()->assertJsonPath('code', 'ACTION_FORBIDDEN');
    $this->actingAs($fixture['payer'])->postJson(route('business.repayments.pay', $business), $body)->assertOk();
    $lookup = route('business.repayments.operations.show', ['business' => $business, 'request_id' => $body['request_id'], 'command' => 'repayment.pay',
        'identity_context_revision' => 1]);
    $this->actingAs($fixture['payer'])->getJson($lookup)->assertOk()->assertJsonPath('code', 'REPAYMENT_RECEIVED');
    $this->actingAs($fixture['viewer'])->getJson($lookup)->assertNotFound()->assertJsonPath('code', 'OPERATION_NOT_FOUND');
    Sanctum::actingAs($fixture['payer'], ['business:read']);
    $this->postJson(route('api.v1.business.repayments.pay', $business), repaymentPayBody($fixture['note']))->assertForbidden();
});
