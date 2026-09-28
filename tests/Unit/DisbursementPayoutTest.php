<?php

declare(strict_types=1);

use App\Domain\Disbursement\DisbursementViolation;
use App\Domain\Disbursement\PayoutOutcome;
use App\Domain\Disbursement\Reconciliation;

it('follows the MC-08 payout outcome matrix', function (string $current, string $event, string $disposition, string $state, bool $blocks): void {
    $outcome = PayoutOutcome::transition($current, $event);
    expect([$outcome->disposition, $outcome->state, $outcome->blocks()])->toBe([$disposition, $state, $blocks]);
})->with([
    'pending → pending repeats' => ['pending', 'pending', 'duplicate', 'pending', false],
    'pending → unknown is recorded, never final' => ['pending', 'unknown', 'applied', 'unknown', false],
    'unknown → pending is recorded' => ['unknown', 'pending', 'applied', 'pending', false],
    'pending → succeeded is applied' => ['pending', 'succeeded', 'applied', 'succeeded', false],
    'unknown → succeeded is applied' => ['unknown', 'succeeded', 'applied', 'succeeded', false],
    'pending → failed is applied' => ['pending', 'failed', 'applied', 'failed', false],
    'unknown → failed is applied' => ['unknown', 'failed', 'applied', 'failed', false],
    'succeeded → succeeded never applies twice' => ['succeeded', 'succeeded', 'duplicate', 'succeeded', false],
    'succeeded → failed opens an exception, never a reversal' => ['succeeded', 'failed', 'after_final', 'succeeded', true],
    'succeeded → unknown is stale evidence that blocks nothing' => ['succeeded', 'unknown', 'stale', 'succeeded', false],
    'succeeded → pending is stale evidence' => ['succeeded', 'pending', 'stale', 'succeeded', false],
    'failed → succeeded is a conflict that blocks the refund' => ['failed', 'succeeded', 'conflict', 'failed', true],
    'failed → failed repeats' => ['failed', 'failed', 'duplicate', 'failed', false],
    'failed → pending is stale evidence' => ['failed', 'pending', 'stale', 'failed', false],
]);

it('classifies an event identity replay, a key collision and an unmatched observation', function (): void {
    $same = PayoutOutcome::observe('pending', 'succeeded', str_repeat('a', 64), str_repeat('a', 64), []);
    $collision = PayoutOutcome::observe('pending', 'succeeded', str_repeat('a', 64), str_repeat('b', 64), []);
    $unmatched = PayoutOutcome::observe('pending', 'succeeded', null, str_repeat('b', 64), ['amount']);
    $applied = PayoutOutcome::observe('pending', 'succeeded', null, str_repeat('b', 64), []);
    expect([$same->disposition, $same->state, $same->blocks()])->toBe(['duplicate', 'pending', false])
        ->and([$collision->disposition, $collision->blocks()])->toBe(['key_conflict', true])
        ->and([$unmatched->disposition, $unmatched->state, $unmatched->blocks()])->toBe(['unverifiable', 'pending', true])
        ->and([$applied->disposition, $applied->state])->toBe(['applied', 'succeeded']);
});

it('rejects states outside the payout contract', function (): void {
    expect(fn () => PayoutOutcome::transition('sent', 'succeeded'))->toThrow(DisbursementViolation::class, 'PAYOUT_OUTCOME_STATE_INVALID')
        ->and(fn () => PayoutOutcome::transition('pending', 'timeout'))->toThrow(DisbursementViolation::class, 'PAYOUT_OUTCOME_STATE_INVALID')
        ->and(fn () => PayoutOutcome::observe('pending', 'timeout', null, 'x', []))->toThrow(DisbursementViolation::class, 'PAYOUT_OUTCOME_STATE_INVALID')
        ->and(PayoutOutcome::isFinal('unknown'))->toBeFalse();
});

/** @return array<string, string> */
function payoutIntent(): array
{
    return ['operation_id' => '01k0000000000000000000000a', 'provider' => 'synthetic', 'provider_reference' => 'ref-1',
        'environment' => 'testing', 'currency' => 'RWF', 'amount' => '10700000', 'destination_sha256' => str_repeat('d', 64)];
}

it('matches an observation to the durable intent with RWF 0 tolerance', function (): void {
    $observed = [...payoutIntent(), 'state' => 'succeeded', 'effective_at' => '2026-01-31T10:00:00Z'];
    expect(Reconciliation::mismatches(payoutIntent(), $observed))->toBe([])
        ->and(Reconciliation::mismatches(payoutIntent(), [...$observed, 'amount' => '10699999']))->toBe(['amount'])
        ->and(Reconciliation::mismatches(payoutIntent(), [...$observed, 'amount' => '10700001']))->toBe(['amount'])
        ->and(Reconciliation::mismatches(payoutIntent(), [...$observed, 'currency' => 'USD', 'environment' => 'production']))->toBe(['environment', 'currency'])
        ->and(Reconciliation::mismatches(payoutIntent(), [...$observed, 'destination_sha256' => null]))->toBe(['destination_sha256'])
        ->and(Reconciliation::mismatches(payoutIntent(), [...$observed, 'effective_at' => null]))->toBe(['effective_at'])
        ->and(Reconciliation::mismatches(payoutIntent(), [...$observed, 'effective_at' => 'yesterday']))->toBe(['effective_at'])
        ->and(Reconciliation::mismatches(payoutIntent(), [...$observed, 'state' => 'unknown', 'effective_at' => null]))->toBe([])
        ->and(Reconciliation::mismatches(payoutIntent(), [...$observed, 'state' => 'paid']))->toBe(['state'])
        ->and(fn () => Reconciliation::mismatches([...payoutIntent(), 'provider_reference' => ''], $observed))->toThrow(DisbursementViolation::class, 'PAYOUT_INTENT_INCOMPLETE');
});

it('decides a reconciliation only from an unblocked applied final observation', function (array $observations, string $decision, array $causes, bool $terminal): void {
    $result = Reconciliation::decide(array_values(array_map(fn (array $observation): array => ['state' => (string) $observation['state'],
        'disposition' => (string) $observation['disposition']], $observations)));
    expect([$result->decision, $result->causes, $result->terminal()])->toBe([$decision, $causes, $terminal]);
})->with([
    'nothing observed' => [[], 'open', [], false],
    'pending then unknown' => [[['state' => 'pending', 'disposition' => 'applied'], ['state' => 'unknown', 'disposition' => 'applied']], 'open', [], false],
    'one matching success' => [[['state' => 'succeeded', 'disposition' => 'applied']], 'matched_success', [], true],
    'success then a lagging unknown' => [[['state' => 'succeeded', 'disposition' => 'applied'], ['state' => 'unknown', 'disposition' => 'stale']], 'matched_success', [], true],
    'success with a duplicate' => [[['state' => 'succeeded', 'disposition' => 'applied'], ['state' => 'succeeded', 'disposition' => 'duplicate']], 'matched_success', [], true],
    'one matching failure' => [[['state' => 'failed', 'disposition' => 'applied']], 'matched_failure', [], true],
    'failure then late success' => [[['state' => 'failed', 'disposition' => 'applied'], ['state' => 'failed', 'disposition' => 'conflict']], 'exception', ['conflict'], false],
    'success then late failure' => [[['state' => 'succeeded', 'disposition' => 'applied'], ['state' => 'succeeded', 'disposition' => 'after_final']], 'exception', ['after_final'], false],
    'key collision' => [[['state' => 'pending', 'disposition' => 'key_conflict'], ['state' => 'succeeded', 'disposition' => 'applied']], 'exception', ['key_conflict'], false],
    'unverifiable then matching' => [[['state' => 'pending', 'disposition' => 'unverifiable'], ['state' => 'succeeded', 'disposition' => 'applied'], ['state' => 'succeeded', 'disposition' => 'unverifiable']], 'exception', ['unverifiable'], false],
]);

it('refuses a malformed observation', function (): void {
    expect(fn () => Reconciliation::decide([['state' => 'paid', 'disposition' => 'applied']]))->toThrow(DisbursementViolation::class, 'PAYOUT_OBSERVATION_INVALID')
        ->and(fn () => Reconciliation::decide([['state' => 'pending', 'disposition' => 'ignored']]))->toThrow(DisbursementViolation::class, 'PAYOUT_OBSERVATION_INVALID');
});
