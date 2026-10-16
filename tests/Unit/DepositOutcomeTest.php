<?php

declare(strict_types=1);

use App\Domain\Wallet\DepositOutcome;
use App\Domain\Wallet\WalletViolation;

it('follows the MC-08 provider outcome matrix', function (string $current, string $event, string $disposition, string $state, bool $credits): void {
    $outcome = DepositOutcome::transition($current, $event);
    expect([$outcome->disposition, $outcome->state, $outcome->credits()])->toBe([$disposition, $state, $credits]);
})->with([
    'pending → pending repeats' => ['pending', 'pending', 'duplicate', 'pending', false],
    'pending → unknown is recorded, never final' => ['pending', 'unknown', 'applied', 'unknown', false],
    'unknown → pending is recorded' => ['unknown', 'pending', 'applied', 'pending', false],
    'unknown → unknown repeats' => ['unknown', 'unknown', 'duplicate', 'unknown', false],
    'pending → succeeded credits' => ['pending', 'succeeded', 'applied', 'succeeded', true],
    'unknown → succeeded credits' => ['unknown', 'succeeded', 'applied', 'succeeded', true],
    'pending → failed is final with no credit' => ['pending', 'failed', 'applied', 'failed', false],
    'unknown → failed is final with no credit' => ['unknown', 'failed', 'applied', 'failed', false],
    'succeeded → succeeded never credits twice' => ['succeeded', 'succeeded', 'duplicate', 'succeeded', false],
    'succeeded → failed opens reconciliation' => ['succeeded', 'failed', 'after_final', 'succeeded', false],
    'succeeded → pending is after final' => ['succeeded', 'pending', 'after_final', 'succeeded', false],
    'succeeded → unknown is after final' => ['succeeded', 'unknown', 'after_final', 'succeeded', false],
    'failed → succeeded is a conflict' => ['failed', 'succeeded', 'conflict', 'failed', false],
    'failed → failed repeats' => ['failed', 'failed', 'duplicate', 'failed', false],
    'failed → unknown is after final' => ['failed', 'unknown', 'after_final', 'failed', false],
]);

it('rejects states outside the provider contract', function (): void {
    expect(fn () => DepositOutcome::transition('redirected', 'succeeded'))->toThrow(WalletViolation::class, 'DEPOSIT_OUTCOME_STATE_INVALID')
        ->and(fn () => DepositOutcome::transition('pending', 'timeout'))->toThrow(WalletViolation::class, 'DEPOSIT_OUTCOME_STATE_INVALID')
        ->and(DepositOutcome::isFinal('unknown'))->toBeFalse();
});
