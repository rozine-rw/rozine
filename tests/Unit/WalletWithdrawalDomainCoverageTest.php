<?php

declare(strict_types=1);

use App\Application\Wallet\WithdrawalInstruction;
use App\Domain\Operations\CommandRejection;
use App\Domain\Wallet\JournalEntry;
use App\Domain\Wallet\WalletMoney;
use App\Domain\Wallet\WalletViolation;
use App\Domain\Wallet\WithdrawalTerms;
use App\Infrastructure\Wallet\UnavailableWithdrawalProvider;

it('refuses a withdrawal that would not cover the provider charge, within the RWF limits', function (): void {
    $terms = new WithdrawalTerms(WalletMoney::of('5000'));

    expect($terms->boundsError(WalletMoney::of('5000'), WalletMoney::of('100000')))->toBe('Withdraw more than the RWF 5000 provider charge.')
        ->and($terms->boundsError(WalletMoney::of('2000'), WalletMoney::of('100000')))->toBe('Withdraw more than the RWF 5000 provider charge.')
        ->and($terms->boundsError(WalletMoney::of('5001'), WalletMoney::of('100000')))->toBeNull()
        ->and($terms->received(WalletMoney::of('5001'))->amount())->toBe('1');
});

it('refuses a withdrawal posting of an unknown kind', function (): void {
    expect(fn () => JournalEntry::withdrawal('withdrawal_bonus', WalletMoney::of('1000')))->toThrow(WalletViolation::class, 'WALLET_POSTING_KIND_INVALID');
});

it('pays out, charges, verifies and signs nothing where no withdrawal provider is available', function (): void {
    $provider = new UnavailableWithdrawalProvider;
    $instruction = new WithdrawalInstruction('withdrawal', 'operation', 'syw_reference', 'method', '1000', 'RWF', 'production');

    expect($provider->name())->toBe('unavailable')
        ->and($provider->idempotentSends())->toBeFalse()
        ->and(fn () => $provider->charge())->toThrow(LogicException::class, 'WALLET_PROVIDER_UNAVAILABLE')
        ->and(fn () => $provider->send($instruction))->toThrow(LogicException::class, 'WALLET_PROVIDER_UNAVAILABLE')
        ->and(fn () => $provider->verify(['provider' => 'synthetic']))->toThrow(CommandRejection::class, 'PROVIDER_EVENT_UNVERIFIED')
        ->and(fn () => $provider->sign(['provider' => 'synthetic']))->toThrow(LogicException::class, 'WALLET_PROVIDER_UNAVAILABLE');
});
