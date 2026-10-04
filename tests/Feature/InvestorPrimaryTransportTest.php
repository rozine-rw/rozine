<?php

declare(strict_types=1);

use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Models\BusinessCampaign;
use App\Models\PrimaryCommitment;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

/*
 * The Investor purchase transport (C3 v2 §2c, #96 5968131139): release and cancel run on real
 * data; reserve and confirm are routed but refused POLICY_INPUT_REQUIRED, with no effect, until a
 * current-authority admission source is bound.
 */

beforeEach(function (): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $this->campaign = PrimaryReservationFixture::campaign();
    $this->investor = PrimaryReservationFixture::investor();
    $this->checkout = app(PrimaryCheckout::class);
});

/** @return array{id: string, revision: int, version: PrimaryReservationVersion} */
function heldReservation(User $investor, BusinessCampaign $campaign): array
{
    $held = app(PrimaryCheckout::class)->reserve($investor->id, 1, $campaign->id, '2', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    $id = (string) $held['data']['reservation_id'];
    $version = PrimaryReservationVersion::query()->where('primary_reservation_id', $id)->orderByDesc('revision')->firstOrFail();

    return ['id' => $id, 'revision' => $version->revision, 'version' => $version];
}

it('refuses reserve POLICY_INPUT_REQUIRED and holds nothing until admission is bound', function (): void {
    $request = (string) Str::uuid();
    $body = ['request_id' => $request, 'identity_context_revision' => 1, 'campaign_id' => $this->campaign->id, 'units' => '2',
        'expected_campaign_revision' => 1, 'quote_revision' => 1];

    $this->actingAs($this->investor['user'])
        ->postJson(route('investor.primary.reserve', ['campaign' => $this->campaign->id]), $body)
        ->assertJsonPath('status', 'rejected')->assertJsonPath('code', 'POLICY_INPUT_REQUIRED');

    expect(PrimaryReservationRecord::query()->count())->toBe(0);
    $this->getJson(route('investor.primary.operations.show', ['campaign' => $this->campaign->id, 'request_id' => $request,
        'identity_context_revision' => 1, 'command' => 'primary.reserve']))
        ->assertJsonPath('code', 'POLICY_INPUT_REQUIRED');
});

it('refuses confirm POLICY_INPUT_REQUIRED and keeps the hold as it was', function (): void {
    $held = heldReservation($this->investor['user'], $this->campaign);

    $this->actingAs($this->investor['user'])
        ->postJson(route('investor.primary.confirm', ['reservation' => $held['id']]), ['request_id' => (string) Str::uuid(),
            'identity_context_revision' => 1, 'reservation_id' => $held['id'], 'expected_reservation_revision' => $held['revision'],
            'disclosure_version' => $held['version']->payload['terms']['disclosure_version'],
            'disclosure_sha256' => $held['version']->payload['disclosure_sha256'], 'acknowledged' => true])
        ->assertJsonPath('code', 'POLICY_INPUT_REQUIRED');

    expect(PrimaryCommitment::query()->count())->toBe(0)
        ->and(PrimaryReservationVersion::query()->where('primary_reservation_id', $held['id'])->max('revision'))->toBe($held['revision']);
});

it('releases a hold through the web and finds the same answer by its request', function (): void {
    $held = heldReservation($this->investor['user'], $this->campaign);
    $request = (string) Str::uuid();

    $released = $this->actingAs($this->investor['user'])
        ->postJson(route('investor.primary.release', ['reservation' => $held['id']]), ['request_id' => $request,
            'identity_context_revision' => 1, 'reservation_id' => $held['id'], 'expected_reservation_revision' => $held['revision']])
        ->assertOk()->assertJsonPath('status', 'completed')->assertJsonPath('code', 'RESERVATION_RELEASED')
        ->assertJsonPath('data.receipt.reservation_id', $held['id'])->assertJsonPath('data.next', null)->json();

    $this->getJson(route('investor.primary.operations.show', ['campaign' => $this->campaign->id, 'request_id' => $request,
        'identity_context_revision' => 1, 'command' => 'primary.release', 'reservation' => $held['id']]))
        ->assertOk()->assertJsonPath('operation_id', $released['operation_id'])->assertJsonPath('code', 'RESERVATION_RELEASED');
    $this->getJson(route('investor.primary.operations.show', ['campaign' => $this->campaign->id, 'request_id' => $request,
        'identity_context_revision' => 1, 'command' => 'primary.release']))
        ->assertNotFound()->assertJsonPath('code', 'OPERATION_NOT_FOUND');
});

it('cancels a confirmed commitment through API v1 with the command ability only', function (): void {
    $held = heldReservation($this->investor['user'], $this->campaign);
    $confirmed = $this->checkout->confirm($this->investor['user']->id, 1, $this->campaign->id, $held['id'], $held['revision'],
        $held['version']->payload['terms']['disclosure_version'], $held['version']->payload['disclosure_sha256'], (string) Str::uuid(),
        PrimaryReservationFixture::terms(...));
    $commitment = (string) $confirmed['data']['commitment_id'];
    $body = ['request_id' => (string) Str::uuid(), 'identity_context_revision' => 1, 'commitment_id' => $commitment,
        'expected_commitment_revision' => (int) $confirmed['revision']];

    Sanctum::actingAs($this->investor['user'], ['investor:read']);
    $this->postJson(route('api.v1.investor.primary.cancel', ['commitment' => $commitment]), $body)->assertForbidden();
    Sanctum::actingAs($this->investor['user'], ['investor:read', 'investor:command']);
    $this->postJson(route('api.v1.investor.primary.cancel', ['commitment' => $commitment]), $body)
        ->assertOk()->assertJsonPath('code', 'COMMITMENT_REFUNDED')->assertJsonPath('data.receipt.fee', '0');
    $this->getJson(route('api.v1.investor.primary.operations.show', ['campaign' => $this->campaign->id, 'request_id' => $body['request_id'],
        'identity_context_revision' => 1, 'command' => 'primary.cancel', 'reservation' => $held['id']]))
        ->assertOk()->assertJsonPath('code', 'COMMITMENT_REFUNDED');
});

it('looks up a confirm by its reservation', function (): void {
    $held = heldReservation($this->investor['user'], $this->campaign);
    $request = (string) Str::uuid();
    $this->checkout->confirm($this->investor['user']->id, 1, $this->campaign->id, $held['id'], $held['revision'],
        $held['version']->payload['terms']['disclosure_version'], $held['version']->payload['disclosure_sha256'], $request,
        PrimaryReservationFixture::terms(...));

    $this->actingAs($this->investor['user'])->getJson(route('investor.primary.operations.show', ['campaign' => $this->campaign->id,
        'request_id' => $request, 'identity_context_revision' => 1, 'command' => 'primary.confirm', 'reservation' => $held['id']]))
        ->assertOk()->assertJsonPath('code', 'RESERVATION_CONFIRMED');
});

it('answers another Investor\'s or an unknown reservation and commitment as not found', function (): void {
    $held = heldReservation($this->investor['user'], $this->campaign);
    $other = PrimaryReservationFixture::investor();
    $release = fn (string $id) => ['request_id' => (string) Str::uuid(), 'identity_context_revision' => 1, 'reservation_id' => $id,
        'expected_reservation_revision' => 1];

    $this->actingAs($other['user'])->postJson(route('investor.primary.release', ['reservation' => $held['id']]), $release($held['id']))
        ->assertNotFound()->assertJsonPath('code', 'RESERVATION_NOT_FOUND');
    $unknown = strtolower((string) Str::ulid());
    $this->postJson(route('investor.primary.release', ['reservation' => $unknown]), $release($unknown))
        ->assertNotFound()->assertJsonPath('code', 'RESERVATION_NOT_FOUND');
    $this->postJson(route('investor.primary.cancel', ['commitment' => $unknown]), ['request_id' => (string) Str::uuid(),
        'identity_context_revision' => 1, 'commitment_id' => $unknown, 'expected_commitment_revision' => 1])
        ->assertNotFound()->assertJsonPath('code', 'NOT_FOUND');
});

it('validates each command and never lets the body select another record', function (string $route, array $parameters, array $body, string $field): void {
    $this->actingAs($this->investor['user'])->postJson(route($route, $parameters), [
        'request_id' => (string) Str::uuid(), 'identity_context_revision' => 1, ...$body,
    ])->assertUnprocessable()->assertJsonValidationErrors($field);
})->with([
    'reserve for another campaign' => ['investor.primary.reserve', ['campaign' => '01k6r4m2n6p8q0r2s4t6v8w0x2'],
        ['campaign_id' => '01k6r4m2n6p8q0r2s4t6v8w0x3', 'units' => '1', 'expected_campaign_revision' => 1, 'quote_revision' => 1], 'campaign_id'],
    'reserve zero units' => ['investor.primary.reserve', ['campaign' => '01k6r4m2n6p8q0r2s4t6v8w0x2'],
        ['campaign_id' => '01k6r4m2n6p8q0r2s4t6v8w0x2', 'units' => '0', 'expected_campaign_revision' => 1, 'quote_revision' => 1], 'units'],
    'confirm without acknowledgement' => ['investor.primary.confirm', ['reservation' => '01k6r4m2n6p8q0r2s4t6v8w0x2'],
        ['reservation_id' => '01k6r4m2n6p8q0r2s4t6v8w0x2', 'expected_reservation_revision' => 1, 'disclosure_version' => 'v',
            'disclosure_sha256' => str_repeat('a', 64), 'acknowledged' => false], 'acknowledged'],
    'release another reservation' => ['investor.primary.release', ['reservation' => '01k6r4m2n6p8q0r2s4t6v8w0x2'],
        ['reservation_id' => '01k6r4m2n6p8q0r2s4t6v8w0x3', 'expected_reservation_revision' => 1], 'reservation_id'],
    'cancel without a revision' => ['investor.primary.cancel', ['commitment' => '01k6r4m2n6p8q0r2s4t6v8w0x2'],
        ['commitment_id' => '01k6r4m2n6p8q0r2s4t6v8w0x2'], 'expected_commitment_revision'],
]);

it('looks up only the purchase commands', function (): void {
    $this->actingAs($this->investor['user'])->getJson(route('investor.primary.operations.show', ['campaign' => $this->campaign->id,
        'request_id' => (string) Str::uuid(), 'identity_context_revision' => 1, 'command' => 'wallet.deposit']))
        ->assertUnprocessable()->assertJsonValidationErrors('command');
});
