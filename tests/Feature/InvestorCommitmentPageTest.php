<?php

declare(strict_types=1);

use App\Application\Operations\Contracts\OperationRecords;
use App\Application\Primary\Contracts\CampaignFundingEvidence;
use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Application\Primary\Contracts\PrimaryCommitmentView;
use App\Domain\Operations\CommandRejection;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

beforeEach(function (): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $this->campaign = PrimaryReservationFixture::campaign();
    $this->investor = PrimaryReservationFixture::investor();
    $checkout = app(PrimaryCheckout::class);
    $checkout->reserve($this->investor['user']->id, 1, $this->campaign->id, '3', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    $this->root = PrimaryReservationRecord::query()->sole();
    $initial = PrimaryReservationVersion::query()->sole()->payload;
    $this->confirmRequest = (string) Str::uuid();
    $this->confirmed = $checkout->confirm($this->investor['user']->id, 1, $this->campaign->id, $this->root->id, 1,
        $initial['terms']['disclosure_version'], $initial['disclosure_sha256'], $this->confirmRequest, PrimaryReservationFixture::terms(...));
    $this->commitment = (string) $this->confirmed['data']['commitment_id'];
    $this->refund = fn (string $requestId): array => $checkout->refund($this->investor['user']->id, 1, $this->campaign->id, $this->root->id, 2, $requestId);
});

it('renders a confirmed commitment from its retained facts and recorded confirmation', function (): void {
    $props = $this->actingAs($this->investor['user'])->get(route('investor.commitments.show', $this->commitment))->assertOk()
        ->assertInertia(fn ($page) => $page->component('investor/commitment'))->viewData('page')['props'];
    $commitment = $props['commitment'];
    $link = ['url' => '/investor/commitments/'.$this->commitment, 'method' => 'get'];

    expect([$props['contract_version'], $props['allowed_actions'], $props['refusal']])->toBe(['investor-primary-v1', [], null])
        ->and($commitment)->toMatchArray(['id' => $this->commitment, 'revision' => 2, 'campaign_id' => $this->campaign->id,
            'deal_name' => $this->campaign->payload['title'], 'units' => '3', 'ordinals' => [['first' => '1', 'last' => '3']],
            'principal' => ['currency' => 'RWF', 'amount' => '15000'], 'rights' => $this->root->payload['rights'], 'state' => 'confirmed',
            'cancelled_by' => null, 'closing' => null, 'refund' => null, 'holding' => null, 'allowed_actions' => [],
            'actions' => ['cancel' => null], 'link' => $link])
        ->and($commitment)->not->toHaveKey('reservation_id')
        ->and($commitment['confirmation'])->toMatchArray(['receipt_id' => $this->commitment, 'operation_id' => $this->confirmed['operation_id'],
            'request_id' => $this->confirmRequest, 'code' => 'RESERVATION_CONFIRMED', 'amount' => ['currency' => 'RWF', 'amount' => '15000'],
            'units' => '3', 'reference' => 'RZC-'.strtoupper(substr($this->commitment, -10)), 'revision' => 2, 'link' => $link])
        ->and($props['links']['operation']['url'])->toContain('/investor/deals/'.$this->campaign->id.'/primary-operations/{request_id}')
        ->toContain('command=primary.cancel')->toContain('reservation='.$this->root->id)
        ->and($props['links']['close'])->toBe(['url' => '/investor/wallet', 'method' => 'get']);

    Sanctum::actingAs($this->investor['user'], ['investor:read']);
    $api = $this->getJson(route('api.v1.investor.commitments.show', ['commitment' => $this->commitment, 'identity_context_revision' => 1]))
        ->assertOk()->json('data');
    expect($api['commitment']['link']['url'])->toBe('/api/v1/investor/commitments/'.$this->commitment)
        ->and($api['commitment']['state'])->toBe('confirmed')->and($api['identity_context_revision'])->toBe(1);
});

it('shows an Investor-cancelled commitment with its refund receipt', function (): void {
    $requestId = (string) Str::uuid();
    $refunded = ($this->refund)($requestId);
    $commitment = $this->actingAs($this->investor['user'])->get(route('investor.commitments.show', $this->commitment))->assertOk()
        ->viewData('page')['props']['commitment'];

    expect([$commitment['state'], $commitment['cancelled_by']])->toBe(['cancelled', 'investor'])
        ->and($commitment['refund'])->toMatchArray(['receipt_id' => $refunded['operation_id'], 'operation_id' => $refunded['operation_id'],
            'request_id' => $requestId, 'code' => 'COMMITMENT_REFUNDED', 'amount' => ['currency' => 'RWF', 'amount' => '15000'],
            'reference' => 'RZF-'.strtoupper(substr($refunded['operation_id'], -10)), 'disclosure_version' => null])
        ->and($commitment['refund']['link']['url'])->toContain('/primary-operations/'.$requestId);
});

it('shows a scoped refusal for an unknown commitment or one of another Investor, and refuses it over the API', function (): void {
    $other = PrimaryReservationFixture::investor();

    $this->actingAs($other['user'])->get(route('investor.commitments.show', $this->commitment))->assertNotFound()
        ->assertInertia(fn ($page) => $page->component('investor/commitment')->where('commitment', null)
            ->where('refusal', ['code' => 'COMMITMENT_NOT_FOUND', 'status' => 404])->where('links.operation', null));
    $this->actingAs($this->investor['user'])->get(route('investor.commitments.show', strtolower((string) Str::ulid())))->assertNotFound()
        ->assertInertia(fn ($page) => $page->where('refusal.code', 'NOT_FOUND'));
    Sanctum::actingAs($other['user'], ['investor:read']);
    $this->getJson(route('api.v1.investor.commitments.show', $this->commitment))->assertNotFound()->assertJsonPath('code', 'COMMITMENT_NOT_FOUND');
    Sanctum::actingAs($this->investor['user'], ['investor:command']);
    $this->getJson(route('api.v1.investor.commitments.show', $this->commitment))->assertForbidden();
});

it('refuses a commitment whose campaign is funded or closed until those sources are wired, unless the Investor refunded it', function (): void {
    app()->instance(CampaignFundingEvidence::class, new class implements CampaignFundingEvidence
    {
        public function find(string $campaignId): array
        {
            return ['id' => 'synthetic-funding'];
        }
    });

    $this->actingAs($this->investor['user'])->get(route('investor.commitments.show', $this->commitment))->assertConflict()
        ->assertInertia(fn ($page) => $page->where('refusal', ['code' => 'COMMITMENT_STATE_UNAVAILABLE', 'status' => 409]));
    ($this->refund)((string) Str::uuid());
    $this->actingAs($this->investor['user'])->get(route('investor.commitments.show', $this->commitment))->assertOk()
        ->assertInertia(fn ($page) => $page->where('commitment.state', 'cancelled'));
});

it('refuses to present a commitment whose recorded evidence does not bind to it', function (Closure $records): void {
    app()->instance(OperationRecords::class, $records(app(OperationRecords::class)));
    ($this->refund)((string) Str::uuid());

    $this->withoutExceptionHandling();
    expect(fn () => $this->actingAs($this->investor['user'])->get(route('investor.commitments.show', $this->commitment)))
        ->toThrow(RuntimeException::class, 'COMMITMENT_INTEGRITY_FAILED');
})->with([
    'no confirmation' => [fn (OperationRecords $real): OperationRecords => new class($real) implements OperationRecords
    {
        public function __construct(private OperationRecords $real) {}

        public function forTarget(string $actorKey, string $command, string $targetType, string $targetId): array
        {
            return $command === 'primary.confirm' ? [] : $this->real->forTarget($actorKey, $command, $targetType, $targetId);
        }
    }],
    'two refunds' => [fn (OperationRecords $real): OperationRecords => new class($real) implements OperationRecords
    {
        public function __construct(private OperationRecords $real) {}

        public function forTarget(string $actorKey, string $command, string $targetType, string $targetId): array
        {
            $records = $this->real->forTarget($actorKey, $command, $targetType, $targetId);

            return $command === 'primary.refund' ? [...$records, ...$records] : $records;
        }
    }],
]);

it('refuses to present facts that name another reservation', function (): void {
    $real = app(PrimaryCheckout::class);
    app()->instance(PrimaryCheckout::class, Mockery::mock(PrimaryCheckout::class, function ($mock) use ($real): void {
        $mock->shouldReceive('commitmentFacts')->andReturnUsing(fn (int $user, int $context, string $campaign, string $commitment): array => [
            ...$real->commitmentFacts($user, $context, $campaign, $commitment), 'reservation_id' => strtolower((string) Str::ulid())]);
    }));

    $this->withoutExceptionHandling();
    expect(fn () => $this->actingAs($this->investor['user'])->get(route('investor.commitments.show', $this->commitment)))
        ->toThrow(RuntimeException::class, 'COMMITMENT_INTEGRITY_FAILED');
});

it('leaves any other refusal to the global handlers', function (): void {
    $this->actingAs(User::factory()->create())->get(route('investor.commitments.show', $this->commitment))
        ->assertForbidden()->assertJsonPath('code', 'IDENTITY_NOT_LINKED');
    app()->instance(PrimaryCommitmentView::class, Mockery::mock(PrimaryCommitmentView::class, function ($mock): void {
        $mock->shouldReceive('show')->andThrow(new CommandRejection('PRIMARY_TRANSACTION_REQUIRED', 503));
    }));
    $this->actingAs($this->investor['user'])->get(route('investor.commitments.show', $this->commitment))->assertStatus(503)
        ->assertInertia(fn ($page) => $page->component('identity/access-denied')->where('code', 'PRIMARY_TRANSACTION_REQUIRED'));
});
