<?php

declare(strict_types=1);

use App\Application\Business\Contracts\BusinessExposureStore;
use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Application\Primary\Contracts\PrimaryFunding;
use App\Application\Primary\Contracts\PrimaryReservations;
use App\Application\Wallet\Contracts\PrimaryReturnedCash;
use App\Application\Wallet\LockedWallet;
use App\Application\Wallet\PostingSource;
use App\Application\Wallet\ReturnedCash;
use App\Domain\Operations\CommandRejection;
use App\Domain\Wallet\WalletMoney;
use App\Domain\Wallet\WalletViolation;
use App\Models\BusinessCampaign;
use App\Models\CommandOperation;
use App\Models\InvestorFundingMethod;
use App\Models\LedgerEntry;
use App\Models\Party;
use App\Models\PrimaryCommitment;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

beforeEach(function (): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $this->campaign = PrimaryReservationFixture::campaign();
    /** @param array{user: User, party: Party, method: InvestorFundingMethod}|null $investor */
    $this->purchase = function (string $units = '3', string $state = 'refunded', ?array $investor = null, ?BusinessCampaign $campaign = null): PrimaryReservationRecord {
        $investor ??= PrimaryReservationFixture::investor();
        $campaign ??= $this->campaign;
        $checkout = app(PrimaryCheckout::class);
        $result = $checkout->reserve($investor['user']->id, 1, $campaign->id, $units, (string) Str::uuid(), PrimaryReservationFixture::terms(...));
        $root = PrimaryReservationRecord::query()->whereKey($result['data']['reservation_id'])->sole();
        $held = PrimaryReservationVersion::query()->where('primary_reservation_id', $root->id)->sole();
        if (in_array($state, ['confirmed', 'refunded'], true)) {
            expect($checkout->confirm($investor['user']->id, 1, $campaign->id, $root->id, 1,
                $held->payload['terms']['disclosure_version'], $held->payload['disclosure_sha256'], (string) Str::uuid(), PrimaryReservationFixture::terms(...))['code'])
                ->toBe('RESERVATION_CONFIRMED');
        }
        if ($state === 'refunded') {
            expect($checkout->refund($investor['user']->id, 1, $campaign->id, $root->id, 2, (string) Str::uuid())['code'])->toBe('COMMITMENT_REFUNDED');
        } elseif ($state === 'released') {
            expect($checkout->release($investor['user']->id, 1, $campaign->id, $root->id, 1, (string) Str::uuid())['code'])->toBe('RESERVATION_RELEASED');
        } elseif ($state === 'expired') {
            $this->travelTo($root->expires_at);
            expect(app(PrimaryReservations::class)->expire($campaign->id, $root->id))->not->toBeNull();
        }

        return $root;
    };
    $this->read = fn () => app(PrimaryReservations::class)->lockReturnedCampaign($this->campaign->id);
});

it('retains all original returns with exact totals and distinct refunded investors without effects', function (): void {
    $investor = PrimaryReservationFixture::investor();
    $roots = [($this->purchase)('3', investor: $investor), ($this->purchase)('1', investor: $investor),
        ($this->purchase)('2'), ($this->purchase)('4', 'released'), ($this->purchase)('5', 'expired')];
    $before = [LedgerEntry::query()->orderBy('id')->get()->toJson(), CommandOperation::query()->count(),
        PrimaryReservationVersion::query()->orderBy('id')->get()->toJson(), app(BusinessExposureStore::class)->current($this->campaign->business_id)];
    $proof = ($this->read)();
    expect($proof->campaignId)->toBe($this->campaign->id)->and($proof->publicationSha256)->toBe($this->campaign->sha256)
        ->and($proof->refundedPrincipal)->toBe('30000')->and($proof->releasedPrincipal)->toBe('45000')->and($proof->refundedInvestors)->toBe(2)
        ->and(array_column($proof->returns, 'reservation_id'))->toBe(collect($roots)->pluck('id')->sort()->values()->all());
    foreach ($proof->returns as $returned) {
        $entry = LedgerEntry::query()->whereKey($returned['cash']->returnEntryId)->sole();
        $version = PrimaryReservationVersion::query()->whereKey($returned['version_id'])->sole();
        expect($returned['cash']->reservationId)->toBe($returned['reservation_id'])->and($entry->source_id)->toBe($returned['reservation_id'])
            ->and($returned['version_sha256'])->toBe($version->sha256)->and($returned['reservation']->terms->toArray())->toBe($version->payload['terms'])
            ->and($returned['commitment_id'] !== null)->toBe($returned['cash']->returnKind === 'primary_refund');
    }
    $this->travelTo($this->campaign->expires_at->addDay());
    expect(($this->read)())->toEqual($proof)
        ->and([LedgerEntry::query()->orderBy('id')->get()->toJson(), CommandOperation::query()->count(),
            PrimaryReservationVersion::query()->orderBy('id')->get()->toJson(), app(BusinessExposureStore::class)->current($this->campaign->business_id)])->toBe($before);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
});

it('proves an empty campaign has zero returns without creating a wallet or cash', function (): void {
    $proof = ($this->read)();
    expect([$proof->releasedPrincipal, $proof->refundedPrincipal, $proof->refundedInvestors, $proof->returns])->toBe(['0', '0', 0, []])
        ->and(LedgerEntry::query()->count())->toBe(0);
});

it('refuses any held root or unreturned commitment even when another purchase has returned', function (string $state): void {
    ($this->purchase)();
    ($this->purchase)(state: $state);
    $before = LedgerEntry::query()->orderBy('id')->get()->toJson();
    expect(fn () => ($this->read)())->toThrow($state === 'held' ? CommandRejection::class : WalletViolation::class,
        $state === 'held' ? 'CAMPAIGN_SETTLEMENT_REQUIRED' : 'PRIMARY_RETURNED_CASH_REQUIRED')
        ->and(LedgerEntry::query()->orderBy('id')->get()->toJson())->toBe($before);
})->with(['held', 'confirmed']);

it('keeps another campaigns refunded purchases out of this proof', function (): void {
    ($this->purchase)('3');
    Cache::forget('fortify.2fa_codes.'.md5((new Google2FA)->getCurrentOtp('JBSWY3DPEHPK3PXP')));
    $other = PrimaryReservationFixture::campaign();
    $foreign = ($this->purchase)('7', campaign: $other);
    $proof = ($this->read)();
    expect($proof->refundedPrincipal)->toBe('15000')->and($proof->returns)->toHaveCount(1)
        ->and(array_column($proof->returns, 'reservation_id'))->not->toContain($foreign->id);
});

it('refuses retained funding before checking any wallet cash', function (): void {
    ($this->purchase)('1080', 'confirmed');
    ($this->purchase)('1080', 'confirmed');
    app(PrimaryFunding::class)->lock($this->campaign->id, fn (string $id): array => ['campaign_id' => $id, 'publication_sha256' => $this->campaign->sha256,
        ...array_fill_keys(['eligibility', 'policy', 'connections', 'destination'], ['status' => 'passed', 'evidence' => ['synthetic' => 'Isolated return-proof refusal fixture.']])]);
    $cash = $this->createMock(PrimaryReturnedCash::class);
    $cash->expects($this->never())->method('requireReturned');
    app()->instance(PrimaryReturnedCash::class, $cash);
    expect(fn () => ($this->read)())->toThrow(CommandRejection::class, 'CAMPAIGN_FUNDED');
});

it('refuses corrupted confirmation or root evidence before reading cash', function (string $case): void {
    $root = ($this->purchase)();
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
    if ($case === 'root') {
        DB::statement('ALTER TABLE primary_reservations DISABLE TRIGGER USER');
        $root->forceFill(['sha256' => str_repeat('0', 64)])->save();
    } else {
        DB::statement('ALTER TABLE primary_commitments DISABLE TRIGGER USER');
        if ($case === 'missing') {
            PrimaryCommitment::query()->delete();
        } else {
            $commitment = PrimaryCommitment::query()->sole();
            $attributes = match ($case) {
                'version' => ['primary_reservation_version_id' => PrimaryReservationVersion::query()->where('revision', 1)->sole()->id],
                'operation' => ['operation_id' => $root->origin_operation_id],
                default => ['confirmed_at' => $commitment->confirmed_at->subSecond()],
            };
            $commitment->forceFill($attributes)->save();
        }
    }
    $cash = $this->createMock(PrimaryReturnedCash::class);
    $cash->expects($this->never())->method('requireReturned');
    app()->instance(PrimaryReturnedCash::class, $cash);
    expect(fn () => ($this->read)())->toThrow(RuntimeException::class, 'RESERVATION_INTEGRITY_FAILED');
})->with(['root', 'missing', 'version', 'operation', 'instant']);

it('rejects a return kind or commitment proof that disagrees with the retained state', function (string $state, string $failure): void {
    ($this->purchase)(state: $state);
    $real = app(PrimaryReturnedCash::class);
    $cash = $this->createMock(PrimaryReturnedCash::class);
    $cash->method('requireReturned')->willReturnCallback(function (LockedWallet $wallet, WalletMoney $amount, PostingSource $source) use ($real, $failure): ReturnedCash {
        $proof = $real->requireReturned($wallet, $amount, $source);

        return new ReturnedCash($proof->holdEntryId, $failure === 'missing_commit' ? null : ($failure === 'extra_commit' ? 'foreign' : $proof->commitEntryId),
            $proof->returnEntryId, $failure === 'kind' ? 'unknown' : $proof->returnKind, $proof->walletId, $proof->reservationId,
            $proof->originOperationId, $proof->amount, $proof->returnedAt);
    });
    app()->instance(PrimaryReturnedCash::class, $cash);
    expect(fn () => ($this->read)())->toThrow(RuntimeException::class, 'RESERVATION_INTEGRITY_FAILED');
})->with([['refunded', 'kind'], ['released', 'kind'], ['refunded', 'missing_commit'], ['released', 'extra_commit']]);

it('locks all roots and commitments before Party-sorted wallets and reads cash after the last wallet gate', function (): void {
    $earlier = PrimaryReservationFixture::investor();
    $later = PrimaryReservationFixture::investor();
    ($this->purchase)(investor: $later);
    ($this->purchase)(investor: $earlier);
    $queries = [];
    DB::listen(function (QueryExecuted $query) use (&$queries): void {
        $queries[] = [$query->sql, $query->bindings];
    });
    ($this->read)();
    $business = array_find_key($queries, fn (array $q): bool => str_contains($q[0], 'from "business_profiles"') && str_contains($q[0], 'for update'));
    $roots = array_find_key($queries, fn (array $q): bool => str_contains($q[0], 'from "primary_reservations"') && str_contains($q[0], 'for update'));
    $commitments = array_find_key($queries, fn (array $q): bool => str_contains($q[0], 'from "primary_commitments"') && str_contains($q[0], 'for update'));
    $wallets = array_filter($queries, fn (array $q): bool => str_contains($q[0], 'from "investor_wallets"') && str_contains($q[0], '"party_id" = ?') && str_contains($q[0], 'for update'));
    $cash = array_find_key($queries, fn (array $q): bool => str_contains($q[0], 'from "ledger_entries"'));
    expect($business)->toBeInt()->and($roots)->toBeInt()->and($commitments)->toBeInt()->and($cash)->toBeInt()
        ->and($business)->toBeLessThan($roots)->and($roots)->toBeLessThan($commitments)->and($commitments)->toBeLessThan(array_key_first($wallets));
    $gates = array_slice($wallets, 0, 2, true);
    expect(array_map(fn (array $q): string => $q[1][0], array_values($gates)))->toBe([$earlier['party']->id, $later['party']->id])
        ->and(array_key_last($gates))->toBeLessThan($cash);
});

it('refuses a commitment attached to a released root before reading cash', function (): void {
    $root = ($this->purchase)(state: 'released');
    $version = PrimaryReservationVersion::query()->where('primary_reservation_id', $root->id)->orderByDesc('revision')->firstOrFail();
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
    DB::statement('ALTER TABLE primary_commitments DISABLE TRIGGER USER');
    PrimaryCommitment::factory()->create(['primary_reservation_id' => $root->id, 'primary_reservation_version_id' => $version->id,
        'operation_id' => $root->origin_operation_id]);
    $cash = $this->createMock(PrimaryReturnedCash::class);
    $cash->expects($this->never())->method('requireReturned');
    app()->instance(PrimaryReturnedCash::class, $cash);
    expect(fn () => ($this->read)())->toThrow(RuntimeException::class, 'RESERVATION_INTEGRITY_FAILED');
});
