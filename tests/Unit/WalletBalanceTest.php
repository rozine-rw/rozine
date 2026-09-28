<?php

declare(strict_types=1);

use App\Domain\Wallet\AccountRestriction;
use App\Domain\Wallet\WalletBalance;
use App\Domain\Wallet\WalletMoney;
use App\Domain\Wallet\WalletViolation;

it('totals each bucket once and keeps pending deposits outside the total', function (): void {
    $balance = new WalletBalance(WalletMoney::of('5000'), WalletMoney::of('700'), WalletMoney::of('300'), WalletMoney::of('9000'));
    expect($balance->total()->amount())->toBe('6000')
        ->and($balance->pendingDeposits->amount())->toBe('9000')
        ->and(WalletBalance::bucket(WalletMoney::of('8000'), WalletMoney::of('3000'))->amount())->toBe('5000')
        ->and(fn () => WalletBalance::bucket(WalletMoney::of('1'), WalletMoney::of('2')))->toThrow(WalletViolation::class, 'WALLET_MONEY_NEGATIVE');
});

it('reads a restriction by its recorded scope: the 11.4 hold never blocks deposits, an order that covers them does', function (): void {
    expect((new AccountRestriction('high_risk_hold', ['withdrawals', 'primary_commitments', 'secondary_trading']))->blocksDeposit())->toBeFalse()
        ->and((new AccountRestriction('external_order', ['withdrawals']))->blocksDeposit())->toBeFalse()
        ->and((new AccountRestriction('external_order', ['deposits', 'withdrawals']))->blocksDeposit())->toBeTrue();
});
