<?php

declare(strict_types=1);

use App\Domain\Disbursement\DisbursementState;
use App\Domain\Disbursement\DisbursementViolation;
use App\Domain\Operations\CommandRejection;

/** @return list<array{kind: string, actor_user_id: int|null}> */
function disbursementEvents(string ...$pairs): array
{
    return array_values(array_map(function (string $pair): array {
        [$kind, $actor] = array_pad(explode(':', $pair), 2, null);

        return ['kind' => (string) $kind, 'actor_user_id' => $actor === null ? null : (int) $actor];
    }, $pairs));
}

/**
 * A dataset's event log, re-typed for the fold.
 *
 * @param  array<mixed>  $events
 * @return list<array{kind: string, actor_user_id: int|null}>
 */
function disbursementLog(array $events): array
{
    return array_values(array_map(fn (mixed $event): array => ['kind' => (string) (is_array($event) ? $event['kind'] : ''),
        'actor_user_id' => is_array($event) && is_int($event['actor_user_id']) ? $event['actor_user_id'] : null], $events));
}

it('folds the event log into state, revision, maker, checker and hold placer', function (): void {
    $initial = DisbursementState::initial();
    expect([$initial->state, $initial->revision, $initial->makerUserId])->toBe(['ready', 0, null]);

    $awaiting = DisbursementState::fold(disbursementEvents('authorized:7'));
    expect([$awaiting->state, $awaiting->revision, $awaiting->makerUserId])->toBe(['awaiting_second_approver', 1, 7]);

    $held = DisbursementState::fold(disbursementEvents('authorized:7', 'held:9'));
    expect([$held->state, $held->heldFrom, $held->holdPlacerUserId, $held->makerUserId])->toBe(['on_hold', 'awaiting_second_approver', 9, 7]);

    $released = DisbursementState::fold(disbursementEvents('authorized:7', 'held:9', 'hold_released:8'));
    expect([$released->state, $released->revision, $released->makerUserId, $released->holdPlacerUserId])->toBe(['awaiting_second_approver', 3, 7, null]);

    $rejected = DisbursementState::fold(disbursementEvents('authorized:7', 'rejected:8'));
    expect([$rejected->state, $rejected->makerUserId])->toBe(['ready', null]);

    $queued = DisbursementState::fold(disbursementEvents('authorized:7', 'intent_recorded:8'));
    expect([$queued->state, $queued->makerUserId, $queued->checkerUserId, $queued->inFlight()])->toBe(['queued', 7, 8, true]);

    $succeeded = DisbursementState::fold(disbursementEvents('authorized:7', 'intent_recorded:8', 'dispatched', 'succeeded'));
    expect([$succeeded->state, $succeeded->revision, $succeeded->checkerUserId])->toBe(['succeeded', 4, 8]);

    expect(DisbursementState::fold(disbursementEvents('authorized:7', 'failed_closing'))->state)->toBe('failed_closing')
        ->and(DisbursementState::fold(disbursementEvents('authorized:7', 'intent_recorded:8', 'failed_closing'))->state)->toBe('failed_closing')
        ->and(DisbursementState::fold(disbursementEvents('authorized:7', 'intent_recorded:8', 'dispatched', 'failed_closing'))->state)->toBe('failed_closing')
        ->and(DisbursementState::fold(disbursementEvents('held:9', 'hold_released:8'))->state)->toBe('ready')
        ->and(DisbursementState::fold(disbursementEvents('authorized:7', 'authorized:10'))->makerUserId)->toBe(10)
        ->and($awaiting->inFlight())->toBeFalse();
});

it('rejects an event the state cannot accept, and a staff event without its actor', function (array $events, string $reason): void {
    expect(fn () => DisbursementState::fold(disbursementLog($events)))->toThrow(DisbursementViolation::class, $reason);
})->with([
    'approve before authorize' => [disbursementEvents('intent_recorded:8'), 'DISBURSEMENT_TRANSITION_INVALID'],
    'dispatch before approval' => [disbursementEvents('authorized:7', 'dispatched'), 'DISBURSEMENT_TRANSITION_INVALID'],
    'success before dispatch' => [disbursementEvents('authorized:7', 'intent_recorded:8', 'succeeded'), 'DISBURSEMENT_TRANSITION_INVALID'],
    'failed closing from ready' => [disbursementEvents('failed_closing'), 'DISBURSEMENT_TRANSITION_INVALID'],
    'hold after queued' => [disbursementEvents('authorized:7', 'intent_recorded:8', 'held:9'), 'DISBURSEMENT_TRANSITION_INVALID'],
    'release without hold' => [disbursementEvents('hold_released:9'), 'DISBURSEMENT_TRANSITION_INVALID'],
    'reject from ready' => [disbursementEvents('rejected:9'), 'DISBURSEMENT_TRANSITION_INVALID'],
    'anything after success' => [disbursementEvents('authorized:7', 'intent_recorded:8', 'dispatched', 'succeeded', 'failed_closing'), 'DISBURSEMENT_TRANSITION_INVALID'],
    'unknown kind' => [disbursementEvents('paid:1'), 'DISBURSEMENT_TRANSITION_INVALID'],
    'staff event without actor' => [disbursementEvents('authorized'), 'DISBURSEMENT_EVENT_ACTOR_REQUIRED'],
    'staff event with a zero actor' => [disbursementEvents('held:0'), 'DISBURSEMENT_EVENT_ACTOR_REQUIRED'],
]);

it('guards segregation of duties before state', function (): void {
    $awaiting = DisbursementState::fold(disbursementEvents('authorized:7'));
    expect($awaiting->refusal('approve', 7))->toBe(['code' => 'SELF_APPROVAL_FORBIDDEN', 'status' => 403])
        ->and($awaiting->refusal('reject', 7))->toBe(['code' => 'SELF_APPROVAL_FORBIDDEN', 'status' => 403])
        ->and($awaiting->refusal('approve', 8))->toBeNull()
        ->and($awaiting->refusal('reject', 8))->toBeNull()
        ->and($awaiting->refusal('hold', 7))->toBeNull();

    $held = DisbursementState::fold(disbursementEvents('held:9'));
    expect($held->refusal('release_hold', 9))->toBe(['code' => 'SELF_APPROVAL_FORBIDDEN', 'status' => 403])
        ->and($held->refusal('release_hold', 8))->toBeNull()
        ->and($held->refusal('hold', 8))->toBe(['code' => 'DISBURSEMENT_STATE_INVALID', 'status' => 409])
        ->and($held->refusal('authorize', 8))->toBe(['code' => 'DISBURSEMENT_STATE_INVALID', 'status' => 409]);
});

it('voids a lapsed maker authorization for approval and lets a fresh maker replace it', function (): void {
    $awaiting = DisbursementState::fold(disbursementEvents('authorized:7'));
    expect($awaiting->refusal('approve', 8, false))->toBe(['code' => 'MAKER_AUTHORIZATION_VOID', 'status' => 409])
        ->and($awaiting->refusal('authorize', 8, false))->toBeNull()
        ->and($awaiting->refusal('authorize', 8))->toBe(['code' => 'DISBURSEMENT_STATE_INVALID', 'status' => 409])
        ->and($awaiting->refusal('reject', 8, false))->toBeNull()
        ->and($awaiting->refusal('approve', 7, false))->toBe(['code' => 'SELF_APPROVAL_FORBIDDEN', 'status' => 403]);
});

it('refuses hold, reject, release and re-authorization once the money is in flight', function (string $state, array $events): void {
    $folded = DisbursementState::fold(disbursementLog($events));
    expect($folded->state)->toBe($state);
    foreach (['authorize', 'approve', 'reject', 'hold', 'release_hold'] as $command) {
        expect($folded->refusal($command, 99))->toBe(['code' => 'DISBURSEMENT_IN_FLIGHT', 'status' => 409]);
    }
})->with([
    'queued' => ['queued', disbursementEvents('authorized:7', 'intent_recorded:8')],
    'dispatched' => ['dispatched', disbursementEvents('authorized:7', 'intent_recorded:8', 'dispatched')],
    'succeeded' => ['succeeded', disbursementEvents('authorized:7', 'intent_recorded:8', 'dispatched', 'succeeded')],
    'failed closing' => ['failed_closing', disbursementEvents('authorized:7', 'failed_closing')],
]);

it('offers requery only on a dispatched payout', function (): void {
    expect(DisbursementState::fold(disbursementEvents('authorized:7', 'intent_recorded:8', 'dispatched'))->allows('requery', 7))->toBeTrue()
        ->and(DisbursementState::fold(disbursementEvents('authorized:7', 'intent_recorded:8'))->refusal('requery', 7))->toBe(['code' => 'DISBURSEMENT_STATE_INVALID', 'status' => 409])
        ->and(DisbursementState::fold(disbursementEvents('authorized:7', 'intent_recorded:8', 'dispatched', 'succeeded'))->refusal('requery', 7))->toBe(['code' => 'DISBURSEMENT_STATE_INVALID', 'status' => 409])
        ->and(DisbursementState::initial()->refusal('requery', 7))->toBe(['code' => 'DISBURSEMENT_STATE_INVALID', 'status' => 409])
        ->and(DisbursementState::initial()->refusal('approve', 7))->toBe(['code' => 'DISBURSEMENT_STATE_INVALID', 'status' => 409])
        ->and(DisbursementState::initial()->allows('authorize', 7))->toBeTrue();
});

it('throws a command refusal that carries the current revision', function (): void {
    $awaiting = DisbursementState::fold(disbursementEvents('authorized:7'));
    try {
        $awaiting->assertAllows('approve', 7);
        $this->fail('Self-approval was allowed.');
    } catch (CommandRejection $rejection) {
        expect([$rejection->reason, $rejection->status, $rejection->revision])->toBe(['SELF_APPROVAL_FORBIDDEN', 403, 1]);
    }
    $awaiting->assertAllows('approve', 8);
    expect(fn () => $awaiting->refusal('pay', 8))->toThrow(DisbursementViolation::class, 'DISBURSEMENT_COMMAND_INVALID');
});
