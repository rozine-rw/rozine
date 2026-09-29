<?php

declare(strict_types=1);

/*
 * Review probes for PR #175 range c87b6e08..2fedd5a5 (P3-a/c/d follow-ups). Copy into tests/Feature/.
 * PROBE = should already hold; OBSERVE = pins behaviour for the report.
 */

use App\Application\Business\Contracts\BusinessCampaignStore;
use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Application\Primary\Contracts\PrimaryReservations;
use App\Application\Wallet\Contracts\WalletPostings;
use App\Application\Wallet\PostingSource;
use App\Domain\Primary\PrimaryTerms;
use App\Domain\Primary\UnitRights;
use App\Domain\Wallet\WalletMoney;
use App\Domain\Wallet\WalletViolation;
use App\Models\CommandOperation;
use App\Models\LedgerEntry;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use Illuminate\Database\QueryException;
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
    $this->source = new PostingSource('primary_reservation', $this->root->id, $this->root->origin_operation_id);
    $this->wallets = app(WalletPostings::class);
    $this->release = fn (?string $key = null, int $revision = 1): array => $this->checkout->release($this->investor['user']->id, 1,
        $this->campaign->id, $this->root->id, $revision, $key ?? (string) Str::uuid());
    $this->confirm = fn (?string $key = null, int $revision = 1, ?Closure $admit = null): array => $this->checkout->confirm($this->investor['user']->id, 1,
        $this->campaign->id, $this->root->id, $revision, $this->version->payload['terms']['disclosure_version'], $this->version->payload['disclosure_sha256'],
        $key ?? (string) Str::uuid(), $admit ?? PrimaryReservationFixture::terms(...));
    $this->state = fn (): array => [PrimaryReservationVersion::query()->count(), LedgerEntry::query()->whereIn('kind', ['primary_release', 'primary_commit'])->count(),
        CommandOperation::query()->whereIn('command', ['primary.release', 'primary.confirm'])->count()];
});

it('PROBE the adoption guard still fires from a requoted held revision (revision 2)', function (): void {
    $requoted = ($this->confirm)(admit: function (UnitRights $rights, array $campaign): PrimaryTerms {
        $terms = PrimaryReservationFixture::terms($rights, $campaign);

        return PrimaryTerms::disclosed($terms->ratePercent, $terms->termMonths, $terms->policyVersion, 'synthetic-disclosure-2',
            ['tier' => 'standard', 'rate_bps' => 1000, 'basis' => 'return_only', 'policy_version' => 'synthetic-primary-fees-1'], $terms->payoutFee ?? '0', $rights);
    });
    expect($requoted['code'])->toBe('RESERVATION_REQUOTED');
    expect(fn () => DB::transaction(function (): void {
        $this->wallets->release($this->wallets->lockForParty($this->root->party_id), WalletMoney::of($this->root->principal), $this->source);
        ($this->release)(revision: 2);
    }))->toThrow(RuntimeException::class, 'RESERVATION_INTEGRITY_FAILED')
        ->and(PrimaryReservationVersion::query()->orderByDesc('revision')->value('state'))->toBe('held')
        ->and(($this->state)())->toBe([2, 0, 1]);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
});

it('PROBE a foreign release posting with a different amount is refused, not adopted, on release and expiry', function (string $path): void {
    if ($path !== 'release') {
        $this->travelTo($this->root->expires_at);
    }
    expect(fn () => DB::transaction(function () use ($path): void {
        // A mismatched earlier posting for this source.
        $this->wallets->release($this->wallets->lockForParty($this->root->party_id), WalletMoney::of('5000'), $this->source);
        match ($path) {
            'release' => ($this->release)(),
            'expiry' => app(PrimaryReservations::class)->expire($this->campaign->id, $this->root->id),
            'confirm' => ($this->confirm)(),
        };
    }))->toThrow(WalletViolation::class)
        ->and(($this->state)())->toBe([1, 0, 0]);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
})->with(['release', 'expiry', 'confirm']);

it('PROBE a foreign commit posting on a held source blocks every release and expiry path', function (string $path): void {
    if ($path !== 'release') {
        $this->travelTo($this->root->expires_at);
    }
    expect(fn () => DB::transaction(function () use ($path): void {
        $this->wallets->commit($this->wallets->lockForParty($this->root->party_id), WalletMoney::of($this->root->principal), $this->source);
        match ($path) {
            'release' => ($this->release)(),
            'expiry' => app(PrimaryReservations::class)->expire($this->campaign->id, $this->root->id),
            'confirm' => ($this->confirm)(),
        };
    }))->toThrow(WalletViolation::class, 'WALLET_POSTING_STATE_INVALID')
        ->and(($this->state)())->toBe([1, 0, 0]);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
})->with(['release', 'expiry', 'confirm']);

it('PROBE an orphan release posting on a held reservation cannot commit, so the guarded state is unreachable at rest', function (): void {
    $this->wallets->release($this->wallets->lockForParty($this->root->party_id), WalletMoney::of($this->root->principal), $this->source);
    expect(fn () => DB::statement('SET CONSTRAINTS ALL IMMEDIATE'))->toThrow(QueryException::class, 'must agree');
});

it('PROBE P3-b terminal replay is untouched by the guard: a fresh key on a released or expired hold replays without new cash', function (bool $expired): void {
    if ($expired) {
        $this->travelTo($this->root->expires_at);
        expect(DB::transaction(fn () => app(PrimaryReservations::class)->expire($this->campaign->id, $this->root->id))?->posting->replayed)->toBeFalse();
        $second = ($this->release)(revision: 2);
        expect($second)->toMatchArray(['status' => 'rejected', 'code' => 'RESERVATION_EXPIRED', 'revision' => 2]);
    } else {
        $first = ($this->release)();
        $second = ($this->release)(revision: 2);
        expect($second)->toMatchArray(['status' => 'completed', 'code' => 'RESERVATION_RELEASED', 'revision' => 2])
            ->and($second['data']['entry_id'])->toBe($first['data']['entry_id']);
    }
    expect(DB::transaction(fn () => app(PrimaryReservations::class)->expire($this->campaign->id, $this->root->id)))->toBeNull()
        ->and(LedgerEntry::query()->where('kind', 'primary_release')->count())->toBe(1);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
})->with([false, true]);

it('PROBE P3-c: confirm expiry receipts at entry and across the deadline equal the release expiry receipt shape', function (string $when): void {
    $admit = $when === 'entry' ? null : function (UnitRights $rights, array $campaign): PrimaryTerms {
        $this->travelTo($this->root->expires_at->addSeconds(5));

        return PrimaryReservationFixture::terms($rights, $campaign);
    };
    if ($when === 'entry') {
        $this->travelTo($this->root->expires_at);
    }
    $key = (string) Str::uuid();
    $confirm = ($this->confirm)($key, 1, $admit);
    $release = ($this->release)(null, 2);
    $expired = PrimaryReservationVersion::query()->orderByDesc('revision')->firstOrFail();
    expect($confirm)->toMatchArray(['status' => 'rejected', 'code' => 'RESERVATION_EXPIRED', 'revision' => 2,
        'data' => ['reservation_id' => $this->root->id, 'amount' => '15000']])
        ->and($expired->revision)->toBe($confirm['revision'])->and($expired->operation_id)->toBe($confirm['operation_id'])
        ->and(array_keys($release['data']))->toEqualCanonicalizing(array_keys($confirm['data']))
        ->and($release['revision'])->toBe(2)
        ->and(($this->confirm)($key, 1, $admit))->toBe($confirm)
        ->and(LedgerEntry::query()->where('kind', 'primary_release')->count())->toBe(1);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
})->with(['entry', 'admission']);

it('PROBE P3-d: confirm on a publication-expired campaign also refuses before admission', function (): void {
    $this->travelTo($this->campaign->expires_at);
    $calls = 0;
    $result = ($this->confirm)(admit: function (UnitRights $rights, array $campaign) use (&$calls): PrimaryTerms {
        $calls++;

        return PrimaryReservationFixture::terms($rights, $campaign);
    });
    expect($result['code'])->toBe('RESERVATION_EXPIRED')->and($calls)->toBe(0);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
});

it('PROBE P3-d: a cancelled campaign refuses confirm before admission and still returns the hold on release', function (): void {
    expect(app(BusinessCampaignStore::class)->cancel($this->campaign->actor_user_id, 1, $this->campaign->business_id,
        $this->campaign->id, 1, null, (string) Str::uuid())['code'])->toBe('CAMPAIGN_CANCELLED');
    $calls = 0;
    $result = ($this->confirm)(admit: function (UnitRights $rights, array $campaign) use (&$calls): PrimaryTerms {
        $calls++;

        return PrimaryReservationFixture::terms($rights, $campaign);
    });
    expect($result['code'])->toBe('CAMPAIGN_CLOSED')->and($calls)->toBe(0)
        ->and(($this->release)()['code'])->toBe('RESERVATION_RELEASED');
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
});
