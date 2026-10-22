<?php

declare(strict_types=1);

use App\Application\Disbursement\ManageDisbursements;
use App\Application\Disbursement\OpenFundedDisbursements;
use App\Application\Disbursement\ReconcileDisbursements;
use App\Models\Disbursement;
use App\Models\DisbursementClosing;
use App\Models\DisbursementEvent;
use App\Models\DisbursementIntent;
use App\Models\DisbursementProviderCall;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\DisbursementFixture;

/*
 * Automatic disbursement end to end (BRS v2.0 SVC-R01, ADM-R02): a funded campaign is paid by the
 * system as soon as nothing blocks it, a failed recheck refunds it, and one staff member may hold
 * and release it while it waits.
 */

it('pays a funded campaign automatically, dispatches after commit and issues once on a reconciled success', function (): void {
    $campaign = DisbursementFixture::sources()->fund();
    expect(app(OpenFundedDisbursements::class)->handle())->toBe(['opened' => 1, 'known' => 0, 'queued' => 1, 'failed' => 0]);
    $disbursement = Disbursement::query()->where('business_campaign_id', $campaign->campaignId)->sole();
    $intent = DisbursementIntent::query()->where('disbursement_id', $disbursement->id)->sole();
    $viewer = DisbursementFixture::staff(['treasury']);
    expect(DisbursementProviderCall::query()->where('intent_id', $intent->id)->where('kind', 'send')->count())->toBe(1)
        ->and(DisbursementFixture::detail($viewer, $disbursement)['state'])->toBe('dispatched')
        ->and(app(OpenFundedDisbursements::class)->handle())->toMatchArray(['opened' => 0, 'queued' => 0, 'failed' => 0])
        ->and(DisbursementIntent::query()->count())->toBe(1);
    DisbursementFixture::provider()->scriptQuery($intent->id, 'succeeded');
    $result = app(ReconcileDisbursements::class)->handle();
    expect($result['decisions'])->toBe(['matched_success' => 1])
        ->and(DisbursementClosing::query()->where('disbursement_id', $disbursement->id)->sole()->kind)->toBe('issued')
        ->and(DisbursementFixture::sources()->effects($campaign->campaignId))->toHaveCount(1)
        ->and(DisbursementFixture::detail($viewer, $disbursement)['state'])->toBe('succeeded');
});

it('records the automatic intent as the system, bound to the verified destination and the principal less the fee', function (): void {
    ['disbursement' => $disbursement, 'intent' => $intent] = DisbursementFixture::paid();
    $event = DisbursementEvent::query()->where('disbursement_id', $disbursement->id)->where('kind', 'auto_intent_recorded')->sole();
    expect([$intent->maker_user_id, $intent->checker_user_id, $intent->rule_version, $intent->revision])->toBe([null, null, 'brs-2.0-svc-r01', 0])
        ->and([$disbursement->amount, $disbursement->platform_fee, $intent->amount])->toBe(['3000000', '90000', '2910000'])
        ->and($intent->operation_id)->toMatch('/^[0-9a-hjkmnp-tv-z]{26}$/')
        ->and(DB::table('command_operations')->where('id', $intent->operation_id)->exists())->toBeFalse()
        ->and(Str::isUuid($intent->request_id))->toBeTrue()
        ->and([$event->revision, $event->actor_user_id, $event->operation_id, $event->request_id])->toBe([1, null, null, null])
        ->and([$event->binding_sha256, $event->destination_sha256])->toBe([$intent->binding_sha256, $intent->destination_sha256])
        ->and($event->payload['precheck']['state'])->toBe('passed')
        ->and($event->payload['destination']['id'])->toBe($intent->destination_id);
    $detail = DisbursementFixture::detail(DisbursementFixture::staff(['compliance']), $disbursement);
    expect($detail['automation'])->toBe(['rule_version' => 'brs-2.0-svc-r01', 'payout' => ['currency' => 'RWF', 'amount' => '2910000'], 'waiting_for' => null])
        ->and([$detail['revision'], $detail['precheck']['state'], $detail['intent']['operation_id'], $detail['allowed_actions']])
        ->toBe([2, 'passed', $intent->operation_id, []])
        ->and($detail['trail'][1]['actor'])->toBe('System');
});

it('closes and refunds a funded campaign whose automatic recheck fails, with no intent or staff authority', function (): void {
    $campaign = DisbursementFixture::sources()->fund();
    DisbursementFixture::sources()->scriptRecheck($campaign->campaignId, 'failed', ['exposure']);
    expect(app(OpenFundedDisbursements::class)->handle())->toBe(['opened' => 1, 'known' => 0, 'queued' => 0, 'failed' => 1]);
    $disbursement = Disbursement::query()->where('business_campaign_id', $campaign->campaignId)->sole();
    $closing = DisbursementClosing::query()->sole();
    $event = DisbursementEvent::query()->where('disbursement_id', $disbursement->id)->sole();
    expect([$closing->kind, $closing->cause, $closing->causes, $closing->intent_id])->toBe(['failed_closing', 'auto_recheck', ['exposure'], null])
        ->and([$closing->operation_id, $closing->actor_user_id, $closing->request_id])->toBe([null, null, null])
        ->and([$event->kind, $event->actor_user_id, $event->operation_id, $event->request_id])->toBe(['failed_closing', null, null, null])
        ->and(DisbursementIntent::query()->count())->toBe(0)
        ->and(DisbursementFixture::sources()->effects($campaign->campaignId)[0]['cause'])->toBe('auto_recheck');
    app(OpenFundedDisbursements::class)->handle();
    $detail = DisbursementFixture::detail(DisbursementFixture::staff(['treasury']), $disbursement);
    expect(DisbursementClosing::query()->count())->toBe(1)
        ->and([$detail['state'], $detail['precheck']['state'], $detail['precheck']['causes'], $detail['refund']['commitments'], $detail['intent']])
        ->toBe(['failed_closing', 'failed', ['exposure'], 2, null]);
});

it('keeps a disbursement ready while its destination is unverified or its recheck unavailable, and pays it on a later run', function (): void {
    ['disbursement' => $disbursement, 'campaign' => $campaign] = DisbursementFixture::funded();
    $viewer = DisbursementFixture::staff(['compliance']);
    $detail = DisbursementFixture::detail($viewer, $disbursement);
    expect([$detail['state'], $detail['automation']['waiting_for'], $detail['precheck']['state'], $detail['intent'], $detail['allowed_actions']])
        ->toBe(['ready', 'verified_payout_destination', 'not_run', null, ['disbursement.hold']])
        ->and(app(ManageDisbursements::class)->page($viewer->id, null, null, 25)['needs_attention'])->toBe(1);
    DisbursementFixture::sources()->scriptRecheck($campaign->campaignId, 'unavailable', ['conditions_precedent']);
    DisbursementFixture::release($campaign);
    expect(DisbursementIntent::query()->count())->toBe(0)->and(DisbursementClosing::query()->count())->toBe(0)
        ->and(DisbursementFixture::detail($viewer, $disbursement)['automation']['waiting_for'])->toBe('next_run');
    DisbursementFixture::sources()->scriptRecheck($campaign->campaignId, 'passed');
    expect(app(OpenFundedDisbursements::class)->handle())->toBe(['opened' => 0, 'known' => 1, 'queued' => 1, 'failed' => 0])
        ->and(DisbursementFixture::detail($viewer, $disbursement)['state'])->toBe('dispatched');
});

it('lets one staff member hold a waiting disbursement and release it, and pays nothing while it is held', function (): void {
    ['disbursement' => $disbursement, 'campaign' => $campaign] = DisbursementFixture::funded();
    $staff = DisbursementFixture::staff(['compliance']);
    expect(DisbursementFixture::command($staff, $disbursement, 'hold', 0, null, 'Checking the mandate.')['code'])->toBe('DISBURSEMENT_HELD');
    DisbursementFixture::release($campaign);
    $held = DisbursementFixture::detail($staff, $disbursement);
    expect(DisbursementIntent::query()->count())->toBe(0)
        ->and([$held['state'], $held['hold']['reason'], $held['automation']['waiting_for'], $held['allowed_actions']])
        ->toBe(['on_hold', 'Checking the mandate.', null, ['disbursement.release_hold']])
        ->and(DisbursementFixture::command($staff, $disbursement, 'release_hold', 1, null, 'Mandate confirmed.')['code'])->toBe('DISBURSEMENT_HOLD_RELEASED')
        ->and(DisbursementFixture::detail($staff, $disbursement)['automation']['waiting_for'])->toBe('next_run')
        ->and(app(OpenFundedDisbursements::class)->handle()['queued'])->toBe(1);
    $paid = DisbursementFixture::detail($staff, $disbursement);
    expect([$paid['state'], $paid['revision'], $paid['allowed_actions']])->toBe(['dispatched', 4, []])
        ->and(DisbursementFixture::command($staff, $disbursement, 'hold', 4)['code'])->toBe('DISBURSEMENT_IN_FLIGHT');
});

it('still folds a legacy authorized disbursement and pays it automatically', function (): void {
    ['disbursement' => $disbursement, 'campaign' => $campaign] = DisbursementFixture::funded();
    $maker = DisbursementFixture::staff(['treasury']);
    DB::table('disbursement_events')->insert(['id' => strtolower((string) Str::ulid()), 'disbursement_id' => $disbursement->id, 'revision' => 1,
        'kind' => 'authorized', 'actor_user_id' => $maker->id, 'operation_id' => strtolower((string) Str::ulid()), 'request_id' => (string) Str::uuid(),
        'binding_sha256' => str_repeat('b', 64), 'destination_sha256' => str_repeat('d', 64),
        'payload' => encrypt(json_encode(['reason' => 'Legacy authorization.', 'destination' => ['masked' => 'Legacy Bank •••• 0001'],
            'precheck' => ['state' => 'passed', 'checked_at' => now()->toIso8601String(), 'policy_version' => 'v1', 'causes' => []]]), false),
        'sha256' => str_repeat('0', 64), 'created_at' => now()]);
    expect(DisbursementFixture::detail($maker, $disbursement)['state'])->toBe('awaiting_second_approver');
    DisbursementFixture::release($campaign);
    $intent = DisbursementIntent::query()->where('disbursement_id', $disbursement->id)->sole();
    $detail = DisbursementFixture::detail($maker, $disbursement);
    expect([$intent->maker_user_id, $intent->checker_user_id, $intent->revision])->toBe([null, null, 1])
        ->and([$detail['state'], $detail['revision']])->toBe(['dispatched', 3])
        ->and(array_column(array_column($detail['trail'], 'action'), 'code'))->toBe(['dispatched', 'auto_intent_recorded', 'authorized']);
});
