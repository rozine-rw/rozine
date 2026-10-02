<?php

declare(strict_types=1);

use App\Application\Business\Contracts\BusinessCampaignStore;
use App\Application\Operations\Contracts\ChangeFeed;
use App\Application\Operations\ReadChanges;
use App\Domain\Operations\ChangeScope;
use App\Models\BusinessCampaign;
use App\Models\InvestorWallet;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Support\AuditSealingFixture;
use Tests\Support\InvestorWalletFixture;

/*
 * The C3 effects that emit a beacon change (S4-E), each in the effect's own transaction: a
 * replayed or refused command, and a provider event that moves nothing, emit nothing.
 */

/**
 * A live campaign, released by staff and published by its Business signatory.
 *
 * @return array{fixture: array<string, mixed>, user: User, staff: User, business: string, campaign: BusinessCampaign}
 */
function liveCampaign(): array
{
    $fixture = AuditSealingFixture::ready();
    AuditSealingFixture::seal($fixture);
    AuditSealingFixture::cosign($fixture);
    $user = $fixture['audit']['authority']['users'][0];
    app(BusinessCampaignStore::class)->release($fixture['audit']['staff']->id, $fixture['application']->id, 0, 'Reviewed.', (string) Str::uuid());
    app(BusinessCampaignStore::class)->publish($user->id, 1, $fixture['audit']['business'], $fixture['application']->id,
        $fixture['application']->refresh()->revision, 'listing-fee-waiver-1', (string) Str::uuid());

    return ['fixture' => $fixture, 'user' => $user, 'staff' => $fixture['audit']['staff'], 'business' => $fixture['audit']['business'],
        'campaign' => BusinessCampaign::query()->sole()];
}

/** @return list<string> */
function campaignRows(): array
{
    return array_values(array_filter(feedRows(), fn (string $row): bool => str_contains($row, '|campaign|')));
}

/** @return list<string> */
function feedRows(): array
{
    return array_values(DB::table('change_feed')->orderBy('id')->get()
        ->map(fn (object $row): string => ($row->party_id ?? $row->business_id ?? $row->staff_queue).'|'.$row->topic.'|'.$row->subject.'@'.$row->revision)->all());
}

it('emits a wallet change when a deposit is recorded and each time its provider outcome applies', function (): void {
    $fixture = InvestorWalletFixture::ready();
    $cursor = app(ReadChanges::class)->cursor($fixture['user']->id);
    $requestId = (string) Str::uuid();
    $recorded = InvestorWalletFixture::deposit($fixture, requestId: $requestId);
    InvestorWalletFixture::deposit($fixture, requestId: $requestId);
    InvestorWalletFixture::deposit($fixture, '1', (string) Str::uuid());
    $wallet = InvestorWallet::query()->sole()->id;
    $key = $fixture['party']->id.'|wallet|'.$wallet;

    expect(feedRows())->toBe([$key.'@1']);

    InvestorWalletFixture::settle($recorded['data']['intent_id'], 'pending', 'event-1');
    InvestorWalletFixture::settle($recorded['data']['intent_id'], 'pending', 'event-1');
    InvestorWalletFixture::settle($recorded['data']['intent_id'], 'succeeded', 'event-2');
    InvestorWalletFixture::settle($recorded['data']['intent_id'], 'failed', 'event-3');

    expect(feedRows())->toBe([$key.'@1', $key.'@2'])
        ->and(app(ReadChanges::class)->handle($fixture['user']->id, ['wallet'], $cursor)['changes'])
        ->toBe([['topic' => 'wallet', 'subject' => $wallet, 'revision' => 2]]);
});

it('rolls a wallet change back with the deposit that recorded it', function (): void {
    $fixture = InvestorWalletFixture::ready();

    expect(fn () => DB::transaction(function () use ($fixture): void {
        InvestorWalletFixture::deposit($fixture);
        throw new RuntimeException('outer rollback');
    }))->toThrow(RuntimeException::class, 'outer rollback')
        ->and(feedRows())->toBe([]);
});

it('emits a Business campaign change once when its campaign is cancelled', function (): void {
    $this->freezeSecond();
    $live = liveCampaign();
    $cursor = app(ReadChanges::class)->cursor($live['user']->id);
    $cancel = fn (int $revision, string $request): array => app(BusinessCampaignStore::class)->cancel($live['user']->id, 1, $live['business'], $live['campaign']->id, $revision, null, $request);

    expect($cancel(0, (string) Str::uuid())['code'])->toBe('VERSION_CONFLICT')->and(campaignRows())->toBe([]);

    $request = (string) Str::uuid();
    expect($cancel(1, $request)['code'])->toBe('CAMPAIGN_CANCELLED')
        ->and($cancel(1, $request)['code'])->toBe('CAMPAIGN_CANCELLED')
        ->and(campaignRows())->toBe([$live['business'].'|campaign|'.$live['campaign']->id.'@1'])
        ->and(app(ReadChanges::class)->handle($live['user']->id, ['campaign'], $cursor)['changes'])
        ->toBe([['topic' => 'campaign', 'subject' => $live['campaign']->id, 'revision' => 1]]);
});

it('emits a Business campaign change once when its campaign expires', function (): void {
    $this->freezeSecond();
    $live = liveCampaign();
    $this->travelTo($live['campaign']->expires_at);

    expect(app(BusinessCampaignStore::class)->expireDue(100))->toBe(1)
        ->and(app(BusinessCampaignStore::class)->expireDue(100))->toBe(0)
        ->and(campaignRows())->toBe([$live['business'].'|campaign|'.$live['campaign']->id.'@1']);
});

it('delivers a campaign closure after progress changes to the same campaign', function (): void {
    $this->freezeSecond();
    $live = liveCampaign();
    $cursor = app(ReadChanges::class)->cursor($live['user']->id);
    foreach (range(1, 3) as $progress) {
        DB::transaction(fn () => app(ChangeFeed::class)->record(ChangeScope::business($live['business']), 'campaign', $live['campaign']->id));
    }
    $progressed = app(ReadChanges::class)->handle($live['user']->id, ['campaign'], $cursor);

    expect(app(BusinessCampaignStore::class)->cancel($live['user']->id, 1, $live['business'], $live['campaign']->id, 1, null, (string) Str::uuid())['code'])
        ->toBe('CAMPAIGN_CANCELLED')
        ->and($progressed['changes'])->toBe([['topic' => 'campaign', 'subject' => $live['campaign']->id, 'revision' => 3]])
        ->and(app(ReadChanges::class)->handle($live['user']->id, ['campaign'], $progressed['next_cursor'])['changes'])
        ->toBe([['topic' => 'campaign', 'subject' => $live['campaign']->id, 'revision' => 4]]);
});

it('emits a staff queue change when an application is submitted and when staff release it', function (): void {
    $this->freezeSecond();
    $fixture = AuditSealingFixture::ready();
    $queue = 'applications|staff_queue|applications';

    expect(feedRows())->toBe([$queue.'@1']);

    AuditSealingFixture::seal($fixture);
    AuditSealingFixture::cosign($fixture);
    $cursor = app(ReadChanges::class)->cursor($fixture['audit']['staff']->id);
    $release = fn (int $revision, string $request): array => app(BusinessCampaignStore::class)->release($fixture['audit']['staff']->id, $fixture['application']->id, $revision, 'Reviewed.', $request);

    expect($release(1, (string) Str::uuid())['code'])->toBe('VERSION_CONFLICT');

    $request = (string) Str::uuid();
    $released = $release(0, $request);

    expect($released['code'])->toBe('APPLICATION_RELEASED')->and($release(0, $request))->toBe($released)
        ->and(feedRows())->toBe([$queue.'@1', $queue.'@2'])
        ->and(app(ReadChanges::class)->handle($fixture['audit']['staff']->id, ['staff_queue'], $cursor)['changes'])
        ->toBe([['topic' => 'staff_queue', 'subject' => 'applications', 'revision' => 2]]);
});

it('gives each live page its beacon link at the render-time cursor, and none over a staff token', function (): void {
    $this->freezeSecond();
    $this->withoutVite();
    $live = liveCampaign();
    $wallet = InvestorWalletFixture::ready();
    $pages = [
        [$wallet['user'], route('investor.wallet'), '/changes?topics=wallet&after='],
        [$live['user'], route('business.campaigns.show', ['business' => $live['business'], 'campaign' => $live['campaign']->id]), '/changes?topics=campaign&after='],
        [$live['staff'], route('staff.applications.index'), '/admin/changes?topics=staff_queue&after='],
    ];
    $links = [];
    foreach ($pages as [$user, $url, $prefix]) {
        $links[] = $link = $this->actingAs($user)->get($url)->assertOk()->viewData('page')['props']['links']['changes'];
        expect($link['method'])->toBe('get')->and($link['url'])->toStartWith($prefix);
    }
    InvestorWalletFixture::deposit($wallet);
    app(BusinessCampaignStore::class)->cancel($live['user']->id, 1, $live['business'], $live['campaign']->id, 1, null, (string) Str::uuid());

    expect($this->actingAs($wallet['user'])->getJson($links[0]['url'])->assertOk()->json('changes.0.topic'))->toBe('wallet')
        ->and($this->actingAs($live['user'])->getJson($links[1]['url'])->assertOk()->json('changes'))
        ->toBe([['topic' => 'campaign', 'subject' => $live['campaign']->id, 'revision' => 1]])
        ->and($this->actingAs($live['staff'])->getJson($links[2]['url'])->assertOk()->json('changes'))->toBe([]);

    Sanctum::actingAs($wallet['user'], ['investor:read']);
    expect($this->getJson(route('api.v1.investor.wallet'))->json('data.links.changes.url'))->toStartWith('/api/v1/changes?topics=wallet&after=');
    Sanctum::actingAs($live['user'], ['business:read']);
    expect($this->getJson(route('api.v1.business.campaigns.show', ['business' => $live['business'], 'campaign' => $live['campaign']->id]))
        ->json('data.links.changes.url'))->toStartWith('/api/v1/changes?topics=campaign&after=');
    Sanctum::actingAs($live['staff'], ['staff:applications:read']);
    expect($this->getJson(route('api.v1.staff.applications.index'))->json('data.links'))->toHaveKey('changes', null);
});
