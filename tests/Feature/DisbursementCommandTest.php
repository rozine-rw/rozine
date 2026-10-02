<?php

declare(strict_types=1);

use App\Application\Disbursement\ManageDisbursements;
use App\Application\Identity\ConfigureStaffAccess;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Domain\Identity\IdentityViolation;
use App\Domain\Operations\CommandRejection;
use App\Models\CommandOperation;
use App\Models\DisbursementClosing;
use App\Models\DisbursementIntent;
use App\Models\DisbursementStepUpProof;
use App\Models\User;
use Illuminate\Support\Str;
use Tests\Support\DisbursementFixture;

/*
 * Maker/checker commands through the operation journal (C3 v2 §2e, #96 5871859618): segregation,
 * combined roles, lapsed authority, the bound step-up proof, fail-closed inputs and replay.
 */

/** @param list<string> $roles */
function revoke(User $user, array $roles): void
{
    app(ConfigureStaffAccess::class)->handle($user->id, true, 'Role change.', (string) Str::uuid(), $roles);
}

it('records authorize once, replays the same body and refuses a changed one', function (): void {
    ['disbursement' => $disbursement] = DisbursementFixture::funded();
    $maker = DisbursementFixture::staff(['treasury']);
    $key = (string) Str::uuid();
    $first = DisbursementFixture::command($maker, $disbursement, 'authorize', 0, null, $key);
    $replay = DisbursementFixture::command($maker, $disbursement, 'authorize', 0, null, $key);
    expect([$first['code'], $first['revision'], $first['status']])->toBe(['DISBURSEMENT_AUTHORIZED', 1, 'completed'])
        ->and($replay['operation_id'])->toBe($first['operation_id'])
        ->and($first['data']['receipt'])->toMatchArray(['code' => 'DISBURSEMENT_AUTHORIZED', 'request_id' => $key, 'revision' => 1,
            'amount' => ['currency' => 'RWF', 'amount' => $disbursement->amount]])
        ->and(fn () => DisbursementFixture::command($maker, $disbursement, 'authorize', 0, null, $key, 'A different reason.'))
        ->toThrow(CommandRejection::class, 'IDEMPOTENCY_CONFLICT')
        ->and(CommandOperation::query()->where('command', 'disbursement.authorize')->count())->toBe(1);
    $detail = DisbursementFixture::detail($maker, $disbursement);
    expect([$detail['state'], $detail['viewer_is_maker'], $detail['precheck']['state'], $detail['maker']['reason']])
        ->toBe(['awaiting_second_approver', true, 'passed', 'Checked against the funded campaign.'])
        ->and($detail['allowed_actions'])->toBe(['disbursement.hold']);
});

it('never lets one user be maker and checker, even holding both roles', function (): void {
    ['disbursement' => $disbursement] = DisbursementFixture::funded();
    $both = DisbursementFixture::staff(['treasury', 'approver']);
    DisbursementFixture::command($both, $disbursement, 'authorize', 0);
    $detail = DisbursementFixture::detail($both, $disbursement);
    expect($detail['allowed_actions'])->not->toContain('disbursement.approve', 'disbursement.reject')
        ->and($detail['step_up_allowed'])->toBeFalse()
        ->and(fn () => app(ManageDisbursements::class)->stepUp($both->id, $disbursement->id, 1, $detail['approval_binding']['intent_digest'], DisbursementFixture::code($both)))
        ->toThrow(CommandRejection::class, 'SELF_APPROVAL_FORBIDDEN');
    $approve = DisbursementFixture::command($both, $disbursement, 'approve', 1, 'not-a-proof');
    $reject = DisbursementFixture::command($both, $disbursement, 'reject', 1);
    expect([$approve['code'], $approve['http_status'], $reject['code']])->toBe(['SELF_APPROVAL_FORBIDDEN', 403, 'SELF_APPROVAL_FORBIDDEN'])
        ->and(DisbursementIntent::query()->count())->toBe(0);
});

it('never lets the staff member who placed a hold release it, even holding approve', function (): void {
    ['disbursement' => $disbursement] = DisbursementFixture::funded();
    $maker = DisbursementFixture::staff(['treasury']);
    $placer = DisbursementFixture::staff(['approver']);
    DisbursementFixture::command($maker, $disbursement, 'authorize', 0);
    expect(DisbursementFixture::command($placer, $disbursement, 'hold', 1)['code'])->toBe('DISBURSEMENT_HELD');
    $detail = DisbursementFixture::detail($placer, $disbursement);
    expect([$detail['state'], $detail['viewer_placed_hold'], $detail['hold']['reason']])->toBe(['on_hold', true, 'Checked against the funded campaign.'])
        ->and($detail['allowed_actions'])->toBe([])
        ->and(DisbursementFixture::command($placer, $disbursement, 'release_hold', 2)['code'])->toBe('SELF_APPROVAL_FORBIDDEN')
        ->and(DisbursementFixture::command($maker, $disbursement, 'release_hold', 2)['code'])->toBe('STAFF_PERMISSION_REQUIRED');
})->throws(IdentityViolation::class, 'STAFF_PERMISSION_REQUIRED');

it('returns a released hold to the state it interrupted without approving anything', function (): void {
    ['disbursement' => $disbursement] = DisbursementFixture::funded();
    $maker = DisbursementFixture::staff(['treasury']);
    $placer = DisbursementFixture::staff(['compliance']);
    $releaser = DisbursementFixture::staff(['approver']);
    DisbursementFixture::command($maker, $disbursement, 'authorize', 0);
    DisbursementFixture::command($placer, $disbursement, 'hold', 1);
    $released = DisbursementFixture::command($releaser, $disbursement, 'release_hold', 2);
    $detail = DisbursementFixture::detail($releaser, $disbursement);
    expect($released['code'])->toBe('DISBURSEMENT_HOLD_RELEASED')
        ->and([$detail['state'], $detail['revision'], $detail['maker']['actor']])->toBe(['awaiting_second_approver', 3, $maker->name])
        ->and(DisbursementIntent::query()->count())->toBe(0);
});

it('voids only the maker authorization on reject', function (): void {
    ['disbursement' => $disbursement, 'campaign' => $campaign] = DisbursementFixture::funded();
    $maker = DisbursementFixture::staff(['treasury']);
    $checker = DisbursementFixture::staff(['approver']);
    DisbursementFixture::command($maker, $disbursement, 'authorize', 0);
    expect(DisbursementFixture::command($checker, $disbursement, 'reject', 1)['code'])->toBe('DISBURSEMENT_AUTHORIZATION_REJECTED');
    $detail = DisbursementFixture::detail($maker, $disbursement);
    expect([$detail['state'], $detail['maker'], $detail['allowed_actions']])->toBe(['ready', null, ['disbursement.authorize', 'disbursement.hold']])
        ->and(array_map(fn (array $entry): string => $entry['action']['code'], $detail['trail']))->toBe(['rejected', 'authorized'])
        ->and(DisbursementFixture::sources()->effects($campaign->campaignId))->toBe([]);
});

it('keeps superadmin to viewing only', function (): void {
    ['disbursement' => $disbursement] = DisbursementFixture::funded();
    $superadmin = DisbursementFixture::staff(['superadmin']);
    $detail = DisbursementFixture::detail($superadmin, $disbursement);
    expect([$detail['state'], $detail['allowed_actions']])->toBe(['ready', []])
        ->and(fn () => DisbursementFixture::command($superadmin, $disbursement, 'authorize', 0))->toThrow(IdentityViolation::class, 'STAFF_PERMISSION_REQUIRED')
        ->and(fn () => DisbursementFixture::command($superadmin, $disbursement, 'hold', 0))->toThrow(IdentityViolation::class, 'STAFF_PERMISSION_REQUIRED');
});

it('refuses a lapsed maker authorization and lets a fresh maker replace it', function (): void {
    ['disbursement' => $disbursement] = DisbursementFixture::funded();
    $maker = DisbursementFixture::staff(['treasury']);
    $checker = DisbursementFixture::staff(['approver']);
    $replacement = DisbursementFixture::staff(['treasury']);
    DisbursementFixture::command($maker, $disbursement, 'authorize', 0);
    $proof = DisbursementFixture::stepUp($checker, $disbursement)['proof'];
    revoke($maker, ['compliance']);
    $void = DisbursementFixture::command($checker, $disbursement, 'approve', 1, $proof);
    expect([$void['code'], $void['http_status']])->toBe(['MAKER_AUTHORIZATION_VOID', 409])
        ->and(DisbursementStepUpProof::query()->sole()->consumed_at)->toBeNull()
        ->and(DisbursementFixture::detail($replacement, $disbursement)['allowed_actions'])->toContain('disbursement.authorize')
        ->and(DisbursementFixture::command($replacement, $disbursement, 'authorize', 1)['code'])->toBe('DISBURSEMENT_AUTHORIZED');
    // One accepted code mints one proof: the checker's next step-up needs a later code.
    expect(fn () => DisbursementFixture::stepUp($checker, $disbursement))->toThrow(CommandRejection::class, 'STEP_UP_CODE_INVALID');
    $this->travel(11)->minutes();
    expect(DisbursementFixture::command($checker, $disbursement, 'approve', 2, DisbursementFixture::stepUp($checker, $disbursement)['proof'])['code'])
        ->toBe('DISBURSEMENT_INTENT_RECORDED')
        ->and(DisbursementIntent::query()->sole()->maker_user_id)->toBe($replacement->id);
});

it('refuses a failed authorize-time recheck and journals it without closing', function (): void {
    ['disbursement' => $disbursement, 'campaign' => $campaign] = DisbursementFixture::funded();
    $maker = DisbursementFixture::staff(['treasury']);
    DisbursementFixture::sources()->scriptRecheck($campaign->campaignId, 'failed', ['mandate', 'dscr']);
    $key = (string) Str::uuid();
    $refused = DisbursementFixture::command($maker, $disbursement, 'authorize', 0, null, $key);
    expect([$refused['code'], $refused['status'], $refused['http_status'], $refused['data']['causes']])->toBe(['DISBURSEMENT_PRECHECK_FAILED', 'rejected', 409, ['dscr', 'mandate']])
        ->and(DisbursementFixture::command($maker, $disbursement, 'authorize', 0, null, $key)['operation_id'])->toBe($refused['operation_id'])
        ->and(DisbursementClosing::query()->count())->toBe(0)
        ->and(DisbursementFixture::detail($maker, $disbursement)['state'])->toBe('ready');
    DisbursementFixture::sources()->scriptRecheck($campaign->campaignId, 'unavailable', ['conditions_precedent']);
    expect(DisbursementFixture::command($maker, $disbursement, 'authorize', 0)['code'])->toBe('POLICY_INPUT_REQUIRED');
});

it('fails closed without a verified destination or an available connection source, and refuses connected staff', function (string $case, string $code): void {
    ['disbursement' => $disbursement, 'campaign' => $campaign] = DisbursementFixture::funded();
    $maker = DisbursementFixture::staff(['treasury']);
    match ($case) {
        'revoked', 'expired' => DisbursementFixture::sources()->setDestination($campaign->businessId, $case),
        'connected' => DisbursementFixture::sources()->setConnection($maker->id, 'connected'),
        default => DisbursementFixture::sources()->setConnection(null, 'unavailable'),
    };
    $refused = DisbursementFixture::command($maker, $disbursement, 'authorize', 0);
    expect([$refused['code'], $refused['status']])->toBe([$code, 'rejected']);
})->with([
    'revoked destination' => ['revoked', 'POLICY_INPUT_REQUIRED'],
    'expired destination' => ['expired', 'POLICY_INPUT_REQUIRED'],
    'connected maker' => ['connected', 'ACTION_FORBIDDEN'],
    'connection source unavailable' => ['unavailable', 'POLICY_INPUT_REQUIRED'],
]);

it('takes the deterministic failed closing on a failed approve-time recheck, and refuses an unavailable one without spending the proof', function (): void {
    ['disbursement' => $disbursement, 'campaign' => $campaign] = DisbursementFixture::funded();
    $maker = DisbursementFixture::staff(['treasury']);
    $checker = DisbursementFixture::staff(['approver']);
    DisbursementFixture::command($maker, $disbursement, 'authorize', 0);
    DisbursementFixture::sources()->scriptRecheck($campaign->campaignId, 'unavailable', ['conditions_precedent']);
    $proof = DisbursementFixture::stepUp($checker, $disbursement)['proof'];
    expect(DisbursementFixture::command($checker, $disbursement, 'approve', 1, $proof)['code'])->toBe('POLICY_INPUT_REQUIRED')
        ->and(DisbursementStepUpProof::query()->sole()->consumed_at)->toBeNull();
    DisbursementFixture::sources()->scriptRecheck($campaign->campaignId, 'failed', ['restriction']);
    $closed = DisbursementFixture::command($checker, $disbursement, 'approve', 1, $proof);
    $closing = DisbursementClosing::query()->sole();
    expect([$closed['code'], $closed['status'], $closed['data']['causes']])->toBe(['CAMPAIGN_FAILED_CLOSING', 'completed', ['restriction']])
        ->and([$closing->kind, $closing->cause, $closing->operation_id])->toBe(['failed_closing', 'approve_recheck', $closed['operation_id']])
        ->and(DisbursementFixture::sources()->effects($campaign->campaignId)[0])->toMatchArray(['kind' => 'failed_closing', 'cause' => 'approve_recheck'])
        ->and(DisbursementFixture::sources()->effects($campaign->campaignId)[0]['refunds'][0]['fee'])->toBe('0')
        ->and(DisbursementIntent::query()->count())->toBe(0);
    $detail = DisbursementFixture::detail($checker, $disbursement);
    expect([$detail['state'], $detail['precheck']['state'], $detail['precheck']['causes'], $detail['checker']['actor'], $detail['refund']['total']['amount']])
        ->toBe(['failed_closing', 'failed', ['restriction'], $checker->name, $disbursement->amount])
        ->and($detail['allowed_actions'])->toBe([]);
});

it('requires a fresh bound step-up proof on approve and consumes it once', function (string $case, string $code): void {
    ['disbursement' => $disbursement] = DisbursementFixture::funded();
    $maker = DisbursementFixture::staff(['treasury']);
    $checker = DisbursementFixture::staff(['approver']);
    $other = DisbursementFixture::staff(['approver']);
    $placer = DisbursementFixture::staff(['compliance']);
    DisbursementFixture::command($maker, $disbursement, 'authorize', 0);
    $proof = match ($case) {
        'missing' => null,
        'foreign' => DisbursementFixture::stepUp($other, $disbursement)['proof'],
        'unknown' => Str::random(64),
        default => DisbursementFixture::stepUp($checker, $disbursement)['proof'],
    };
    $revision = 1;
    if ($case === 'stale revision') {
        DisbursementFixture::command($placer, $disbursement, 'hold', 1);
        DisbursementFixture::command($other, $disbursement, 'release_hold', 2);
        $revision = 3;
    }
    if ($case === 'expired') {
        $this->travel(6)->minutes();
    }
    if ($case === 'reused') {
        DisbursementStepUpProof::query()->update(['consumed_at' => now(), 'consumed_operation_id' => strtolower((string) Str::ulid())]);
    }
    $refused = DisbursementFixture::command($checker, $disbursement, 'approve', $revision, $proof);
    expect([$refused['code'], $refused['http_status']])->toBe([$code, 403])->and(DisbursementIntent::query()->count())->toBe(0);
})->with([
    'missing' => ['missing', 'STEP_UP_REQUIRED'],
    'foreign' => ['foreign', 'STEP_UP_INVALID'],
    'unknown' => ['unknown', 'STEP_UP_INVALID'],
    'stale revision' => ['stale revision', 'STEP_UP_INVALID'],
    'expired' => ['expired', 'STEP_UP_EXPIRED'],
    'reused' => ['reused', 'STEP_UP_INVALID'],
]);

it('replays a recorded approval without the consumed proof but still rechecks current authority', function (): void {
    ['disbursement' => $disbursement] = DisbursementFixture::funded();
    $maker = DisbursementFixture::staff(['treasury']);
    $checker = DisbursementFixture::staff(['approver']);
    DisbursementFixture::command($maker, $disbursement, 'authorize', 0);
    $key = (string) Str::uuid();
    $approved = DisbursementFixture::command($checker, $disbursement, 'approve', 1, DisbursementFixture::stepUp($checker, $disbursement)['proof'], $key);
    $replay = DisbursementFixture::command($checker, $disbursement, 'approve', 1, null, $key);
    expect($approved['code'])->toBe('DISBURSEMENT_INTENT_RECORDED')->and($replay['operation_id'])->toBe($approved['operation_id'])
        ->and(DisbursementIntent::query()->count())->toBe(1)
        ->and(CommandOperation::query()->where('command', 'disbursement.approve')->sole()->request_hash)
        ->toBe(hash('sha256', app(CanonicalJson::class)->encode(['target_type' => 'disbursement',
            'target_id' => $disbursement->id, 'input' => ['expected_revision' => 1, 'reason' => 'Checked against the funded campaign.']])));
    revoke($checker, ['analyst']);
    expect(fn () => DisbursementFixture::command($checker, $disbursement, 'approve', 1, null, $key))->toThrow(IdentityViolation::class, 'STAFF_PERMISSION_REQUIRED')
        ->and(fn () => app(ManageDisbursements::class)->find($checker->id, 'disbursement.approve', $key))->toThrow(IdentityViolation::class, 'STAFF_PERMISSION_REQUIRED');
});

it('refuses an approval whose checker lost authority after the step-up', function (): void {
    ['disbursement' => $disbursement] = DisbursementFixture::funded();
    $maker = DisbursementFixture::staff(['treasury']);
    $checker = DisbursementFixture::staff(['approver']);
    DisbursementFixture::command($maker, $disbursement, 'authorize', 0);
    $proof = DisbursementFixture::stepUp($checker, $disbursement)['proof'];
    revoke($checker, ['compliance']);
    expect(fn () => DisbursementFixture::command($checker, $disbursement, 'approve', 1, $proof))->toThrow(IdentityViolation::class, 'STAFF_PERMISSION_REQUIRED')
        ->and(DisbursementStepUpProof::query()->sole()->consumed_at)->toBeNull();
});

it('refuses approval when the destination was rotated after authorization, and a stale destination at step-up', function (): void {
    ['disbursement' => $disbursement, 'campaign' => $campaign] = DisbursementFixture::funded();
    $maker = DisbursementFixture::staff(['treasury']);
    $checker = DisbursementFixture::staff(['approver']);
    DisbursementFixture::command($maker, $disbursement, 'authorize', 0);
    $proof = DisbursementFixture::stepUp($checker, $disbursement)['proof'];
    DisbursementFixture::sources()->setDestination($campaign->businessId, 'rotated');
    expect(DisbursementFixture::command($checker, $disbursement, 'approve', 1, $proof)['code'])->toBe('DIGEST_STALE');
    DisbursementFixture::sources()->setDestination($campaign->businessId, 'revoked');
    expect(DisbursementFixture::command($checker, $disbursement, 'approve', 1, $proof)['code'])->toBe('POLICY_INPUT_REQUIRED');
});

it('refuses hold, reject and release once the intent is recorded', function (): void {
    ['disbursement' => $disbursement, 'maker' => $maker, 'checker' => $checker] = DisbursementFixture::approved();
    $compliance = DisbursementFixture::staff(['compliance']);
    expect(DisbursementFixture::command($compliance, $disbursement, 'hold', 3)['code'])->toBe('DISBURSEMENT_IN_FLIGHT')
        ->and(DisbursementFixture::command($checker, $disbursement, 'reject', 3)['code'])->toBe('DISBURSEMENT_IN_FLIGHT')
        ->and(DisbursementFixture::command($maker, $disbursement, 'authorize', 3)['code'])->toBe('DISBURSEMENT_IN_FLIGHT');
});

it('refuses a stale revision and an empty or control-character reason as recorded refusals', function (): void {
    ['disbursement' => $disbursement] = DisbursementFixture::funded();
    $maker = DisbursementFixture::staff(['treasury']);
    $stale = DisbursementFixture::command($maker, $disbursement, 'authorize', 4);
    $empty = DisbursementFixture::command($maker, $disbursement, 'authorize', 0, null, null, '   ');
    $control = DisbursementFixture::command($maker, $disbursement, 'hold', 0, null, null, "Held\u{0007}");
    expect([$stale['code'], $stale['revision']])->toBe(['VERSION_CONFLICT', 0])
        ->and([$empty['code'], $empty['http_status'], array_keys($empty['field_errors'])])->toBe(['VALIDATION_FAILED', 422, ['reason']])
        ->and($control['code'])->toBe('VALIDATION_FAILED')
        ->and(DisbursementFixture::detail($maker, $disbursement)['revision'])->toBe(0);
});

it('scopes unknown disbursements and lookups', function (): void {
    ['disbursement' => $disbursement] = DisbursementFixture::funded();
    $maker = DisbursementFixture::staff(['treasury']);
    $other = DisbursementFixture::staff(['treasury']);
    $key = (string) Str::uuid();
    DisbursementFixture::command($maker, $disbursement, 'authorize', 0, null, $key);
    $manage = app(ManageDisbursements::class);
    expect($manage->find($maker->id, 'disbursement.authorize', $key)['code'])->toBe('DISBURSEMENT_AUTHORIZED')
        ->and(fn () => $manage->find($other->id, 'disbursement.authorize', $key))->toThrow(CommandRejection::class, 'OPERATION_NOT_FOUND')
        ->and(fn () => $manage->find($maker->id, 'application.release', $key))->toThrow(CommandRejection::class, 'OPERATION_NOT_FOUND')
        ->and(fn () => $manage->command($maker->id, strtolower((string) Str::ulid()), 'authorize', 0, 'Reason.', (string) Str::uuid()))
        ->toThrow(CommandRejection::class, 'DISBURSEMENT_NOT_FOUND')
        ->and($manage->page($maker->id, strtolower((string) Str::ulid()), null, 25)['refusal'])->toBe(['code' => 'DISBURSEMENT_NOT_FOUND', 'status' => 404]);
});
