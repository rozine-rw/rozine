<?php

declare(strict_types=1);

use App\Application\Business\Contracts\BusinessCampaignStore;
use App\Application\Primary\Contracts\CampaignReservationSummary;
use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Application\Primary\Contracts\PrimaryReservations;
use App\Application\Wallet\Contracts\PrimaryReturnedCash;
use App\Application\Wallet\PostingSource;
use App\Domain\Operations\CommandRejection;
use App\Domain\Wallet\WalletMoney;
use App\Domain\Wallet\WalletViolation;
use App\Infrastructure\Primary\RetainedHeldClaimRelease;
use App\Infrastructure\Primary\RetainedPrimaryReservation;
use App\Models\LedgerEntry;
use App\Models\PartyNotice;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

/**
 * BRS v3.0 INV-R03 / LED-R02: an Investor may withdraw a commitment, for a full refund, at any time
 * until the raise reaches 100%. The hold is released at once, the units go back on offer, and from
 * 100% on the commitment is final.
 */
beforeEach(function (): void {
    Notification::fake();
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $this->campaign = PrimaryReservationFixture::campaign();
    $this->checkout = app(PrimaryCheckout::class);
    $this->commit = function (array $investor, string $units): array {
        $held = $this->checkout->reserve($investor['user']->id, 1, $this->campaign->id, $units, (string) Str::uuid(), PrimaryReservationFixture::terms(...));
        $root = PrimaryReservationRecord::query()->whereKey($held['data']['reservation_id'])->sole();
        $version = PrimaryReservationVersion::query()->where('primary_reservation_id', $root->id)->sole()->payload;
        $confirmed = $this->checkout->confirm($investor['user']->id, 1, $this->campaign->id, $root->id, 1, $version['terms']['disclosure_version'],
            $version['disclosure_sha256'], (string) Str::uuid(), PrimaryReservationFixture::terms(...));

        return ['root' => $root, 'commitment' => (string) $confirmed['data']['commitment_id']];
    };
    $this->investor = PrimaryReservationFixture::investor();
    ['root' => $this->root, 'commitment' => $this->commitment] = ($this->commit)($this->investor, '3');
    $this->withdraw = fn (?string $requestId = null, ?string $commitment = null) => $this->actingAs($this->investor['user'])
        ->postJson(route('investor.primary.cancel', ['commitment' => $commitment ?? $this->commitment]), ['request_id' => $requestId ?? (string) Str::uuid(),
            'identity_context_revision' => 1, 'commitment_id' => $commitment ?? $this->commitment, 'expected_commitment_revision' => 2]);
    $this->available = fn (): string => (string) DB::table('ledger_lines')->join('ledger_accounts', 'ledger_accounts.id', 'ledger_lines.account_id')
        ->where('ledger_accounts.kind', 'investor_available')->where('ledger_accounts.wallet_id', LedgerEntry::query()
        ->where('source_id', $this->root->id)->value('wallet_id'))
        ->selectRaw("COALESCE(SUM(CASE WHEN ledger_lines.direction = 'credit' THEN ledger_lines.amount ELSE -ledger_lines.amount END), 0)::bigint AS balance")
        ->value('balance');
});

it('offers the withdrawal on a commitment below 100% and refunds it in full, fee-free, back to Available', function (): void {
    $page = $this->actingAs($this->investor['user'])->get(route('investor.commitments.show', $this->commitment))->assertOk()->viewData('page')['props'];
    expect($page['allowed_actions'])->toBe(['primary.cancel'])
        ->and($page['commitment']['allowed_actions'])->toBe(['primary.cancel'])
        ->and($page['commitment']['actions']['cancel'])->toBe(['url' => '/investor/commitments/'.$this->commitment.'/cancel', 'method' => 'post']);
    $before = ($this->available)();
    $requestId = (string) Str::uuid();

    ($this->withdraw)($requestId)->assertOk()->assertJsonPath('code', 'COMMITMENT_REFUNDED')
        ->assertJsonPath('data.receipt.commitment_id', $this->commitment)->assertJsonPath('data.receipt.amount', '15000')
        ->assertJsonPath('data.receipt.fee', '0')->assertJsonPath('data.next', null);

    expect(($this->available)())->toBe((string) ((int) $before + 15000))
        ->and(PartyNotice::query()->where('kind', 'primary.refunded')->exists())->toBeFalse()
        ->and(DB::table('primary_held_claim_releases')->where('primary_reservation_id', $this->root->id)->sole()->commit_entry_id)
        ->toBe(LedgerEntry::query()->where('source_id', $this->root->id)->where('kind', 'primary_commit')->value('id'));
    $this->get(route('investor.commitments.show', $this->commitment))->assertOk()
        ->assertInertia(fn ($page) => $page->where('commitment.state', 'cancelled')->where('commitment.cancelled_by', 'investor')
            ->where('commitment.refund.request_id', $requestId)->where('allowed_actions', [])->where('commitment.actions.cancel', null));
    $this->getJson(route('investor.primary.operations.show', ['campaign' => $this->campaign->id, 'request_id' => $requestId,
        'identity_context_revision' => 1, 'command' => 'primary.cancel', 'reservation' => $this->root->id]))
        ->assertOk()->assertJsonPath('code', 'COMMITMENT_REFUNDED');
});

it('puts the withdrawn units back on offer for the next Investor', function (): void {
    ($this->withdraw)()->assertOk();
    $summary = app(CampaignReservationSummary::class)->read($this->campaign->id, now('UTC')->toDateTimeImmutable());
    expect([$summary['committed_units'], $summary['occupied_units'], $summary['returned_units']])->toBe(['0', '0', '3']);

    $next = ($this->commit)(PrimaryReservationFixture::investor(), '2');
    expect($next['root']->payload['ordinals'])->toBe([['first' => '1', 'last' => '2']])
        ->and(DB::table('primary_held_claim_generations')->where('previous_reservation_id', $this->root->id)->count())->toBe(2);
});

it('replays a withdrawal by its request id and refuses a second one as already withdrawn', function (): void {
    $requestId = (string) Str::uuid();
    $first = ($this->withdraw)($requestId)->assertOk()->json('operation_id');

    ($this->withdraw)($requestId)->assertOk()->assertJsonPath('operation_id', $first);
    ($this->withdraw)()->assertConflict()->assertJsonPath('code', 'COMMITMENT_WITHDRAWN');
    expect(LedgerEntry::query()->where('kind', 'primary_refund')->count())->toBe(1);
});

it('makes a commitment final once the raise reaches 100%, funded or not', function (): void {
    $units = (int) ((int) $this->campaign->principal / 5000);
    ($this->commit)(PrimaryReservationFixture::investor(), (string) intdiv($units, 2));
    ($this->commit)(PrimaryReservationFixture::investor(), (string) (intdiv($units, 2) - 3));

    $this->actingAs($this->investor['user'])->get(route('investor.commitments.show', $this->commitment))->assertOk()
        ->assertInertia(fn ($page) => $page->where('commitment.state', 'confirmed')->where('allowed_actions', [])->where('commitment.actions.cancel', null));
    ($this->withdraw)()->assertConflict()->assertJsonPath('code', 'COMMITMENT_LOCKED');
    expect(LedgerEntry::query()->where('kind', 'primary_refund')->exists())->toBeFalse();
});

it('refuses a withdrawal once the raise window has closed, and its settlement still returns every commitment once', function (): void {
    $other = PrimaryReservationFixture::investor();
    ($this->commit)($other, '4');
    ($this->withdraw)()->assertOk();
    $this->travelTo($this->campaign->expires_at);

    $this->actingAs($this->investor['user'])->get(route('investor.commitments.show', $this->commitment))->assertOk()
        ->assertInertia(fn ($page) => $page->where('commitment.state', 'cancelled'));
    ($this->withdraw)()->assertConflict()->assertJsonPath('code', 'CAMPAIGN_CLOSED');
    expect(app(BusinessCampaignStore::class)->expireDue(10))->toBe(1)
        ->and(LedgerEntry::query()->where('kind', 'primary_refund')->count())->toBe(2)
        ->and(PartyNotice::query()->where('kind', 'primary.refunded')->pluck('party_id')->all())->toBe([$other['user']->party_id]);
});

it('does not offer a withdrawal once the raise window has elapsed', function (): void {
    $this->travelTo($this->campaign->expires_at);

    $this->actingAs($this->investor['user'])->get(route('investor.commitments.show', $this->commitment))->assertOk()
        ->assertInertia(fn ($page) => $page->where('allowed_actions', [])->where('commitment.actions.cancel', null));
});

it('finds no commitment that is unknown or another Investor\'s, and needs a command token over the API', function (): void {
    $unknown = strtolower((string) Str::ulid());
    ($this->withdraw)(null, $unknown)->assertNotFound()->assertJsonPath('code', 'NOT_FOUND');
    $other = PrimaryReservationFixture::investor();
    $this->actingAs($other['user'])->postJson(route('investor.primary.cancel', ['commitment' => $this->commitment]), ['request_id' => (string) Str::uuid(),
        'identity_context_revision' => 1, 'commitment_id' => $this->commitment, 'expected_commitment_revision' => 2])->assertNotFound();

    $body = ['request_id' => (string) Str::uuid(), 'identity_context_revision' => 1, 'commitment_id' => $this->commitment, 'expected_commitment_revision' => 2];
    Sanctum::actingAs($this->investor['user'], ['investor:read']);
    $this->postJson(route('api.v1.investor.primary.cancel', ['commitment' => $this->commitment]), $body)->assertForbidden();
    Sanctum::actingAs($this->investor['user'], ['investor:read', 'investor:command']);
    $this->postJson(route('api.v1.investor.primary.cancel', ['commitment' => $this->commitment]), $body)->assertOk()
        ->assertJsonPath('code', 'COMMITMENT_REFUNDED');
    expect(LedgerEntry::query()->where('kind', 'primary_refund')->count())->toBe(1);
});

it('refuses a stale revision and another Party\'s reservation', function (): void {
    $this->actingAs($this->investor['user'])->postJson(route('investor.primary.cancel', ['commitment' => $this->commitment]), ['request_id' => (string) Str::uuid(),
        'identity_context_revision' => 1, 'commitment_id' => $this->commitment, 'expected_commitment_revision' => 1])
        ->assertConflict()->assertJsonPath('code', 'VERSION_CONFLICT');
    expect(fn () => DB::transaction(fn () => app(PrimaryReservations::class)->withdraw($this->campaign->id, $this->root->id, 'another-party', 2)))
        ->toThrow(CommandRejection::class, 'RESERVATION_NOT_FOUND');
});

it('retires a withdrawn claim only with its exact hold, commit and full refund', function (): void {
    $source = new PostingSource('primary_reservation', $this->root->id, $this->root->origin_operation_id);
    $cash = app(PrimaryReturnedCash::class);
    expect(fn () => $cash->inspectWithdrawnReturn($this->root->party_id, WalletMoney::of('15000'), $source))
        ->toThrow(WalletViolation::class, 'PRIMARY_RETURNED_CASH_REQUIRED');
    $retained = app(RetainedPrimaryReservation::class)->facts($this->root);
    expect(fn () => app(RetainedHeldClaimRelease::class)->binding($this->root, $retained))->toThrow(WalletViolation::class);

    ($this->withdraw)()->assertOk();
    $evidence = $cash->inspectWithdrawnReturn($this->root->party_id, WalletMoney::of('15000'), $source);
    expect($evidence['commit_entry_id'])->toBe(LedgerEntry::query()->where('source_id', $this->root->id)->where('kind', 'primary_commit')->value('id'))
        ->and(fn () => $cash->inspectWithdrawnReturn($this->root->party_id, WalletMoney::of('15005'), $source))->toThrow(WalletViolation::class, 'WALLET_POSTING_CONFLICT')
        ->and(fn () => $cash->inspectWithdrawnReturn(PrimaryReservationFixture::investor()['user']->party_id, WalletMoney::of('15000'), $source))
        ->toThrow(WalletViolation::class, 'WALLET_POSTING_CONFLICT')
        ->and(fn () => $cash->inspectWithdrawnReturn($this->root->party_id, WalletMoney::of('15000'), new PostingSource('cart', $this->root->id, 'x')))
        ->toThrow(WalletViolation::class, 'WALLET_POSTING_SOURCE_INVALID');
});

it('lets the database refuse a withdrawn retirement whose commit evidence does not match', function (): void {
    ($this->withdraw)()->assertOk();
    $row = (array) DB::table('primary_held_claim_releases')->where('primary_reservation_id', $this->root->id)->sole();
    $other = ($this->commit)(PrimaryReservationFixture::investor(), '1')['root'];
    $hold = LedgerEntry::query()->where('source_id', $other->id)->where('kind', 'primary_hold')->sole();

    expect(fn () => DB::transaction(fn () => DB::table('primary_held_claim_releases')->insert([...$row, 'primary_reservation_id' => $other->id,
        'root_sha256' => $other->sha256, 'version_id' => PrimaryReservationVersion::query()->where('primary_reservation_id', $other->id)->orderByDesc('revision')->value('id'),
        'version_sha256' => PrimaryReservationVersion::query()->where('primary_reservation_id', $other->id)->orderByDesc('revision')->value('sha256'),
        'hold_entry_id' => $hold->id, 'hold_sha256' => $hold->sha256])))->toThrow(QueryException::class, 'Held claim retirement requires full original terminal held cash return');
    expect(fn () => DB::transaction(fn () => DB::table('primary_held_claim_releases')->where('primary_reservation_id', $this->root->id)->delete()))
        ->toThrow(QueryException::class);
});
