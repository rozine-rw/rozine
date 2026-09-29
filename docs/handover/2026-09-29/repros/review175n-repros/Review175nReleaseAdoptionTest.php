<?php

declare(strict_types=1);

/*
 * Review DEFECT repro for PR #175 range c8f30fcb..205a8a08 (S3-C release/expiry). Copy into tests/Feature/.
 * Asserts the SAFE behaviour, so it FAILS on 205a8a08.
 */

use App\Application\Business\Contracts\BusinessCampaignStore;
use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Application\Primary\Contracts\PrimaryReservations;
use App\Application\Wallet\GetInvestorWallet;
use App\Domain\Operations\CommandRejection;
use App\Domain\Primary\PrimaryTerms;
use App\Domain\Primary\UnitRights;
use App\Models\CommandOperation;
use App\Models\LedgerEntry;
use App\Models\PrimaryCommitment;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use Illuminate\Database\Events\TransactionBeginning;
use Illuminate\Database\Events\TransactionCommitted;
use Illuminate\Database\Events\TransactionRolledBack;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

beforeEach(function (): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $this->campaign = PrimaryReservationFixture::campaign();
    $this->investor = PrimaryReservationFixture::investor();
    $this->checkout = app(PrimaryCheckout::class);
    $this->store = app(PrimaryReservations::class);
    $this->checkout->reserve($this->investor['user']->id, 1, $this->campaign->id, '3', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    $this->root = PrimaryReservationRecord::query()->sole();
    $this->version = PrimaryReservationVersion::query()->sole();
    $this->release = fn (?string $key = null, int $revision = 1): array => $this->checkout->release($this->investor['user']->id, 1,
        $this->campaign->id, $this->root->id, $revision, $key ?? (string) Str::uuid());
    $this->confirm = fn (?string $key = null, int $revision = 1, ?Closure $admit = null): array => $this->checkout->confirm($this->investor['user']->id, 1,
        $this->campaign->id, $this->root->id, $revision, $this->version->payload['terms']['disclosure_version'], $this->version->payload['disclosure_sha256'],
        $key ?? (string) Str::uuid(), $admit ?? PrimaryReservationFixture::terms(...));
    $this->cash = fn (): array => [LedgerEntry::query()->where('kind', 'primary_release')->count(), LedgerEntry::query()->where('kind', 'primary_commit')->count(),
        PrimaryReservationVersion::query()->count(), PrimaryCommitment::query()->count(),
        app(GetInvestorWallet::class)->handle($this->investor['user']->id, 1)['wallet']['breakdown']['held']['amount']];
});

it('DEFECT (K2 analogue, P3): a fresh release from held does not adopt a release posting it did not make', function (): void {
    $wallets = app(\App\Application\Wallet\Contracts\WalletPostings::class);
    $earlier = $wallets->release($wallets->lockForParty($this->root->party_id), \App\Domain\Wallet\WalletMoney::of($this->root->principal),
        new \App\Application\Wallet\PostingSource('primary_reservation', $this->root->id, $this->root->origin_operation_id));
    $result = ($this->release)();
    fwrite(STDERR, 'release after a foreign posting: '.$result['code'].' entry '.($result['data']['entry_id'] ?? '-').' foreign '.$earlier->entryId."\n");
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    expect($result['status'] === 'completed' && ($result['data']['entry_id'] ?? null) === $earlier->entryId)->toBeFalse(
        'RESERVATION_RELEASED adopted entry '.$earlier->entryId.' and the transaction satisfies every deferred guard');
});
