<?php

declare(strict_types=1);

/*
 * Review repros for PR #175 range 2271131e..85b68691 (S3-C confirm/requote). Copy into tests/Feature/.
 * Each `it` asserts the SAFE behaviour, so a failure on 85b68691 confirms the defect. Controls
 * (prefixed "control:") assert behaviour that should already hold and must pass.
 */

use App\Application\Business\Contracts\BusinessCampaignStore;
use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Application\Wallet\Contracts\WalletPostings;
use App\Application\Wallet\GetInvestorWallet;
use App\Application\Wallet\PostingSource;
use App\Domain\Operations\CommandRejection;
use App\Domain\Primary\PrimaryTerms;
use App\Domain\Primary\UnitRights;
use App\Domain\Wallet\WalletMoney;
use App\Models\CommandOperation;
use App\Models\LedgerEntry;
use App\Models\PrimaryCommitment;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use App\Models\User;
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
    $this->breakdown = fn (): array => app(GetInvestorWallet::class)->handle($this->investor['user']->id, 1)['wallet']['breakdown'];
    $this->confirm = fn (?string $request = null, int $revision = 1, ?string $version = null, ?string $hash = null, ?Closure $admit = null): array => $this->checkout->confirm(
        $this->investor['user']->id, 1, $this->campaign->id, $this->root->id, $revision,
        $version ?? $this->version->payload['terms']['disclosure_version'], $version === null && $hash === null ? $this->version->payload['disclosure_sha256'] : ($hash ?? ''),
        $request ?? (string) Str::uuid(), $admit ?? PrimaryReservationFixture::terms(...));
});

/*
 * K1 (DB guard). No database rule binds a primary_commit posting to a confirmed version or commitment.
 * The wallet port itself commits a still-held reservation, and every deferred trigger accepts it.
 */
it('K1: refuses a commit posting for a reservation that has no confirmed version', function (): void {
    $this->wallets->commit($this->wallets->lockForParty($this->root->party_id), WalletMoney::of($this->root->principal), $this->source);
    expect(fn () => DB::statement('SET CONSTRAINTS ALL IMMEDIATE'))->toThrow(QueryException::class);
});

/*
 * K1 consequence. Cash sits in committed with no commitment; after the deadline the hold can never be
 * released (commit and release are exclusive), so the Investor's principal is stranded.
 */
it('K1b: never strands committed cash without a commitment once the hold expires', function (): void {
    $this->wallets->commit($this->wallets->lockForParty($this->root->party_id), WalletMoney::of($this->root->principal), $this->source);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('SET CONSTRAINTS ALL DEFERRED');
    $this->travelTo($this->root->expires_at);
    $stranded = PrimaryCommitment::query()->count() === 0 && ($this->breakdown)()['committed']['amount'] === '15000';
    $releasable = true;
    try {
        DB::transaction(fn () => $this->wallets->release($this->wallets->lockForParty($this->root->party_id), WalletMoney::of($this->root->principal), $this->source));
    } catch (Throwable $e) {
        $releasable = false;
        $why = $e::class.': '.$e->getMessage();
    }
    expect($stranded && ! $releasable)->toBeFalse('committed 15000 with no commitment and no release path: '.($why ?? ''));
});

/*
 * K2. Confirm accepts a pre-existing commit posting (PostingReceipt::$replayed === true) as its own cash
 * movement, so RESERVATION_CONFIRMED is issued although this confirmation moved nothing.
 */
it('K2: does not confirm by replaying a commit posting it did not make', function (): void {
    $prior = $this->wallets->commit($this->wallets->lockForParty($this->root->party_id), WalletMoney::of($this->root->principal), $this->source);
    $result = ($this->confirm)();
    expect($result['code'])->not->toBe('RESERVATION_CONFIRMED', 'confirmed with entry '.($result['data']['entry_id'] ?? '?').' already posted as '.$prior->entryId);
});

/*
 * K3 (DB guard, reverse direction). Raw rows can create a confirmed version and commitment with no commit
 * posting, then release the held cash back to available: a standing commitment with no committed cash.
 */
it('K3: refuses a confirmed commitment whose held principal was released instead of committed', function (): void {
    $operation = CommandOperation::factory()->create(['actor_key' => 'party:'.$this->root->party_id,
        'actor_user_id' => $this->investor['user']->id, 'command' => 'primary.confirm',
        'target_type' => 'primary_reservation', 'target_id' => $this->root->id,
        'result' => ['status' => 'completed', 'code' => 'DISCLOSURE_STALE']]);
    $at = now('UTC');
    $payload = [...$this->version->payload, 'revision' => 2, 'state' => 'confirmed', 'operation_id' => $operation->id, 'previous_sha256' => $this->version->sha256];
    $version = (new PrimaryReservationVersion)->forceFill(['primary_reservation_id' => $this->root->id, 'revision' => 2, 'state' => 'confirmed',
        'operation_id' => $operation->id, 'previous_sha256' => $this->version->sha256, 'payload' => $payload, 'sha256' => str_repeat('a', 64), 'created_at' => $at]);
    $version->save();
    (new PrimaryCommitment)->forceFill(['primary_reservation_id' => $this->root->id, 'primary_reservation_version_id' => $version->id,
        'operation_id' => $operation->id, 'confirmed_at' => $at, 'created_at' => $at])->save();
    $this->wallets->release($this->wallets->lockForParty($this->root->party_id), WalletMoney::of($this->root->principal), $this->source);
    expect(fn () => DB::statement('SET CONSTRAINTS ALL IMMEDIATE'))->toThrow(QueryException::class);
});

/*
 * K4. Cancel is still written for a campaign with no investors. Now that confirm can create commitments,
 * cancelling after a confirmation succeeds, releases the full exposure and reports investors: 0 while the
 * commitment and its committed cash remain.
 */
it('K4: does not cancel a campaign as investor-free after a confirmed commitment', function (): void {
    expect(($this->confirm)()['code'])->toBe('RESERVATION_CONFIRMED');
    $signatory = User::query()->findOrFail($this->campaign->actor_user_id);
    $cancelled = app(BusinessCampaignStore::class)->cancel($signatory->id, 1, $this->campaign->business_id, $this->campaign->id, 1, 'After confirm.', (string) Str::uuid());
    $honest = $cancelled['code'] !== 'CAMPAIGN_CANCELLED' || ($cancelled['data']['receipt']['investors'] ?? null) !== 0;
    expect($honest)->toBeTrue('CAMPAIGN_CANCELLED investors=0 exposure_released='.json_encode($cancelled['data']['receipt']['exposure_released'] ?? null)
        .' with commitments='.PrimaryCommitment::query()->count().' committed='.($this->breakdown)()['committed']['amount']);
});

/* Controls: behaviour the increment claims, which must hold. */
it('control: a requote cannot be confirmed against the superseded disclosure', function (): void {
    $revised = function (UnitRights $rights, array $campaign): PrimaryTerms {
        $terms = PrimaryReservationFixture::terms($rights, $campaign);

        return PrimaryTerms::disclosed($terms->ratePercent, $terms->termMonths, $terms->policyVersion, 'synthetic-disclosure-2', $terms->earningsFee, $terms->payoutFee, $rights);
    };
    $requote = ($this->confirm)(admit: $revised);
    expect($requote['code'])->toBe('RESERVATION_REQUOTED')
        ->and(($this->confirm)(revision: 2, admit: $revised)['code'])->toBe('DISCLOSURE_STALE')
        ->and(($this->confirm)(revision: 2, version: 'synthetic-disclosure-2', hash: $this->version->payload['disclosure_sha256'], admit: $revised)['code'])->toBe('DISCLOSURE_STALE')
        ->and(($this->confirm)(revision: 2, version: 'synthetic-disclosure-1', hash: $requote['data']['disclosure_sha256'], admit: $revised)['code'])->toBe('DISCLOSURE_STALE')
        ->and(($this->confirm)(revision: 1, version: 'synthetic-disclosure-2', hash: $requote['data']['disclosure_sha256'], admit: $revised)['code'])->toBe('VERSION_CONFLICT')
        ->and(PrimaryCommitment::query()->count())->toBe(0)
        ->and(($this->breakdown)()['held']['amount'])->toBe('15000');
});

it('control: admission reverting to the original terms after a requote requires a second requote', function (): void {
    $revised = function (UnitRights $rights, array $campaign): PrimaryTerms {
        $terms = PrimaryReservationFixture::terms($rights, $campaign);

        return PrimaryTerms::disclosed($terms->ratePercent, $terms->termMonths, $terms->policyVersion, 'synthetic-disclosure-2', $terms->earningsFee, $terms->payoutFee, $rights);
    };
    ($this->confirm)(admit: $revised);
    $back = ($this->confirm)(revision: 2);
    expect($back['code'])->toBe('RESERVATION_REQUOTED')->and($back['revision'])->toBe(3)
        ->and(($this->confirm)(revision: 3)['code'])->toBe('RESERVATION_CONFIRMED')
        ->and(($this->breakdown)())->toMatchArray(['held' => ['currency' => 'RWF', 'amount' => '0'], 'committed' => ['currency' => 'RWF', 'amount' => '15000']]);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
});

it('control: every fingerprint field conflicts on a same-key replay', function (string $field): void {
    $key = (string) Str::uuid();
    ($this->confirm)($key);
    $call = match ($field) {
        'revision' => fn () => ($this->confirm)($key, revision: 2),
        'version' => fn () => ($this->confirm)($key, version: 'other', hash: $this->version->payload['disclosure_sha256']),
        'hash' => fn () => ($this->confirm)($key, version: $this->version->payload['terms']['disclosure_version'], hash: str_repeat('0', 64)),
    };
    expect($call)->toThrow(CommandRejection::class, 'IDEMPOTENCY_CONFLICT');
    expect(PrimaryCommitment::query()->count())->toBe(1)->and(LedgerEntry::query()->where('kind', 'primary_commit')->count())->toBe(1);
})->with(['revision', 'version', 'hash']);

it('control: a confirmation key used for one reservation conflicts on another of the same Party', function (): void {
    $key = (string) Str::uuid();
    ($this->confirm)($key);
    $other = $this->checkout->reserve($this->investor['user']->id, 1, $this->campaign->id, '1', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    $otherVersion = PrimaryReservationVersion::query()->where('primary_reservation_id', $other['data']['reservation_id'])->sole();
    expect(fn () => $this->checkout->confirm($this->investor['user']->id, 1, $this->campaign->id, $other['data']['reservation_id'], 1,
        $this->version->payload['terms']['disclosure_version'], $this->version->payload['disclosure_sha256'], $key, PrimaryReservationFixture::terms(...)))
        ->toThrow(CommandRejection::class, 'IDEMPOTENCY_CONFLICT')
        ->and(PrimaryReservationVersion::query()->where('primary_reservation_id', $other['data']['reservation_id'])->count())->toBe(1)
        ->and($otherVersion->state)->toBe('held');
});

it('control: confirm after the campaign is cancelled is a journaled CAMPAIGN_CLOSED', function (): void {
    $signatory = User::query()->findOrFail($this->campaign->actor_user_id);
    expect(app(BusinessCampaignStore::class)->cancel($signatory->id, 1, $this->campaign->business_id, $this->campaign->id, 1, null, (string) Str::uuid())['code'])->toBe('CAMPAIGN_CANCELLED')
        ->and(($this->confirm)())->toMatchArray(['status' => 'rejected', 'code' => 'CAMPAIGN_CLOSED'])
        ->and(PrimaryCommitment::query()->count())->toBe(0)->and(($this->breakdown)()['held']['amount'])->toBe('15000');
});

/*
 * K5 (DB guard, receipt binding). The completed-outcome trigger checks only result.status. A confirmed
 * version, commitment and exact commit posting are accepted when the bound primary.confirm receipt says
 * RESERVATION_REQUOTED (a refresh, not a purchase) and names no commitment.
 */
it('K5: refuses a confirmed version whose bound receipt is not RESERVATION_CONFIRMED', function (): void {
    $operation = CommandOperation::factory()->create(['actor_key' => 'party:'.$this->root->party_id,
        'actor_user_id' => $this->investor['user']->id, 'command' => 'primary.confirm',
        'target_type' => 'primary_reservation', 'target_id' => $this->root->id,
        'result' => ['status' => 'completed', 'code' => 'RESERVATION_REQUOTED', 'data' => ['commitment_id' => null]]]);
    $at = now('UTC');
    $payload = [...$this->version->payload, 'revision' => 2, 'state' => 'confirmed', 'operation_id' => $operation->id, 'previous_sha256' => $this->version->sha256];
    $version = (new PrimaryReservationVersion)->forceFill(['primary_reservation_id' => $this->root->id, 'revision' => 2, 'state' => 'confirmed',
        'operation_id' => $operation->id, 'previous_sha256' => $this->version->sha256, 'payload' => $payload,
        'sha256' => hash('sha256', app(\App\Application\Operations\Contracts\CanonicalJson::class)->encode($payload)), 'created_at' => $at]);
    $version->save();
    (new PrimaryCommitment)->forceFill(['primary_reservation_id' => $this->root->id, 'primary_reservation_version_id' => $version->id,
        'operation_id' => $operation->id, 'confirmed_at' => $at, 'created_at' => $at])->save();
    $this->wallets->commit($this->wallets->lockForParty($this->root->party_id), WalletMoney::of($this->root->principal), $this->source);
    expect(fn () => DB::statement('SET CONSTRAINTS ALL IMMEDIATE'))->toThrow(QueryException::class);
});
