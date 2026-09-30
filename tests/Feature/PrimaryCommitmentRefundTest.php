<?php

declare(strict_types=1);

use App\Application\Business\Contracts\BusinessCampaignStore;
use App\Application\Business\Contracts\BusinessExposureStore;
use App\Application\Identity\SelectActiveRole;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Application\Primary\Contracts\PrimaryFunding;
use App\Application\Primary\Contracts\PrimaryReservations;
use App\Application\Wallet\Contracts\PrimaryReturnedCash;
use App\Application\Wallet\GetInvestorWallet;
use App\Application\Wallet\LockedWallet;
use App\Application\Wallet\PostingSource;
use App\Application\Wallet\ReturnedCash;
use App\Domain\Identity\IdentityViolation;
use App\Domain\Operations\CommandRejection;
use App\Domain\Wallet\WalletMoney;
use App\Domain\Wallet\WalletViolation;
use App\Models\BusinessCampaign;
use App\Models\CommandOperation;
use App\Models\LedgerEntry;
use App\Models\PrimaryCommitment;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use App\Models\RoleMembership;
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
    $held = PrimaryReservationVersion::query()->sole();
    $this->confirmKey = (string) Str::uuid();
    $this->confirmation = $this->checkout->confirm($this->investor['user']->id, 1, $this->campaign->id, $this->root->id, 1,
        $held->payload['terms']['disclosure_version'], $held->payload['disclosure_sha256'], $this->confirmKey, PrimaryReservationFixture::terms(...));
    $this->version = PrimaryReservationVersion::query()->orderByDesc('revision')->firstOrFail();
    $this->commitment = PrimaryCommitment::query()->sole();
    $this->refund = fn (?string $key = null, int $revision = 2): array => $this->checkout->refund($this->investor['user']->id, 1,
        $this->campaign->id, $this->root->id, $revision, $key ?? (string) Str::uuid());
});

it('returns exactly the original confirmed principal with no fee or rewritten purchase history', function (): void {
    $before = [PrimaryReservationRecord::query()->get()->toJson(), PrimaryReservationVersion::query()->orderBy('revision')->get()->toJson(),
        PrimaryCommitment::query()->get()->toJson(), app(BusinessExposureStore::class)->current($this->campaign->business_id)];
    $result = ($this->refund)();
    $entry = LedgerEntry::query()->where('kind', 'primary_refund')->sole();
    expect($result)->toMatchArray(['status' => 'completed', 'code' => 'COMMITMENT_REFUNDED', 'revision' => 2])
        ->and($result['data'])->toMatchArray(['reservation_id' => $this->root->id, 'commitment_id' => $this->commitment->id,
            'entry_id' => $entry->id, 'origin_operation_id' => $this->root->origin_operation_id,
            'amount' => '15000', 'currency' => 'RWF', 'fee' => '0'])
        ->and($entry->source_id)->toBe($this->root->id)->and($entry->origin_operation_id)->toBe($this->root->origin_operation_id)
        ->and([PrimaryReservationRecord::query()->get()->toJson(), PrimaryReservationVersion::query()->orderBy('revision')->get()->toJson(),
            PrimaryCommitment::query()->get()->toJson(), app(BusinessExposureStore::class)->current($this->campaign->business_id)])->toBe($before)
        ->and(app(GetInvestorWallet::class)->handle($this->investor['user']->id, 1)['wallet']['breakdown'])
        ->toMatchArray(['available' => ['currency' => 'RWF', 'amount' => '10000000'], 'committed' => ['currency' => 'RWF', 'amount' => '0']]);
    $progress = app(BusinessCampaignStore::class)->campaign($this->campaign->actor_user_id, 1, $this->campaign->business_id, $this->campaign->id)['progress'];
    expect($progress['committed']['amount'])->toBe('0')->and($progress['units']['unavailable'])->toBe('3');
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
});

it('replays or finds a retained refund after expiry and fresh keys never refund cash twice', function (): void {
    $key = (string) Str::uuid();
    $result = ($this->refund)($key);
    $this->travelTo($this->campaign->expires_at->addDay());
    expect(($this->refund)($key))->toBe($result)
        ->and($this->checkout->findRefund($this->investor['user']->id, 1, $this->campaign->id, $this->root->id, $key))->toBe($result)
        ->and(($this->refund)()['data'])->toBe($result['data'])
        ->and(fn () => ($this->refund)($key, 3))->toThrow(CommandRejection::class, 'IDEMPOTENCY_CONFLICT')
        ->and($this->checkout->findConfirmation($this->investor['user']->id, 1, $this->campaign->id, $this->root->id, $this->confirmKey))->toBe($this->confirmation)
        ->and(LedgerEntry::query()->where('kind', 'primary_refund')->count())->toBe(1)
        ->and(CommandOperation::query()->where('command', 'primary.refund')->count())->toBe(2);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
});

it('permits the first unfunded refund after the old hold or publication deadline', function (bool $publicationExpired): void {
    $this->travelTo($publicationExpired ? $this->campaign->expires_at->addDay() : $this->root->expires_at);
    expect(($this->refund)()['code'])->toBe('COMMITMENT_REFUNDED')
        ->and(LedgerEntry::query()->where('kind', 'primary_refund')->count())->toBe(1);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
})->with([false, true]);

it('requires current Investor authority for new refunds retries and operation lookup', function (): void {
    $key = (string) Str::uuid();
    ($this->refund)($key);
    $this->investor['party']->forceFill(['verified_at' => null])->save();
    foreach ([$key, (string) Str::uuid()] as $request) {
        expect(fn () => ($this->refund)($request))->toThrow(IdentityViolation::class, 'IDENTITY_VERIFICATION_REQUIRED');
    }
    expect(fn () => $this->checkout->findRefund($this->investor['user']->id, 1, $this->campaign->id, $this->root->id, $key))
        ->toThrow(IdentityViolation::class, 'IDENTITY_VERIFICATION_REQUIRED');
});

it('scopes refunds and lookup to the exact canonical Party campaign and purchase', function (): void {
    $key = (string) Str::uuid();
    ($this->refund)($key);
    $other = PrimaryReservationFixture::investor();
    $otherCampaign = BusinessCampaign::factory()->create();
    foreach ([[$other['user']->id, $this->campaign->id, $this->root->id], [$this->investor['user']->id, $otherCampaign->id, $this->root->id],
        [$this->investor['user']->id, $this->campaign->id, strtolower((string) Str::ulid())]] as [$user, $campaign, $root]) {
        expect(fn () => $this->checkout->refund($user, 1, $campaign, $root, 2, $key))->toThrow(CommandRejection::class, 'RESERVATION_NOT_FOUND')
            ->and(fn () => $this->checkout->findRefund($user, 1, $campaign, $root, $key))->toThrow(CommandRejection::class, 'RESERVATION_NOT_FOUND');
    }
    expect(fn () => app(PrimaryReservations::class)->refund($this->campaign->id, $this->root->id, $other['party']->id, 2))
        ->toThrow(CommandRejection::class, 'RESERVATION_NOT_FOUND');
    $root = $this->checkout->reserve($this->investor['user']->id, 1, $this->campaign->id, '1', (string) Str::uuid(), PrimaryReservationFixture::terms(...))['data']['reservation_id'];
    expect(fn () => $this->checkout->findRefund($this->investor['user']->id, 1, $this->campaign->id, $root, $key))
        ->toThrow(CommandRejection::class, 'OPERATION_NOT_FOUND');
});

it('rejects stale confirmation revisions without moving cash', function (): void {
    $result = ($this->refund)(revision: 1);
    expect($result)->toMatchArray(['status' => 'rejected', 'code' => 'VERSION_CONFLICT', 'revision' => 2])
        ->and(LedgerEntry::query()->where('kind', 'primary_refund')->count())->toBe(0);
});

it('refuses held released and expired reservations as commitment refunds', function (string $state): void {
    $result = $this->checkout->reserve($this->investor['user']->id, 1, $this->campaign->id, '1', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    $root = PrimaryReservationRecord::query()->whereKey($result['data']['reservation_id'])->sole();
    if ($state === 'released') {
        $this->checkout->release($this->investor['user']->id, 1, $this->campaign->id, $root->id, 1, (string) Str::uuid());
    } elseif ($state === 'expired') {
        $this->travelTo($root->expires_at);
        app(PrimaryReservations::class)->expire($this->campaign->id, $root->id);
    }
    $version = PrimaryReservationVersion::query()->where('primary_reservation_id', $root->id)->orderByDesc('revision')->firstOrFail();
    expect($this->checkout->refund($this->investor['user']->id, 1, $this->campaign->id, $root->id, $version->revision, (string) Str::uuid())['code'])
        ->toBe('RESERVATION_NOT_CONFIRMED')->and(LedgerEntry::query()->where('kind', 'primary_refund')->count())->toBe(0);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
})->with(['held', 'released', 'expired']);

it('rolls back the refund and receipt when exact returned cash cannot be verified', function (string $failure): void {
    $real = app(PrimaryReturnedCash::class);
    $cash = $this->createMock(PrimaryReturnedCash::class);
    $cash->expects($this->once())->method('requireReturned')->willReturnCallback(
        function (LockedWallet $wallet, WalletMoney $amount, PostingSource $source) use ($real, $failure): ReturnedCash {
            $returned = $real->requireReturned($wallet, $amount, $source);
            if ($failure === 'refused') {
                throw new RuntimeException('RETURN_VERIFICATION_FAILED');
            }

            return new ReturnedCash($returned->holdEntryId, $failure === 'missing_commit' ? null : $returned->commitEntryId,
                $failure === 'wrong_entry' ? strtolower((string) Str::ulid()) : $returned->returnEntryId,
                $failure === 'wrong_kind' ? 'primary_release' : $returned->returnKind,
                $returned->walletId, $returned->reservationId, $returned->originOperationId, $returned->amount, $returned->returnedAt);
        });
    app()->instance(PrimaryReturnedCash::class, $cash);
    expect(fn () => app(PrimaryCheckout::class)->refund($this->investor['user']->id, 1, $this->campaign->id, $this->root->id, 2, (string) Str::uuid()))
        ->toThrow(RuntimeException::class, $failure === 'refused' ? 'RETURN_VERIFICATION_FAILED' : 'RESERVATION_INTEGRITY_FAILED')
        ->and(LedgerEntry::query()->where('kind', 'primary_refund')->count())->toBe(0)
        ->and(CommandOperation::query()->where('command', 'primary.refund')->count())->toBe(0)
        ->and(PrimaryCommitment::query()->count())->toBe(1);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
})->with(['refused', 'wrong_entry', 'wrong_kind', 'missing_commit']);

it('refuses a missing or substituted confirmation commitment before posting cash', function (string $case): void {
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('ALTER TABLE primary_commitments DISABLE TRIGGER USER');
    if ($case === 'missing') {
        PrimaryCommitment::query()->delete();
    } elseif ($case === 'substituted') {
        PrimaryCommitment::query()->update(['operation_id' => $this->root->origin_operation_id]);
    } elseif ($case === 'version') {
        PrimaryCommitment::query()->update(['primary_reservation_version_id' => PrimaryReservationVersion::query()->where('revision', 1)->sole()->id]);
    } else {
        PrimaryCommitment::query()->update(['confirmed_at' => $this->commitment->confirmed_at->subSecond()]);
    }
    expect(fn () => ($this->refund)())->toThrow(RuntimeException::class, 'RESERVATION_INTEGRITY_FAILED')
        ->and(LedgerEntry::query()->where('kind', 'primary_refund')->count())->toBe(0)
        ->and(CommandOperation::query()->where('command', 'primary.refund')->count())->toBe(0);
})->with(['missing', 'substituted', 'version', 'instant']);

it('holds Business campaign root and commitment locks before the wallet gate', function (): void {
    $locks = [];
    DB::listen(function (QueryExecuted $query) use (&$locks): void {
        if (str_contains($query->sql, 'for update')) {
            $locks[] = $query->sql;
        }
    });
    ($this->refund)();
    $order = array_map(fn (string $table): ?int => array_find_key($locks, fn (string $sql): bool => str_contains($sql, 'from "'.$table.'"')),
        ['business_profiles', 'business_campaigns', 'primary_reservations', 'primary_commitments', 'investor_wallets']);
    expect($order)->each->toBeInt();
    $sorted = $order;
    sort($sorted);
    expect($order)->toBe($sorted);
});

it('refuses new refunds after actual full funding and retains the refusal for authorized lookup', function (): void {
    $first = $this->checkout->reserve($this->investor['user']->id, 1, $this->campaign->id, '1077', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    $other = PrimaryReservationFixture::investor();
    $second = $this->checkout->reserve($other['user']->id, 1, $this->campaign->id, '1080', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    foreach ([[$this->investor, $first], [$other, $second]] as [$investor, $purchase]) {
        $root = PrimaryReservationRecord::query()->whereKey($purchase['data']['reservation_id'])->sole();
        $version = PrimaryReservationVersion::query()->where('primary_reservation_id', $root->id)->sole();
        expect($this->checkout->confirm($investor['user']->id, 1, $this->campaign->id, $root->id, 1,
            $version->payload['terms']['disclosure_version'], $version->payload['disclosure_sha256'], (string) Str::uuid(), PrimaryReservationFixture::terms(...))['code'])
            ->toBe('RESERVATION_CONFIRMED');
    }
    app(PrimaryFunding::class)->lock($this->campaign->id, fn (string $id): array => ['campaign_id' => $id, 'publication_sha256' => $this->campaign->sha256,
        ...array_fill_keys(['eligibility', 'policy', 'connections', 'destination'], ['status' => 'passed', 'evidence' => ['synthetic' => 'Isolated refund refusal fixture.']])]);
    $before = LedgerEntry::query()->orderBy('id')->get()->toJson();
    $key = (string) Str::uuid();
    $result = ($this->refund)($key);
    expect($result)->toMatchArray(['status' => 'rejected', 'code' => 'CAMPAIGN_FUNDED', 'revision' => 2])
        ->and($this->checkout->findRefund($this->investor['user']->id, 1, $this->campaign->id, $this->root->id, $key))->toBe($result)
        ->and(LedgerEntry::query()->orderBy('id')->get()->toJson())->toBe($before);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
});

it('rolls back cash if retaining the command outcome fails after the return posting', function (): void {
    $real = app(CanonicalJson::class);
    $json = $this->createMock(CanonicalJson::class);
    $json->method('encode')->willReturnCallback(function (mixed $value) use ($real): string {
        if (is_array($value) && ($value['code'] ?? null) === 'COMMITMENT_REFUNDED') {
            throw new RuntimeException('REFUND_RECEIPT_FAILED');
        }

        return $real->encode($value);
    });
    app()->instance(CanonicalJson::class, $json);
    expect(fn () => app(PrimaryCheckout::class)->refund($this->investor['user']->id, 1, $this->campaign->id, $this->root->id, 2, (string) Str::uuid()))
        ->toThrow(RuntimeException::class, 'REFUND_RECEIPT_FAILED')
        ->and(LedgerEntry::query()->where('kind', 'primary_refund')->count())->toBe(0)
        ->and(CommandOperation::query()->where('command', 'primary.refund')->count())->toBe(0);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
});

it('reverifies retained refund cash before issuing a fresh-key replay receipt', function (): void {
    $key = (string) Str::uuid();
    $result = ($this->refund)($key);
    $other = PrimaryReservationFixture::investor();
    $otherAccount = DB::table('ledger_accounts')->join('investor_wallets', 'investor_wallets.id', '=', 'ledger_accounts.wallet_id')
        ->where('investor_wallets.party_id', $other['party']->id)->where('ledger_accounts.kind', 'investor_available')->value('ledger_accounts.id');
    expect($otherAccount)->toBeString();
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('ALTER TABLE ledger_lines DISABLE TRIGGER USER');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
    expect(DB::table('ledger_lines')->where('entry_id', $result['data']['entry_id'])->where('direction', 'credit')
        ->update(['account_id' => $otherAccount]))->toBe(1);
    $before = [LedgerEntry::query()->orderBy('id')->get()->toJson(), DB::table('ledger_lines')->orderBy('id')->get()->toJson(),
        CommandOperation::query()->where('command', 'primary.refund')->get()->toJson()];
    expect(fn () => ($this->refund)())->toThrow(WalletViolation::class, 'WALLET_POSTING_CONFLICT')
        ->and([LedgerEntry::query()->orderBy('id')->get()->toJson(), DB::table('ledger_lines')->orderBy('id')->get()->toJson(),
            CommandOperation::query()->where('command', 'primary.refund')->get()->toJson()])->toBe($before);
});

it('binds a refund retry to its original identity context while authorized lookup survives a context change', function (): void {
    $key = (string) Str::uuid();
    $result = ($this->refund)($key);
    RoleMembership::factory()->for($this->investor['party'])->active()->create(['role' => 'business']);
    app(SelectActiveRole::class)->handle($this->investor['user']->id, 'business', 1, (string) Str::uuid());
    app(SelectActiveRole::class)->handle($this->investor['user']->id, 'investor', 2, (string) Str::uuid());
    expect(fn () => ($this->refund)($key))->toThrow(IdentityViolation::class, 'ACTIVE_ROLE_REVISION_CONFLICT')
        ->and(fn () => $this->checkout->refund($this->investor['user']->id, 3, $this->campaign->id, $this->root->id, 2, $key))
        ->toThrow(CommandRejection::class, 'IDEMPOTENCY_CONFLICT')
        ->and($this->checkout->findRefund($this->investor['user']->id, 3, $this->campaign->id, $this->root->id, $key))->toBe($result)
        ->and($this->checkout->refund($this->investor['user']->id, 3, $this->campaign->id, $this->root->id, 2, (string) Str::uuid())['data'])->toBe($result['data'])
        ->and(LedgerEntry::query()->where('kind', 'primary_refund')->count())->toBe(1)
        ->and(CommandOperation::query()->where('command', 'primary.refund')->count())->toBe(2);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
});
