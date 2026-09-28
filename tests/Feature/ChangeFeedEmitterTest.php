<?php

declare(strict_types=1);

use App\Application\Business\Contracts\BusinessCampaignStore;
use App\Application\Operations\ReadChanges;
use App\Models\BusinessCampaign;
use App\Models\InvestorWallet;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
    return DB::table('change_feed')->orderBy('id')->get()
        ->map(fn (object $row): string => ($row->party_id ?? $row->business_id ?? $row->staff_queue).'|'.$row->topic.'|'.$row->subject.'@'.$row->revision)->all();
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
        ->and(campaignRows())->toBe([$live['business'].'|campaign|'.$live['campaign']->id.'@2'])
        ->and(app(ReadChanges::class)->handle($live['user']->id, ['campaign'], $cursor)['changes'])
        ->toBe([['topic' => 'campaign', 'subject' => $live['campaign']->id, 'revision' => 2]]);
});

it('emits a Business campaign change once when its campaign expires', function (): void {
    $this->freezeSecond();
    $live = liveCampaign();
    $this->travelTo($live['campaign']->expires_at);

    expect(app(BusinessCampaignStore::class)->expireDue(100))->toBe(1)
        ->and(app(BusinessCampaignStore::class)->expireDue(100))->toBe(0)
        ->and(campaignRows())->toBe([$live['business'].'|campaign|'.$live['campaign']->id.'@2']);
});
