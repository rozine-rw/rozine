<?php

declare(strict_types=1);

use App\Application\Disbursement\ManageDisbursements;
use App\Application\Disbursement\OpenFundedDisbursements;
use App\Application\Identity\ConfigureStaffAccess;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Domain\Disbursement\DisbursementState;
use App\Domain\Disbursement\DisbursementViolation;
use App\Domain\Identity\IdentityViolation;
use App\Domain\Operations\CommandRejection;
use App\Models\CommandOperation;
use App\Models\Disbursement;
use App\Models\DisbursementClosing;
use App\Models\DisbursementDispatch;
use App\Models\DisbursementEvent;
use App\Models\DisbursementIntent;
use App\Models\User;
use Illuminate\Support\Str;
use Tests\Support\DisbursementFixture;

/*
 * Automatic disbursement and the staff commands left around it (BRS v2.0 SVC-R01, ADM-R02; C3 v2
 * §2e): the system's own intent, the failed and waiting recheck paths, and the one-person hold and
 * release through the operation journal, with lapsed authority, fail-closed inputs and replay.
 */

/** @param list<string> $roles */
function revoke(User $user, array $roles): void
{
    app(ConfigureStaffAccess::class)->handle($user->id, true, 'Role change.', (string) Str::uuid(), $roles);
}

/**
 * @param  array<string, mixed>  $detail
 * @return list<string> the trail's event kinds, newest first
 */
function trailKinds(array $detail): array
{
    return array_values(array_map(fn (array $entry): string => $entry['action']['code'], $detail['trail']));
}

it('pays a funded campaign automatically under a system intent that no staff member recorded', function (): void {
    $campaign = DisbursementFixture::sources()->fund();
    $counts = app(OpenFundedDisbursements::class)->handle();
    $intent = DisbursementIntent::query()->sole();
    $event = DisbursementEvent::query()->where('kind', 'auto_intent_recorded')->sole();
    expect($counts)->toBe(['opened' => 1, 'known' => 0, 'queued' => 1, 'failed' => 0])
        ->and([$intent->maker_user_id, $intent->checker_user_id, $intent->rule_version, $intent->amount, $intent->revision])
        ->toBe([null, null, DisbursementState::AUTO_RULE_VERSION, '2910000', 0])
        ->and(CommandOperation::query()->whereKey($intent->operation_id)->exists())->toBeFalse()
        ->and(Str::isUuid($intent->request_id))->toBeTrue()
        ->and([$event->actor_user_id, $event->operation_id, $event->request_id, $event->revision])->toBe([null, null, null, 1])
        ->and([$event->binding_sha256, $event->destination_sha256])->toBe([$intent->binding_sha256, $intent->destination_sha256])
        ->and($event->payload['precheck'])->toMatchArray(['state' => 'passed', 'causes' => []])
        ->and($event->payload['destination']['revision'])->toBe($intent->destination_revision)
        ->and(DisbursementDispatch::query()->where('intent_id', $intent->id)->orderBy('id')->pluck('phase')->all())->toBe(['queued', 'claimed', 'sent'])
        ->and(app(OpenFundedDisbursements::class)->handle())->toBe(['opened' => 0, 'known' => 1, 'queued' => 0, 'failed' => 0])
        ->and(DisbursementIntent::query()->count())->toBe(1);
    $detail = DisbursementFixture::detail(DisbursementFixture::staff(['treasury']), Disbursement::query()->sole());
    expect([$detail['state'], $detail['revision'], $detail['precheck']['state'], $detail['automation']['waiting_for']])->toBe(['dispatched', 2, 'passed', null])
        ->and($detail['automation']['payout'])->toBe(['currency' => 'RWF', 'amount' => '2910000'])
        ->and(trailKinds($detail))->toBe(['dispatched', 'auto_intent_recorded'])
        ->and($detail['trail'][1]['actor'])->toBe('System')
        ->and($detail['allowed_actions'])->toBe(['disbursement.requery'])
        ->and(DisbursementFixture::sources()->effects($campaign->campaignId))->toBe([]);
});

it('waits without a verified destination and pays once one is verified again', function (string $state): void {
    ['disbursement' => $disbursement, 'campaign' => $campaign] = DisbursementFixture::funded();
    DisbursementFixture::sources()->setDestination($campaign->businessId, $state === 'expired' ? 'expired' : 'revoked');
    $viewer = DisbursementFixture::staff(['compliance']);
    expect(app(OpenFundedDisbursements::class)->handle())->toBe(['opened' => 0, 'known' => 1, 'queued' => 0, 'failed' => 0])
        ->and(DisbursementIntent::query()->count())->toBe(0);
    $waiting = DisbursementFixture::detail($viewer, $disbursement);
    expect([$waiting['state'], $waiting['destination'], $waiting['automation']['waiting_for'], $waiting['precheck']['state']])
        ->toBe(['ready', 'Unverified', 'verified_payout_destination', 'not_run'])
        ->and($waiting['allowed_actions'])->toBe(['disbursement.hold']);

    DisbursementFixture::release($campaign);

    expect(DisbursementFixture::detail($viewer, $disbursement)['state'])->toBe('dispatched')
        ->and(DisbursementIntent::query()->sole()->destination_revision)->toBe(2);
})->with(['revoked', 'expired']);

it('takes the deterministic failed closing on a failed recheck at payment, and waits on an unavailable one', function (): void {
    ['disbursement' => $disbursement, 'campaign' => $campaign] = DisbursementFixture::funded();
    $viewer = DisbursementFixture::staff(['treasury']);
    DisbursementFixture::sources()->scriptRecheck($campaign->campaignId, 'unavailable', ['conditions_precedent']);
    DisbursementFixture::release($campaign);
    $waiting = DisbursementFixture::detail($viewer, $disbursement);
    expect([$waiting['state'], $waiting['revision'], $waiting['automation']['waiting_for'], $waiting['precheck']['state']])->toBe(['ready', 0, 'next_run', 'not_run'])
        ->and(DisbursementClosing::query()->count())->toBe(0);

    DisbursementFixture::sources()->scriptRecheck($campaign->campaignId, 'failed', ['restriction']);
    $counts = app(OpenFundedDisbursements::class)->handle();

    $closing = DisbursementClosing::query()->sole();
    $event = DisbursementEvent::query()->where('kind', 'failed_closing')->sole();
    expect($counts)->toBe(['opened' => 0, 'known' => 1, 'queued' => 0, 'failed' => 1])
        ->and([$closing->kind, $closing->cause, $closing->causes, $closing->operation_id, $closing->actor_user_id, $closing->request_id, $closing->intent_id])
        ->toBe(['failed_closing', 'auto_recheck', ['restriction'], null, null, null, null])
        ->and([$event->actor_user_id, $event->operation_id, $event->request_id, $event->revision])->toBe([null, null, null, 1])
        ->and(DisbursementFixture::sources()->effects($campaign->campaignId)[0])->toMatchArray(['kind' => 'failed_closing', 'cause' => 'auto_recheck', 'causes' => ['restriction']])
        ->and(DisbursementFixture::sources()->effects($campaign->campaignId)[0]['refunds'][0]['fee'])->toBe('0')
        ->and(DisbursementIntent::query()->count())->toBe(0);
    $detail = DisbursementFixture::detail($viewer, $disbursement);
    expect([$detail['state'], $detail['precheck']['state'], $detail['precheck']['causes'], $detail['refund']['total']['amount'], $detail['automation']['waiting_for']])
        ->toBe(['failed_closing', 'failed', ['restriction'], $disbursement->amount, null])
        ->and($detail['trail'][0]['actor'])->toBe('System')
        ->and($detail['allowed_actions'])->toBe([])
        ->and(app(OpenFundedDisbursements::class)->handle())->toBe(['opened' => 0, 'known' => 1, 'queued' => 0, 'failed' => 0]);
});

it('records a hold once, replays the same body and refuses a changed one', function (): void {
    ['disbursement' => $disbursement] = DisbursementFixture::funded();
    $placer = DisbursementFixture::staff(['compliance']);
    $key = (string) Str::uuid();
    $first = DisbursementFixture::command($placer, $disbursement, 'hold', 0, $key);
    $replay = DisbursementFixture::command($placer, $disbursement, 'hold', 0, $key);
    expect([$first['code'], $first['revision'], $first['status']])->toBe(['DISBURSEMENT_HELD', 1, 'completed'])
        ->and($replay['operation_id'])->toBe($first['operation_id'])
        ->and($first['data']['receipt'])->toMatchArray(['code' => 'DISBURSEMENT_HELD', 'request_id' => $key, 'revision' => 1,
            'amount' => ['currency' => 'RWF', 'amount' => $disbursement->amount]])
        ->and(fn () => DisbursementFixture::command($placer, $disbursement, 'hold', 0, $key, 'A different reason.'))
        ->toThrow(CommandRejection::class, 'IDEMPOTENCY_CONFLICT')
        ->and(CommandOperation::query()->where('command', 'disbursement.hold')->sole()->request_hash)
        ->toBe(hash('sha256', app(CanonicalJson::class)->encode(['target_type' => 'disbursement',
            'target_id' => $disbursement->id, 'input' => ['expected_revision' => 0, 'reason' => 'Checked against the funded campaign.']])));
    $detail = DisbursementFixture::detail($placer, $disbursement);
    expect([$detail['state'], $detail['hold']['reason'], $detail['hold']['placed_by']['actor'], $detail['automation']['waiting_for']])
        ->toBe(['on_hold', 'Checked against the funded campaign.', $placer->name, null])
        ->and($detail['allowed_actions'])->toBe(['disbursement.release_hold'])
        ->and($detail)->not->toHaveKeys(['maker', 'checker', 'approval_binding', 'step_up', 'viewer_is_maker', 'viewer_placed_hold']);
});

it('lets the staff member who placed a hold release it alone, with a reason', function (): void {
    ['disbursement' => $disbursement, 'campaign' => $campaign] = DisbursementFixture::funded();
    $placer = DisbursementFixture::staff(['approver']);
    DisbursementFixture::command($placer, $disbursement, 'hold', 0);
    $released = DisbursementFixture::command($placer, $disbursement, 'release_hold', 1, null, 'Business confirmed its account.');
    $detail = DisbursementFixture::detail($placer, $disbursement);
    expect($released['code'])->toBe('DISBURSEMENT_HOLD_RELEASED')
        ->and([$detail['state'], $detail['revision'], $detail['hold']])->toBe(['ready', 2, null])
        ->and(trailKinds($detail))->toBe(['hold_released', 'held'])
        ->and($detail['trail'][0])->toMatchArray(['actor' => $placer->name, 'reason' => 'Business confirmed its account.'])
        ->and($detail['allowed_actions'])->toBe(['disbursement.hold'])
        ->and(DisbursementIntent::query()->count())->toBe(0)
        ->and(DisbursementFixture::sources()->effects($campaign->campaignId))->toBe([]);
});

it('lets any other holder of the hold permission release a hold', function (): void {
    ['disbursement' => $disbursement] = DisbursementFixture::funded();
    $placer = DisbursementFixture::staff(['compliance']);
    $releaser = DisbursementFixture::staff(['treasury']);
    DisbursementFixture::command($placer, $disbursement, 'hold', 0);
    expect(DisbursementFixture::detail($releaser, $disbursement)['allowed_actions'])->toBe(['disbursement.release_hold'])
        ->and(DisbursementFixture::command($releaser, $disbursement, 'release_hold', 1)['code'])->toBe('DISBURSEMENT_HOLD_RELEASED')
        ->and(DisbursementFixture::detail($releaser, $disbursement)['state'])->toBe('ready');
});

it('keeps a held disbursement unpaid until its hold is released, then pays it on the next run', function (): void {
    ['disbursement' => $disbursement, 'campaign' => $campaign] = DisbursementFixture::funded();
    $placer = DisbursementFixture::staff(['compliance']);
    DisbursementFixture::command($placer, $disbursement, 'hold', 0);
    DisbursementFixture::release($campaign);
    expect(DisbursementFixture::detail($placer, $disbursement)['state'])->toBe('on_hold')
        ->and(DisbursementIntent::query()->count())->toBe(0);
    DisbursementFixture::command($placer, $disbursement, 'release_hold', 1);
    expect(DisbursementFixture::detail($placer, $disbursement)['automation']['waiting_for'])->toBe('next_run');

    expect(app(OpenFundedDisbursements::class)->handle())->toBe(['opened' => 0, 'known' => 1, 'queued' => 1, 'failed' => 0]);

    $detail = DisbursementFixture::detail($placer, $disbursement);
    expect([$detail['state'], $detail['revision']])->toBe(['dispatched', 4])
        ->and(trailKinds($detail))->toBe(['dispatched', 'auto_intent_recorded', 'hold_released', 'held'])
        ->and(DisbursementIntent::query()->sole()->revision)->toBe(2);
});

it('keeps superadmin to viewing only', function (): void {
    ['disbursement' => $disbursement] = DisbursementFixture::funded();
    $superadmin = DisbursementFixture::staff(['superadmin']);
    $detail = DisbursementFixture::detail($superadmin, $disbursement);
    expect([$detail['state'], $detail['allowed_actions']])->toBe(['ready', []])
        ->and(fn () => DisbursementFixture::command($superadmin, $disbursement, 'hold', 0))->toThrow(IdentityViolation::class, 'STAFF_PERMISSION_REQUIRED');
    DisbursementFixture::command(DisbursementFixture::staff(['compliance']), $disbursement, 'hold', 0);
    expect(DisbursementFixture::detail($superadmin, $disbursement)['allowed_actions'])->toBe([])
        ->and(fn () => DisbursementFixture::command($superadmin, $disbursement, 'release_hold', 1))->toThrow(IdentityViolation::class, 'STAFF_PERMISSION_REQUIRED');
});

it('refuses the retired maker/checker commands', function (string $command): void {
    ['disbursement' => $disbursement] = DisbursementFixture::funded();
    $staff = DisbursementFixture::staff(['treasury', 'approver']);
    expect(fn () => DisbursementFixture::command($staff, $disbursement, $command, 0))->toThrow(DisbursementViolation::class, 'DISBURSEMENT_COMMAND_INVALID')
        ->and(DisbursementFixture::detail($staff, $disbursement)['revision'])->toBe(0)
        ->and(CommandOperation::query()->count())->toBe(0);
})->with(['authorize', 'approve', 'reject']);

it('refuses hold and release once the intent is recorded', function (): void {
    ['disbursement' => $disbursement] = DisbursementFixture::paid();
    $compliance = DisbursementFixture::staff(['compliance']);
    $hold = DisbursementFixture::command($compliance, $disbursement, 'hold', 2);
    expect([$hold['code'], $hold['http_status']])->toBe(['DISBURSEMENT_IN_FLIGHT', 409])
        ->and(DisbursementFixture::command($compliance, $disbursement, 'release_hold', 2)['code'])->toBe('DISBURSEMENT_IN_FLIGHT')
        ->and(DisbursementFixture::detail($compliance, $disbursement)['allowed_actions'])->toBe([]);
});

it('replays a recorded hold but still rechecks current authority', function (): void {
    ['disbursement' => $disbursement] = DisbursementFixture::funded();
    $placer = DisbursementFixture::staff(['compliance']);
    $key = (string) Str::uuid();
    $held = DisbursementFixture::command($placer, $disbursement, 'hold', 0, $key);
    expect(DisbursementFixture::command($placer, $disbursement, 'hold', 0, $key)['operation_id'])->toBe($held['operation_id']);
    revoke($placer, ['analyst']);
    expect(fn () => DisbursementFixture::command($placer, $disbursement, 'hold', 0, $key))->toThrow(IdentityViolation::class, 'STAFF_PERMISSION_REQUIRED')
        ->and(fn () => DisbursementFixture::command($placer, $disbursement, 'release_hold', 1))->toThrow(IdentityViolation::class, 'STAFF_PERMISSION_REQUIRED')
        ->and(fn () => app(ManageDisbursements::class)->find($placer->id, 'disbursement.hold', $key))->toThrow(IdentityViolation::class, 'STAFF_PERMISSION_REQUIRED');
});

it('refuses a stale revision and an empty or control-character reason as recorded refusals', function (): void {
    ['disbursement' => $disbursement] = DisbursementFixture::funded();
    $staff = DisbursementFixture::staff(['treasury']);
    $stale = DisbursementFixture::command($staff, $disbursement, 'hold', 4);
    $empty = DisbursementFixture::command($staff, $disbursement, 'hold', 0, null, '   ');
    $control = DisbursementFixture::command($staff, $disbursement, 'hold', 0, null, "Held\u{0007}");
    $notHeld = DisbursementFixture::command($staff, $disbursement, 'release_hold', 0);
    expect([$stale['code'], $stale['revision']])->toBe(['VERSION_CONFLICT', 0])
        ->and([$empty['code'], $empty['http_status'], array_keys($empty['field_errors'])])->toBe(['VALIDATION_FAILED', 422, ['reason']])
        ->and($control['code'])->toBe('VALIDATION_FAILED')
        ->and([$notHeld['code'], $notHeld['http_status']])->toBe(['DISBURSEMENT_STATE_INVALID', 409])
        ->and(DisbursementFixture::detail($staff, $disbursement)['revision'])->toBe(0);
});

it('scopes unknown disbursements and lookups', function (): void {
    ['disbursement' => $disbursement] = DisbursementFixture::funded();
    $placer = DisbursementFixture::staff(['treasury']);
    $other = DisbursementFixture::staff(['treasury']);
    $key = (string) Str::uuid();
    DisbursementFixture::command($placer, $disbursement, 'hold', 0, $key);
    $manage = app(ManageDisbursements::class);
    expect($manage->find($placer->id, 'disbursement.hold', $key)['code'])->toBe('DISBURSEMENT_HELD')
        ->and(fn () => $manage->find($other->id, 'disbursement.hold', $key))->toThrow(CommandRejection::class, 'OPERATION_NOT_FOUND')
        ->and(fn () => $manage->find($placer->id, 'application.release', $key))->toThrow(CommandRejection::class, 'OPERATION_NOT_FOUND')
        ->and(fn () => $manage->find($placer->id, 'disbursement.approve', $key))->toThrow(CommandRejection::class, 'OPERATION_NOT_FOUND')
        ->and(fn () => $manage->command($placer->id, strtolower((string) Str::ulid()), 'hold', 0, 'Reason.', (string) Str::uuid()))
        ->toThrow(CommandRejection::class, 'DISBURSEMENT_NOT_FOUND')
        ->and($manage->page($placer->id, strtolower((string) Str::ulid()), null, 25)['refusal'])->toBe(['code' => 'DISBURSEMENT_NOT_FOUND', 'status' => 404]);
});
