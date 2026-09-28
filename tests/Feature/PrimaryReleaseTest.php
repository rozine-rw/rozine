<?php

declare(strict_types=1);

use App\Application\Business\Contracts\BusinessCampaignStore;
use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Application\Primary\Contracts\PrimaryReservations;
use App\Application\Wallet\Contracts\WalletPostings;
use App\Application\Wallet\GetInvestorWallet;
use App\Domain\Identity\IdentityViolation;
use App\Domain\Operations\CommandRejection;
use App\Models\BusinessCampaign;
use App\Models\CommandOperation;
use App\Models\LedgerEntry;
use App\Models\PrimaryCommitment;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

beforeEach(function (): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $this->campaign = PrimaryReservationFixture::campaign();
    $this->investor = PrimaryReservationFixture::investor();
    $this->checkout = app(PrimaryCheckout::class);
    $this->checkout->reserve($this->investor['user']->id, 1, $this->campaign->id, '3', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    $this->root = PrimaryReservationRecord::query()->sole();
    $this->version = PrimaryReservationVersion::query()->sole();
    $this->release = fn (?string $key = null, int $revision = 1): array => $this->checkout->release($this->investor['user']->id, 1,
        $this->campaign->id, $this->root->id, $revision, $key ?? (string) Str::uuid());
});

it('returns exactly the held principal with one immutable release and original source', function (): void {
    $this->travelTo($this->root->expires_at->subMicrosecond());
    $result = ($this->release)();
    $version = PrimaryReservationVersion::query()->orderByDesc('revision')->firstOrFail();
    $entry = LedgerEntry::query()->where('kind', 'primary_release')->sole();
    expect($result)->toMatchArray(['status' => 'completed', 'code' => 'RESERVATION_RELEASED', 'revision' => 2])
        ->and($result['data'])->toMatchArray(['reservation_id' => $this->root->id, 'amount' => '15000', 'entry_id' => $entry->id])
        ->and($entry->source_id)->toBe($this->root->id)->and($entry->origin_operation_id)->toBe($this->root->origin_operation_id)
        ->and($version->operation_id)->toBe($result['operation_id'])
        ->and($version->payload)->toMatchArray(['state' => 'released', 'previous_sha256' => $this->version->sha256, 'terms' => $this->version->payload['terms']])
        ->and($this->root->fresh()->payload)->toBe($this->root->payload)
        ->and(PrimaryCommitment::query()->count())->toBe(0)
        ->and(app(GetInvestorWallet::class)->handle($this->investor['user']->id, 1)['wallet']['breakdown'])
        ->toMatchArray(['held' => ['currency' => 'RWF', 'amount' => '0'], 'available' => ['currency' => 'RWF', 'amount' => '10000000']]);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
});

it('replays and looks up the same release after campaign expiry without another cash movement', function (): void {
    $key = (string) Str::uuid();
    $released = ($this->release)($key);
    $this->travelTo($this->campaign->expires_at->addDay());
    expect(($this->release)($key))->toBe($released)
        ->and($this->checkout->findRelease($this->investor['user']->id, 1, $this->campaign->id, $this->root->id, $key))->toBe($released)
        ->and(fn () => ($this->release)($key, 2))->toThrow(CommandRejection::class, 'IDEMPOTENCY_CONFLICT');
    $fresh = ($this->release)(revision: 2);
    expect($fresh['data'])->toBe($released['data'])->and($fresh['revision'])->toBe(2)
        ->and(PrimaryReservationVersion::query()->count())->toBe(2)->and(LedgerEntry::query()->where('kind', 'primary_release')->count())->toBe(1);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
});

it('records an actor expiry refusal and its cash release atomically at and after the deadline', function (string $when, string $action): void {
    $this->travelTo(match ($when) {
        'deadline' => $this->root->expires_at,
        'late' => $this->root->expires_at->addSecond(),
        'campaign_closed' => $this->campaign->expires_at->addSecond(),
        default => throw new InvalidArgumentException('Unknown expiry boundary.'),
    });
    $key = (string) Str::uuid();
    $perform = fn (): array => $action === 'release' ? ($this->release)($key) : $this->checkout->confirm($this->investor['user']->id, 1,
        $this->campaign->id, $this->root->id, 1, 'unused', 'unused', $key, function (): never {
            throw new RuntimeException('Expired holds cannot reach admission.');
        });
    $result = $perform();
    $version = PrimaryReservationVersion::query()->orderByDesc('revision')->firstOrFail();
    expect($result)->toMatchArray(['status' => 'rejected', 'code' => 'RESERVATION_EXPIRED'])
        ->and($version->state)->toBe('expired')->and($version->operation_id)->toBe($result['operation_id'])
        ->and(LedgerEntry::query()->where('kind', 'primary_release')->count())->toBe(1)
        ->and(PrimaryCommitment::query()->count())->toBe(0)->and($perform())->toBe($result)
        ->and(PrimaryReservationVersion::query()->count())->toBe(2)
        ->and(CommandOperation::query()->where('command', 'primary.'.$action)->count())->toBe(1);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
})->with(['deadline', 'late', 'campaign_closed'])->with(['release', 'confirm']);

it('expires a system hold only when due without manufacturing an actor receipt', function (): void {
    $store = app(PrimaryReservations::class);
    $operations = CommandOperation::query()->count();
    $this->travelTo($this->root->expires_at->subMicrosecond());
    expect($store->expire($this->campaign->id, $this->root->id))->toBeNull();
    $this->travelTo($this->campaign->expires_at->addDay());
    $expired = $store->expire($this->campaign->id, $this->root->id);
    expect($expired?->reservation->state)->toBe('expired')->and($expired?->posting->amount)->toBe('15000')
        ->and(PrimaryReservationVersion::query()->orderByDesc('revision')->firstOrFail()->operation_id)->toBeNull()
        ->and($store->expire($this->campaign->id, $this->root->id))->toBeNull()
        ->and(CommandOperation::query()->count())->toBe($operations);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
});

it('does not release or refund a confirmed commitment even after its old hold deadline', function (): void {
    $payload = $this->version->payload;
    $this->checkout->confirm($this->investor['user']->id, 1, $this->campaign->id, $this->root->id, 1,
        $payload['terms']['disclosure_version'], $payload['disclosure_sha256'], (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    $this->travelTo($this->campaign->expires_at->addDay());
    expect(($this->release)(revision: 2)['code'])->toBe('RESERVATION_NOT_HELD')
        ->and(app(PrimaryReservations::class)->expire($this->campaign->id, $this->root->id))->toBeNull()
        ->and(LedgerEntry::query()->whereIn('kind', ['primary_release', 'primary_refund'])->count())->toBe(0)
        ->and(PrimaryCommitment::query()->count())->toBe(1);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
});

it('requires current canonical Investor authority for a new release replay and lookup', function (): void {
    $key = (string) Str::uuid();
    ($this->release)($key);
    $this->investor['party']->forceFill(['verified_at' => null])->save();
    foreach ([$key, (string) Str::uuid()] as $request) {
        expect(fn () => ($this->release)($request))->toThrow(IdentityViolation::class, 'IDENTITY_VERIFICATION_REQUIRED');
    }
    expect(fn () => $this->checkout->findRelease($this->investor['user']->id, 1, $this->campaign->id, $this->root->id, $key))
        ->toThrow(IdentityViolation::class, 'IDENTITY_VERIFICATION_REQUIRED');
});

it('scopes release and lookup to the exact Party campaign and reservation', function (): void {
    $key = (string) Str::uuid();
    ($this->release)($key);
    $other = PrimaryReservationFixture::investor();
    $otherCampaign = BusinessCampaign::factory()->create();
    foreach ([[$other['user']->id, $this->campaign->id, $this->root->id], [$this->investor['user']->id, $otherCampaign->id, $this->root->id], [$this->investor['user']->id, $this->campaign->id, strtolower((string) Str::ulid())]] as [$user, $campaign, $root]) {
        expect(fn () => $this->checkout->release($user, 1, $campaign, $root, 1, $key))->toThrow(CommandRejection::class, 'RESERVATION_NOT_FOUND')
            ->and(fn () => $this->checkout->findRelease($user, 1, $campaign, $root, $key))->toThrow(CommandRejection::class, 'RESERVATION_NOT_FOUND');
    }
    $reserved = $this->checkout->reserve($this->investor['user']->id, 1, $this->campaign->id, '1', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    expect(fn () => $this->checkout->findRelease($this->investor['user']->id, 1, $this->campaign->id, $reserved['data']['reservation_id'], $key))
        ->toThrow(CommandRejection::class, 'OPERATION_NOT_FOUND');
    expect(fn () => app(PrimaryReservations::class)->release($this->campaign->id, $this->root->id, $other['party']->id, strtolower((string) Str::ulid()), 2))
        ->toThrow(CommandRejection::class, 'RESERVATION_NOT_FOUND');
    expect(fn () => app(PrimaryReservations::class)->expire($this->campaign->id, strtolower((string) Str::ulid())))
        ->toThrow(CommandRejection::class, 'RESERVATION_NOT_FOUND');
});

it('records stale revision refusals without changing live or expired holds', function (bool $expired): void {
    if ($expired) {
        $this->travelTo($this->root->expires_at);
    }
    expect(($this->release)(revision: 0)['code'])->toBe('VERSION_CONFLICT')
        ->and(PrimaryReservationVersion::query()->count())->toBe(1)->and(LedgerEntry::query()->where('kind', 'primary_release')->count())->toBe(0);
})->with([false, true]);

it('rolls back the expiry receipt and evidence if returning cash fails', function (string $action): void {
    $this->travelTo($this->root->expires_at);
    $wallet = $this->createMock(WalletPostings::class);
    $wallet->expects($this->once())->method('lockForParty')->willThrowException(new RuntimeException('Ledger unavailable.'));
    app()->instance(WalletPostings::class, $wallet);
    $checkout = app(PrimaryCheckout::class);
    $perform = fn (): array => $action === 'release'
        ? $checkout->release($this->investor['user']->id, 1, $this->campaign->id, $this->root->id, 1, (string) Str::uuid())
        : $checkout->confirm($this->investor['user']->id, 1, $this->campaign->id, $this->root->id, 1, 'x', 'x', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    expect($perform)->toThrow(RuntimeException::class, 'Ledger unavailable.')
        ->and(CommandOperation::query()->where('command', 'primary.'.$action)->count())->toBe(0)
        ->and(PrimaryReservationVersion::query()->count())->toBe(1)->and(LedgerEntry::query()->where('kind', 'primary_release')->count())->toBe(0);
})->with(['confirm', 'release']);

it('keeps a released allocation reserved until the separate inventory guard migration', function (): void {
    ($this->release)();
    $result = $this->checkout->reserve($this->investor['user']->id, 1, $this->campaign->id, '1', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    expect(PrimaryReservationRecord::query()->whereKey($result['data']['reservation_id'])->sole()->ordinal_ranges)->toBe('{[4,5)}');
});

it('returns retained held cash after an actual campaign cancellation', function (bool $expired): void {
    $cancelled = app(BusinessCampaignStore::class)->cancel($this->campaign->actor_user_id, 1, $this->campaign->business_id,
        $this->campaign->id, 1, 'Cancelled before funding integration.', (string) Str::uuid());
    expect($cancelled['code'])->toBe('CAMPAIGN_CANCELLED');
    if ($expired) {
        $this->travelTo($this->root->expires_at);
    }
    $result = ($this->release)();
    expect($result['code'])->toBe($expired ? 'RESERVATION_EXPIRED' : 'RESERVATION_RELEASED')
        ->and(PrimaryReservationVersion::query()->orderByDesc('revision')->value('state'))->toBe($expired ? 'expired' : 'released')
        ->and(LedgerEntry::query()->where('kind', 'primary_release')->sole()->source_id)->toBe($this->root->id);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
})->with([false, true]);
