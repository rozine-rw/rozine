<?php

declare(strict_types=1);

use App\Application\Business\Contracts\BusinessCampaignStore;
use App\Application\Business\Contracts\PrimaryCampaignSource;
use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Application\Primary\Contracts\PrimaryReservations;
use App\Domain\Operations\CommandRejection;
use App\Models\BusinessCampaign;
use App\Models\BusinessCampaignClosure;
use App\Models\PrimaryCommitment;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\AuditSealingFixture;
use Tests\Support\BusinessAuthorityFixture;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

/*
 * Review repros for #175 ranges C (3141615a..129ad273) and E (4d1e908b..79d43341).
 */

beforeEach(function (): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $fixture = AuditSealingFixture::ready(signatories: 2, requiredSignatories: 1);
    AuditSealingFixture::seal($fixture);
    AuditSealingFixture::cosign($fixture);
    $campaigns = app(BusinessCampaignStore::class);
    $campaigns->release($fixture['audit']['staff']->id, $fixture['application']->id, 0, 'Verified release.', (string) Str::uuid());
    $campaigns->publish($fixture['audit']['authority']['users'][0]->id, 1, $fixture['audit']['business'],
        $fixture['application']->id, $fixture['application']->refresh()->revision, 'listing-fee-waiver-1', (string) Str::uuid());
    $this->fixture = $fixture;
    $this->campaign = BusinessCampaign::query()->sole();
    $this->checkout = app(PrimaryCheckout::class);
    $this->reserve = function (array $investor, string $units = '1080'): PrimaryReservationRecord {
        $result = $this->checkout->reserve($investor['user']->id, 1, $this->campaign->id, $units, (string) Str::uuid(), PrimaryReservationFixture::terms(...));

        return PrimaryReservationRecord::query()->whereKey($result['data']['reservation_id'])->sole();
    };
    $this->confirm = function (array $investor, PrimaryReservationRecord $root): string {
        $version = PrimaryReservationVersion::query()->where('primary_reservation_id', $root->id)->orderByDesc('revision')->firstOrFail();

        return $this->checkout->confirm($investor['user']->id, 1, $this->campaign->id, $root->id, $version->revision,
            $version->payload['terms']['disclosure_version'], $version->payload['disclosure_sha256'], (string) Str::uuid(), PrimaryReservationFixture::terms(...))['code'];
    };
});

it('C-R1: a raise fully committed before its deadline can never become a funding candidate after it, and cannot close either', function (): void {
    foreach ([1, 2] as $index) {
        $investor = PrimaryReservationFixture::investor();
        expect(($this->confirm)($investor, ($this->reserve)($investor)))->toBe('RESERVATION_CONFIRMED');
    }
    expect(PrimaryCommitment::query()->count())->toBe(2);
    $this->travelTo($this->campaign->expires_at);
    expect(fn () => DB::transaction(fn () => app(PrimaryReservations::class)->lockFundingCandidate($this->campaign->id)))
        ->toThrow(CommandRejection::class, 'CAMPAIGN_CLOSED');
    // The Business expiry sweep defers a committed raise, so nothing closes it either.
    expect(app(BusinessCampaignStore::class)->expireDue(10))->toBe(0)
        ->and(BusinessCampaignClosure::query()->count())->toBe(0);
});

it('C-R2: the post-cash deadline recheck accepts the final microsecond before expiry', function (): void {
    foreach ([1, 2] as $index) {
        $investor = PrimaryReservationFixture::investor();
        ($this->confirm)($investor, ($this->reserve)($investor));
    }
    $elapsed = false;
    DB::listen(function (QueryExecuted $query) use (&$elapsed): void {
        if (! $elapsed && str_contains($query->sql, 'from "ledger_entries"')) {
            $elapsed = true;
            $this->travelTo($this->campaign->expires_at->subMicrosecond());
        }
    });
    expect(DB::transaction(fn () => app(PrimaryReservations::class)->lockFundingCandidate($this->campaign->id))->purchases)->toHaveCount(2)
        ->and($elapsed)->toBeTrue();
});

it('C-R3: purchases are in reservation id order, not commitment id order (FundedCampaign in #176 requires ascending commitment ids)', function (): void {
    $first = PrimaryReservationFixture::investor();
    $second = PrimaryReservationFixture::investor();
    $firstRoot = ($this->reserve)($first);
    $secondRoot = ($this->reserve)($second);
    usleep(3000);
    expect(($this->confirm)($second, $secondRoot))->toBe('RESERVATION_CONFIRMED');
    usleep(3000);
    expect(($this->confirm)($first, $firstRoot))->toBe('RESERVATION_CONFIRMED');
    $candidate = DB::transaction(fn () => app(PrimaryReservations::class)->lockFundingCandidate($this->campaign->id));
    $commitmentIds = array_column($candidate->purchases, 'commitment_id');
    $sorted = $commitmentIds;
    sort($sorted, SORT_STRING);
    expect(array_column($candidate->purchases, 'reservation_id'))->toBe([$firstRoot->id, $secondRoot->id])
        ->and($commitmentIds)->not->toBe($sorted);
});

it('C-R4/E: exact lock sequence - Business, campaign, roots, commitments, Business re-lock (connections), then wallets; no users/parties locks', function (): void {
    $investors = [PrimaryReservationFixture::investor(), PrimaryReservationFixture::investor()];
    foreach ($investors as $investor) {
        ($this->confirm)($investor, ($this->reserve)($investor));
    }
    $locks = [];
    DB::listen(function (QueryExecuted $query) use (&$locks): void {
        if (str_contains($query->sql, 'for update') || str_contains($query->sql, 'for share')) {
            preg_match('/from "([a-z_]+)"/', $query->sql, $match);
            $locks[] = $match[1] ?? $query->sql;
        }
    });
    DB::transaction(fn () => app(PrimaryReservations::class)->lockFundingCandidate($this->campaign->id));
    expect($locks)->toBe(['business_profiles', 'business_campaigns', 'business_profiles', 'primary_reservations', 'primary_commitments',
        'business_profiles', 'investor_wallets', 'investor_wallets', 'investor_wallets', 'investor_wallets'])
        ->and(array_intersect($locks, ['users', 'parties', 'staff_users']))->toBe([]);
});

it('E-R5: a mandate effective exactly now is current (inclusive start)', function (): void {
    $authority = $this->fixture['audit']['authority'];
    $authority['terms']['effective_at'] = now('UTC')->format('Y-m-d\TH:i:s\Z');
    $business = BusinessAuthorityFixture::configure($authority, 1)['data']['business']['id'];
    DB::transaction(fn () => app(PrimaryCampaignSource::class)->rejectKnownConnections($business, [(string) Str::ulid()]));
    expect(fn () => DB::transaction(fn () => app(PrimaryCampaignSource::class)->rejectKnownConnections($business,
        [(string) Str::ulid(), strtoupper($authority['people'][0]->id), strtoupper($authority['people'][0]->id)])))
        ->toThrow(CommandRejection::class, 'CONNECTED_BUSINESS_INVESTMENT_PROHIBITED');
});

it('E-R6: expiry is exempt - a hold whose Investor became a declared connection still expires and returns cash', function (): void {
    $investor = PrimaryReservationFixture::investor();
    $root = ($this->reserve)($investor);
    $authority = $this->fixture['audit']['authority'];
    $authority['terms']['people'][] = ['party_id' => $investor['party']->id, 'name' => 'Newly declared owner', 'roles' => ['beneficial_owner'], 'permissions' => []];
    BusinessAuthorityFixture::configure($authority, 1);
    $this->travelTo($root->expires_at);
    expect(app(PrimaryReservations::class)->expireDue(10))->toBe(1)
        ->and(PrimaryReservationVersion::query()->where('primary_reservation_id', $root->id)->orderByDesc('revision')->first()->state)->toBe('expired');
});

it('E-R7: an expired mandate makes the whole raise unfundable, even for unrelated Investors', function (): void {
    foreach ([1, 2] as $index) {
        $investor = PrimaryReservationFixture::investor();
        ($this->confirm)($investor, ($this->reserve)($investor));
    }
    $authority = $this->fixture['audit']['authority'];
    $authority['terms']['expires_at'] = now('UTC')->addSecond()->format('Y-m-d\TH:i:s\Z');
    BusinessAuthorityFixture::configure($authority, 1);
    $this->travel(2)->seconds();
    expect(fn () => DB::transaction(fn () => app(PrimaryReservations::class)->lockFundingCandidate($this->campaign->id)))
        ->toThrow(CommandRejection::class, 'MANDATE_REQUIRED');
});
