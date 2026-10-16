<?php

declare(strict_types=1);

use App\Application\Business\Contracts\BusinessCampaignStore;
use App\Application\Business\Contracts\PrimaryCampaignSource;
use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Application\Primary\Contracts\PrimaryReservations;
use App\Domain\Operations\CommandRejection;
use App\Models\BusinessCampaign;
use App\Models\BusinessProfile;
use App\Models\LedgerEntry;
use App\Models\PrimaryCommitment;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use Illuminate\Support\Str;
use Tests\Support\AuditSealingFixture;
use Tests\Support\BusinessAuthorityFixture;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

beforeEach(function (): void {
    $this->freezeSecond();
});

it('rejects the Business itself and declared mandate people by canonical Party', function (string $target): void {
    $authority = BusinessAuthorityFixture::make('organization');
    $business = BusinessAuthorityFixture::configure($authority)['data']['business']['id'];
    $party = $target === 'entity' ? $authority['entity'] : $authority['people'][0]->id;
    expect(fn () => app(PrimaryCampaignSource::class)->rejectKnownConnections($business, [strtoupper($party)]))
        ->toThrow(CommandRejection::class, 'CONNECTED_BUSINESS_INVESTMENT_PROHIBITED');
})->with(['entity', 'person']);

it('leaves unrelated Parties to the remaining admission gates without writing records', function (): void {
    $authority = BusinessAuthorityFixture::make('organization');
    $business = BusinessAuthorityFixture::configure($authority)['data']['business']['id'];
    $before = LedgerEntry::query()->count();
    app(PrimaryCampaignSource::class)->rejectKnownConnections($business, [(string) Str::ulid()]);
    expect(LedgerEntry::query()->count())->toBe($before);
});

it('refuses missing Business or current mandate evidence', function (bool $missingBusiness): void {
    $id = $missingBusiness ? (string) Str::ulid() : BusinessProfile::factory()->create()->id;
    expect(fn () => app(PrimaryCampaignSource::class)->rejectKnownConnections($id, [(string) Str::ulid()]))
        ->toThrow(CommandRejection::class, $missingBusiness ? 'BUSINESS_NOT_FOUND' : 'MANDATE_REQUIRED');
})->with([true, false]);

it('refuses a mandate outside its current active window', function (string $case): void {
    $authority = BusinessAuthorityFixture::make('organization');
    if ($case === 'revoked') {
        BusinessAuthorityFixture::configure($authority);
        $authority['terms']['status'] = 'revoked';
    } elseif ($case === 'future') {
        $authority['terms']['effective_at'] = now('UTC')->addSecond()->format('Y-m-d\TH:i:s\Z');
    } else {
        $authority['terms']['expires_at'] = now('UTC')->format('Y-m-d\TH:i:s\Z');
    }
    $business = BusinessAuthorityFixture::configure($authority, $case === 'revoked' ? 1 : 0)['data']['business']['id'];
    expect(fn () => app(PrimaryCampaignSource::class)->rejectKnownConnections($business, [(string) Str::ulid()]))
        ->toThrow(CommandRejection::class, 'MANDATE_REQUIRED');
})->with(['revoked', 'future', 'expiry']);

it('rejects a self reserve before invoking admission or creating a hold', function (): void {
    $campaign = PrimaryReservationFixture::campaign();
    $business = BusinessProfile::query()->findOrFail($campaign->business_id);
    $before = LedgerEntry::query()->count();
    expect(fn () => app(PrimaryReservations::class)->reserve($campaign->id, $business->entity_party_id, (string) Str::ulid(), '1',
        function (): never {
            throw new RuntimeException('Admission must not run.');
        }))
        ->toThrow(CommandRejection::class, 'CONNECTED_BUSINESS_INVESTMENT_PROHIBITED');
    expect(PrimaryReservationRecord::query()->count())->toBe(0)->and(LedgerEntry::query()->count())->toBe($before);
});

it('rechecks newly declared connections before confirmation and funding evidence', function (bool $funding): void {
    InvestorWalletFixture::policy(maximum: null);
    $fixture = AuditSealingFixture::ready(signatories: 2, requiredSignatories: 1);
    AuditSealingFixture::seal($fixture);
    AuditSealingFixture::cosign($fixture);
    $campaigns = app(BusinessCampaignStore::class);
    $campaigns->release($fixture['audit']['staff']->id, $fixture['application']->id, 0, 'Verified release.', (string) Str::uuid());
    $campaigns->publish($fixture['audit']['authority']['users'][0]->id, 1, $fixture['audit']['business'],
        $fixture['application']->id, $fixture['application']->refresh()->revision, 'listing-fee-waiver-1', (string) Str::uuid());
    $campaign = BusinessCampaign::query()->sole();
    $checkout = app(PrimaryCheckout::class);
    foreach (range(1, $funding ? 2 : 1) as $index) {
        $investor = PrimaryReservationFixture::investor();
        $result = $checkout->reserve($investor['user']->id, 1, $campaign->id, '1080', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
        $root = PrimaryReservationRecord::query()->whereKey($result['data']['reservation_id'])->sole();
        $version = PrimaryReservationVersion::query()->where('primary_reservation_id', $root->id)->sole();
        if ($funding) {
            expect($checkout->confirm($investor['user']->id, 1, $campaign->id, $root->id, 1,
                $version->payload['terms']['disclosure_version'], $version->payload['disclosure_sha256'], (string) Str::uuid(), PrimaryReservationFixture::terms(...))['code'])->toBe('RESERVATION_CONFIRMED');
        }
    }
    $authority = $fixture['audit']['authority'];
    $authority['terms']['people'][] = ['party_id' => $investor['party']->id, 'name' => 'Newly declared owner', 'roles' => ['beneficial_owner'], 'permissions' => []];
    BusinessAuthorityFixture::configure($authority, 1);
    $before = LedgerEntry::query()->count();
    if ($funding) {
        expect(fn () => app(PrimaryReservations::class)->lockFundingCandidate($campaign->id))
            ->toThrow(CommandRejection::class, 'CONNECTED_BUSINESS_INVESTMENT_PROHIBITED');
    } else {
        expect($checkout->confirm($investor['user']->id, 1, $campaign->id, $root->id, 1,
            $version->payload['terms']['disclosure_version'], $version->payload['disclosure_sha256'], (string) Str::uuid(), PrimaryReservationFixture::terms(...))['code'])
            ->toBe('CONNECTED_BUSINESS_INVESTMENT_PROHIBITED');
        expect(PrimaryCommitment::query()->count())->toBe(0);
        expect($checkout->release($investor['user']->id, 1, $campaign->id, $root->id, 1, (string) Str::uuid())['code'])->toBe('RESERVATION_RELEASED');
        $before++;
    }
    expect(LedgerEntry::query()->count())->toBe($before);
})->with(['confirmation' => false, 'funding candidate' => true]);
