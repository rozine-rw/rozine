<?php

declare(strict_types=1);

use App\Application\Business\Contracts\BusinessCampaignStore;
use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Application\Primary\Contracts\PrimaryReservations;
use App\Application\Wallet\Contracts\PrimaryReturnedCash;
use App\Application\Wallet\Contracts\WalletPostings;
use App\Application\Wallet\GetInvestorWallet;
use App\Application\Wallet\LockedWallet;
use App\Application\Wallet\PostingSource;
use App\Application\Wallet\ReturnedCash;
use App\Domain\Identity\IdentityViolation;
use App\Domain\Operations\CommandRejection;
use App\Domain\Primary\PrimaryTerms;
use App\Domain\Primary\UnitRights;
use App\Domain\Wallet\WalletMoney;
use App\Models\BusinessCampaign;
use App\Models\CommandOperation;
use App\Models\LedgerEntry;
use App\Models\PrimaryCommitment;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use Illuminate\Database\Events\QueryExecuted;
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
    expect($result)->toMatchArray(['status' => 'rejected', 'code' => 'RESERVATION_EXPIRED', 'revision' => 2,
        'data' => ['reservation_id' => $this->root->id, 'amount' => $this->root->principal]])
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

it('returns held cash before permitting actual campaign cancellation', function (bool $expired): void {
    $cancelled = app(BusinessCampaignStore::class)->cancel($this->campaign->actor_user_id, 1, $this->campaign->business_id,
        $this->campaign->id, 1, 'Cancelled before funding integration.', (string) Str::uuid());
    expect($cancelled['code'])->toBe('CAMPAIGN_SETTLEMENT_REQUIRED');
    if ($expired) {
        $this->travelTo($this->root->expires_at);
    }
    $result = ($this->release)();
    expect($result['code'])->toBe($expired ? 'RESERVATION_EXPIRED' : 'RESERVATION_RELEASED')
        ->and(PrimaryReservationVersion::query()->orderByDesc('revision')->value('state'))->toBe($expired ? 'expired' : 'released')
        ->and(LedgerEntry::query()->where('kind', 'primary_release')->sole()->source_id)->toBe($this->root->id);
    expect(app(BusinessCampaignStore::class)->cancel($this->campaign->actor_user_id, 1, $this->campaign->business_id,
        $this->campaign->id, 1, null, (string) Str::uuid())['code'])->toBe('CAMPAIGN_CANCELLED');
    expect(($this->release)(revision: 2)['data'])->toBe($result['data'])
        ->and(LedgerEntry::query()->where('kind', 'primary_release')->count())->toBe(1);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
})->with([false, true]);

it('refuses a preexisting cash release from held on every release and expiry path', function (string $path): void {
    if ($path !== 'release') {
        $this->travelTo($this->root->expires_at);
    }
    expect(fn () => DB::transaction(function () use ($path): void {
        $wallets = app(WalletPostings::class);
        $wallets->release($wallets->lockForParty($this->root->party_id), WalletMoney::of($this->root->principal),
            new PostingSource('primary_reservation', $this->root->id, $this->root->origin_operation_id));
        match ($path) {
            'release', 'expired release' => ($this->release)(),
            'system expiry' => app(PrimaryReservations::class)->expire($this->campaign->id, $this->root->id),
            'expired confirm' => $this->checkout->confirm($this->investor['user']->id, 1, $this->campaign->id, $this->root->id, 1,
                'unused', 'unused', (string) Str::uuid(), PrimaryReservationFixture::terms(...)),
            default => throw new InvalidArgumentException('Unknown release path.'),
        };
    }))->toThrow(RuntimeException::class, 'RESERVATION_INTEGRITY_FAILED')
        ->and(PrimaryReservationVersion::query()->count())->toBe(1)
        ->and(LedgerEntry::query()->where('kind', 'primary_release')->count())->toBe(0)
        ->and(CommandOperation::query()->whereIn('command', ['primary.release', 'primary.confirm'])->count())->toBe(0);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
})->with(['release', 'expired release', 'system expiry', 'expired confirm']);

it('retains the same expiry receipt facts when a hold expires during confirmation admission', function (): void {
    $key = (string) Str::uuid();
    /** @param array<string, mixed> $campaign */
    $admit = function (UnitRights $rights, array $campaign): PrimaryTerms {
        $this->travelTo($this->root->expires_at);

        return PrimaryReservationFixture::terms($rights, $campaign);
    };
    $perform = fn (): array => $this->checkout->confirm($this->investor['user']->id, 1, $this->campaign->id, $this->root->id, 1,
        $this->version->payload['terms']['disclosure_version'], $this->version->payload['disclosure_sha256'], $key, $admit);
    $result = $perform();
    expect($result)->toMatchArray(['status' => 'rejected', 'code' => 'RESERVATION_EXPIRED', 'revision' => 2,
        'data' => ['reservation_id' => $this->root->id, 'amount' => $this->root->principal]])
        ->and($perform())->toBe($result)
        ->and(PrimaryReservationVersion::query()->orderByDesc('revision')->firstOrFail()->state)->toBe('expired')
        ->and(LedgerEntry::query()->where('kind', 'primary_release')->count())->toBe(1);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
});

it('refuses confirmation of a returned reservation after cancellation before admission can requote it', function (): void {
    ($this->release)();
    expect(app(BusinessCampaignStore::class)->cancel($this->campaign->actor_user_id, 1, $this->campaign->business_id,
        $this->campaign->id, 1, null, (string) Str::uuid())['code'])->toBe('CAMPAIGN_CANCELLED');
    $result = $this->checkout->confirm($this->investor['user']->id, 1, $this->campaign->id, $this->root->id, 2,
        $this->version->payload['terms']['disclosure_version'], $this->version->payload['disclosure_sha256'], (string) Str::uuid(),
        function (): never {
            throw new RuntimeException('Closed campaign reached admission.');
        });
    expect($result['code'])->toBe('RESERVATION_NOT_HELD')
        ->and(PrimaryReservationVersion::query()->count())->toBe(2)
        ->and(LedgerEntry::query()->where('kind', 'primary_release')->count())->toBe(1)
        ->and(LedgerEntry::query()->where('kind', 'primary_commit')->count())->toBe(0);
});

it('takes the reservation lock before the wallet lock when releasing cash', function (): void {
    $locks = [];
    DB::listen(function (QueryExecuted $query) use (&$locks): void {
        if (str_contains($query->sql, 'for update')) {
            $locks[] = $query->sql;
        }
    });
    expect(($this->release)()['code'])->toBe('RESERVATION_RELEASED');
    $reservation = array_find_key($locks, fn (string $sql): bool => str_contains($sql, 'from "primary_reservations"'));
    $wallet = array_find_key($locks, fn (string $sql): bool => str_contains($sql, 'from "investor_wallets"'));
    expect($reservation)->toBeInt()->and($wallet)->toBeInt()->and($reservation)->toBeLessThan($wallet);
});

it('rolls back cash state and actor receipt when retained return verification fails', function (string $path, string $failure): void {
    if ($path !== 'release') {
        $this->travelTo($this->root->expires_at);
    }
    $real = app(PrimaryReturnedCash::class);
    $cash = $this->createMock(PrimaryReturnedCash::class);
    $cash->expects($this->once())->method('requireReturned')->willReturnCallback(
        function (LockedWallet $wallet, WalletMoney $amount, PostingSource $source) use ($real, $failure): ReturnedCash {
            $returned = $real->requireReturned($wallet, $amount, $source);
            if ($failure === 'refused') {
                throw new RuntimeException('RETURN_VERIFICATION_FAILED');
            }

            return new ReturnedCash($returned->holdEntryId, $returned->commitEntryId,
                $failure === 'wrong_entry' ? strtolower((string) Str::ulid()) : $returned->returnEntryId,
                $failure === 'wrong_kind' ? 'primary_refund' : $returned->returnKind,
                $returned->walletId, $returned->reservationId, $returned->originOperationId, $returned->amount, $returned->returnedAt);
        });
    app()->instance(PrimaryReturnedCash::class, $cash);
    $checkout = app(PrimaryCheckout::class);
    $perform = fn () => match ($path) {
        'release', 'expired release' => $checkout->release($this->investor['user']->id, 1, $this->campaign->id, $this->root->id, 1, (string) Str::uuid()),
        'system expiry' => app(PrimaryReservations::class)->expire($this->campaign->id, $this->root->id),
        'expired confirm' => $checkout->confirm($this->investor['user']->id, 1, $this->campaign->id, $this->root->id, 1,
            'unused', 'unused', (string) Str::uuid(), PrimaryReservationFixture::terms(...)),
        default => throw new InvalidArgumentException('Unknown release path.'),
    };
    expect($perform)->toThrow(RuntimeException::class, $failure === 'refused' ? 'RETURN_VERIFICATION_FAILED' : 'RESERVATION_INTEGRITY_FAILED')
        ->and(PrimaryReservationVersion::query()->count())->toBe(1)
        ->and(LedgerEntry::query()->where('kind', 'primary_release')->count())->toBe(0)
        ->and(CommandOperation::query()->whereIn('command', ['primary.release', 'primary.confirm'])->count())->toBe(0);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
})->with(['release', 'expired release', 'system expiry', 'expired confirm'])->with(['refused', 'wrong_entry', 'wrong_kind']);
