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

it('folds an automatic payment from ready straight to queued with no maker or checker', function (): void {
    $initial = DisbursementState::initial();
    expect([$initial->state, $initial->revision, $initial->makerUserId, $initial->awaitsPayment()])->toBe(['ready', 0, null, true]);

    $queued = DisbursementState::fold(disbursementEvents('auto_intent_recorded'));
    expect([$queued->state, $queued->revision, $queued->makerUserId, $queued->checkerUserId, $queued->inFlight(), $queued->awaitsPayment()])
        ->toBe(['queued', 1, null, null, true, false]);

    $succeeded = DisbursementState::fold(disbursementEvents('auto_intent_recorded', 'dispatched', 'succeeded'));
    expect([$succeeded->state, $succeeded->revision, $succeeded->makerUserId, $succeeded->checkerUserId])->toBe(['succeeded', 3, null, null]);

    $released = DisbursementState::fold(disbursementEvents('held:9', 'hold_released:9', 'auto_intent_recorded'));
    expect([$released->state, $released->revision])->toBe(['queued', 3]);

    expect(DisbursementState::fold(disbursementEvents('auto_intent_recorded', 'failed_closing'))->state)->toBe('failed_closing')
        ->and(DisbursementState::fold(disbursementEvents('auto_intent_recorded', 'dispatched', 'failed_closing'))->state)->toBe('failed_closing')
        ->and(DisbursementState::fold(disbursementEvents('failed_closing'))->state)->toBe('failed_closing')
        ->and(DisbursementState::fold(disbursementEvents('held:9', 'hold_released:8', 'failed_closing'))->state)->toBe('failed_closing');
});

it('keeps folding legacy maker/checker history into state, revision, maker, checker and hold placer', function (): void {
    $awaiting = DisbursementState::fold(disbursementEvents('authorized:7'));
    expect([$awaiting->state, $awaiting->revision, $awaiting->makerUserId, $awaiting->awaitsPayment()])->toBe(['awaiting_second_approver', 1, 7, true]);

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

    $automatic = DisbursementState::fold(disbursementEvents('authorized:7', 'auto_intent_recorded'));
    expect([$automatic->state, $automatic->makerUserId, $automatic->checkerUserId])->toBe(['queued', null, null]);

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
    'legacy approve before authorize' => [disbursementEvents('intent_recorded:8'), 'DISBURSEMENT_TRANSITION_INVALID'],
    'dispatch before any intent' => [disbursementEvents('dispatched'), 'DISBURSEMENT_TRANSITION_INVALID'],
    'legacy dispatch before approval' => [disbursementEvents('authorized:7', 'dispatched'), 'DISBURSEMENT_TRANSITION_INVALID'],
    'success before dispatch' => [disbursementEvents('auto_intent_recorded', 'succeeded'), 'DISBURSEMENT_TRANSITION_INVALID'],
    'automatic intent with an actor' => [disbursementEvents('auto_intent_recorded:7'), 'DISBURSEMENT_TRANSITION_INVALID'],
    'automatic intent while held' => [disbursementEvents('held:9', 'auto_intent_recorded'), 'DISBURSEMENT_TRANSITION_INVALID'],
    'automatic intent twice' => [disbursementEvents('auto_intent_recorded', 'auto_intent_recorded'), 'DISBURSEMENT_TRANSITION_INVALID'],
    'staff failed closing from ready' => [disbursementEvents('failed_closing:7'), 'DISBURSEMENT_TRANSITION_INVALID'],
    'failed closing while held' => [disbursementEvents('held:9', 'failed_closing'), 'DISBURSEMENT_TRANSITION_INVALID'],
    'hold after queued' => [disbursementEvents('auto_intent_recorded', 'held:9'), 'DISBURSEMENT_TRANSITION_INVALID'],
    'release without hold' => [disbursementEvents('hold_released:9'), 'DISBURSEMENT_TRANSITION_INVALID'],
    'reject from ready' => [disbursementEvents('rejected:9'), 'DISBURSEMENT_TRANSITION_INVALID'],
    'anything after success' => [disbursementEvents('auto_intent_recorded', 'dispatched', 'succeeded', 'failed_closing'), 'DISBURSEMENT_TRANSITION_INVALID'],
    'unknown kind' => [disbursementEvents('paid:1'), 'DISBURSEMENT_TRANSITION_INVALID'],
    'staff event without actor' => [disbursementEvents('held'), 'DISBURSEMENT_EVENT_ACTOR_REQUIRED'],
    'legacy staff event without actor' => [disbursementEvents('authorized'), 'DISBURSEMENT_EVENT_ACTOR_REQUIRED'],
    'staff event with a zero actor' => [disbursementEvents('held:0'), 'DISBURSEMENT_EVENT_ACTOR_REQUIRED'],
]);

it('lets one authorised staff member hold and release, including the one who placed the hold', function (): void {
    $ready = DisbursementState::initial();
    expect($ready->refusal('hold', 9))->toBeNull()
        ->and($ready->refusal('release_hold', 9))->toBe(['code' => 'DISBURSEMENT_STATE_INVALID', 'status' => 409]);

    $held = DisbursementState::fold(disbursementEvents('held:9'));
    expect($held->refusal('release_hold', 9))->toBeNull()
        ->and($held->refusal('release_hold', 8))->toBeNull()
        ->and($held->refusal('hold', 8))->toBe(['code' => 'DISBURSEMENT_STATE_INVALID', 'status' => 409]);

    $legacy = DisbursementState::fold(disbursementEvents('authorized:7'));
    expect($legacy->refusal('hold', 7))->toBeNull()
        ->and(DisbursementState::fold(disbursementEvents('authorized:7', 'held:7'))->refusal('release_hold', 7))->toBeNull();
});

it('accepts only the remaining staff commands', function (string $command): void {
    expect(fn () => DisbursementState::initial()->refusal($command, 8))->toThrow(DisbursementViolation::class, 'DISBURSEMENT_COMMAND_INVALID');
})->with(['authorize', 'approve', 'reject', 'pay']);

it('refuses hold and release once the money is in flight', function (string $state, array $events): void {
    $folded = DisbursementState::fold(disbursementLog($events));
    expect($folded->state)->toBe($state);
    foreach (['hold', 'release_hold'] as $command) {
        expect($folded->refusal($command, 99))->toBe(['code' => 'DISBURSEMENT_IN_FLIGHT', 'status' => 409]);
    }
})->with([
    'queued' => ['queued', disbursementEvents('auto_intent_recorded')],
    'dispatched' => ['dispatched', disbursementEvents('auto_intent_recorded', 'dispatched')],
    'succeeded' => ['succeeded', disbursementEvents('auto_intent_recorded', 'dispatched', 'succeeded')],
    'failed closing' => ['failed_closing', disbursementEvents('failed_closing')],
    'legacy queued' => ['queued', disbursementEvents('authorized:7', 'intent_recorded:8')],
    'legacy failed closing' => ['failed_closing', disbursementEvents('authorized:7', 'failed_closing')],
]);

it('offers requery only on a dispatched payout', function (): void {
    expect(DisbursementState::fold(disbursementEvents('auto_intent_recorded', 'dispatched'))->allows('requery', 7))->toBeTrue()
        ->and(DisbursementState::fold(disbursementEvents('authorized:7', 'intent_recorded:8', 'dispatched'))->allows('requery', 7))->toBeTrue()
        ->and(DisbursementState::fold(disbursementEvents('auto_intent_recorded'))->refusal('requery', 7))->toBe(['code' => 'DISBURSEMENT_STATE_INVALID', 'status' => 409])
        ->and(DisbursementState::fold(disbursementEvents('auto_intent_recorded', 'dispatched', 'succeeded'))->refusal('requery', 7))->toBe(['code' => 'DISBURSEMENT_STATE_INVALID', 'status' => 409])
        ->and(DisbursementState::initial()->refusal('requery', 7))->toBe(['code' => 'DISBURSEMENT_STATE_INVALID', 'status' => 409])
        ->and(DisbursementState::initial()->allows('hold', 7))->toBeTrue();
});

it('throws a command refusal that carries the current revision', function (): void {
    $queued = DisbursementState::fold(disbursementEvents('held:9', 'hold_released:9', 'auto_intent_recorded'));
    try {
        $queued->assertAllows('hold', 9);
        $this->fail('A hold on money in flight was allowed.');
    } catch (CommandRejection $rejection) {
        expect([$rejection->reason, $rejection->status, $rejection->revision])->toBe(['DISBURSEMENT_IN_FLIGHT', 409, 3]);
    }
    DisbursementState::fold(disbursementEvents('held:9'))->assertAllows('release_hold', 9);
    expect(fn () => $queued->refusal('pay', 8))->toThrow(DisbursementViolation::class, 'DISBURSEMENT_COMMAND_INVALID');
});
