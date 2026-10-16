<?php

declare(strict_types=1);

use App\Application\Business\Contracts\PrimaryCampaignSource;
use App\Application\Primary\Contracts\InvestorHoldings;
use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Application\Primary\Contracts\RetainedNoteSchedules;
use App\Application\Primary\RetainedHoldingSchedule;
use App\Application\Primary\RetainedNoteSchedule;
use App\Models\PrimaryHolding;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryIssuanceFixture;
use Tests\Support\PrimaryReservationFixture;

/*
 * The Investor's Holding and commitment readers fail closed: a Holding the issued schedule does
 * not carry is never shown, and a closure that bound no cash return to a purchase leaves its
 * commitment refused rather than guessed.
 */

it('fails closed when the issued schedule does not carry the Investor\'s Holding', function (): void {
    Carbon::setTestNow(CarbonImmutable::parse('2026-10-10 10:00:00', 'Africa/Kigali'));
    PrimaryIssuanceFixture::close('2026-10-09T22:30:00+00:00', units: ['1000', '80', '1080'], buyers: ['solo', 'solo', 'other']);
    $holding = PrimaryHolding::query()->where('units', 1000)->sole();
    $user = User::query()->where('party_id', $holding->party_id)->sole();
    $issued = app(RetainedNoteSchedules::class)->find($holding->business_campaign_id);
    expect($issued)->not->toBeNull()
        ->and(array_map(fn (RetainedHoldingSchedule $candidate): string => $candidate->holdingId, $issued->holdings))->toContain($holding->id);

    $partial = new RetainedNoteSchedule($issued->campaignId, $issued->businessId, $issued->fundingId, $issued->closing, $issued->issuedAt,
        $issued->principal, $issued->contractualReturn, $issued->gross,
        array_values(array_filter($issued->holdings, fn (RetainedHoldingSchedule $candidate): bool => $candidate->holdingId !== $holding->id)),
        $issued->instalments);
    app()->instance(RetainedNoteSchedules::class, new class($partial) implements RetainedNoteSchedules
    {
        public function __construct(private RetainedNoteSchedule $schedule) {}

        public function find(string $campaignId): ?RetainedNoteSchedule
        {
            return $campaignId === $this->schedule->campaignId ? $this->schedule : null;
        }
    });

    expect(fn () => app(InvestorHoldings::class)->find($user->id, $holding->id))->toThrow(RuntimeException::class, 'PRIMARY_ISSUANCE_INTEGRITY_FAILED')
        ->and(fn () => app(InvestorHoldings::class)->ofCommitment($user->id, $holding->commitment_id))
        ->toThrow(RuntimeException::class, 'PRIMARY_ISSUANCE_INTEGRITY_FAILED');
});

it('refuses a commitment whose campaign closure bound no cash return to it', function (): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $campaign = PrimaryReservationFixture::campaign();
    $investor = PrimaryReservationFixture::investor();
    $checkout = app(PrimaryCheckout::class);
    $checkout->reserve($investor['user']->id, 1, $campaign->id, '3', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
    $root = PrimaryReservationRecord::query()->sole();
    $initial = PrimaryReservationVersion::query()->sole()->payload;
    $commitment = (string) $checkout->confirm($investor['user']->id, 1, $campaign->id, $root->id, 1,
        $initial['terms']['disclosure_version'], $initial['disclosure_sha256'], (string) Str::uuid(), PrimaryReservationFixture::terms(...))['data']['commitment_id'];

    /*
     * A legacy closure: the campaign reads as expired, but nothing was ever bound back to this
     * reservation in `primary_campaign_closure_returns`.
     */
    app()->instance(PrimaryCampaignSource::class, new class(app(PrimaryCampaignSource::class)) implements PrimaryCampaignSource
    {
        public function __construct(private PrimaryCampaignSource $real) {}

        public function lockBusiness(string $campaignId): void
        {
            $this->real->lockBusiness($campaignId);
        }

        public function rejectKnownConnections(string $businessId, array $partyIds): void
        {
            $this->real->rejectKnownConnections($businessId, $partyIds);
        }

        public function lock(string $campaignId): array
        {
            return $this->real->lock($campaignId);
        }

        public function lockForFunding(string $campaignId): array
        {
            return $this->real->lockForFunding($campaignId);
        }

        public function lockRetained(string $campaignId): array
        {
            return $this->real->lockRetained($campaignId);
        }

        public function presentation(string $campaignId): array
        {
            return [...$this->real->presentation($campaignId), 'closed' => true, 'closure' => 'expired'];
        }
    });

    $this->actingAs($investor['user'])->get(route('investor.commitments.show', $commitment))->assertStatus(409)
        ->assertInertia(fn ($page) => $page->component('investor/commitment')->where('commitment', null)
            ->where('refusal', ['code' => 'COMMITMENT_STATE_UNAVAILABLE', 'status' => 409]));
});
