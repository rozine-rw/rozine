<?php

declare(strict_types=1);

use App\Domain\Wallet\DepositPolicyTerms;
use App\Domain\Wallet\WalletMoney;
use App\Domain\Wallet\WalletViolation;

it('accepts only canonical positive whole-franc input of at most twelve digits', function (string $input, ?string $parsed): void {
    expect(WalletMoney::fromInput($input)?->amount())->toBe($parsed);
})->with([
    ['5000', '5000'], ['1', '1'], ['999999999999', '999999999999'],
    ['0', null], ['05000', null], ['5000.00', null], ['5e3', null], ['-5000', null], ['1000000000000', null], ['', null], [' 5000', null], ["5000\n", null],
]);

it('keeps recorded values exact and never negative', function (): void {
    $large = WalletMoney::of('99999999999999999999');
    expect($large->amount())->toBe('99999999999999999999')
        ->and(WalletMoney::of('0')->isZero())->toBeTrue()
        ->and(WalletMoney::of('7000')->minus(WalletMoney::of('2500'))->money())->toBe(['currency' => 'RWF', 'amount' => '4500'])
        ->and(WalletMoney::zero()->plus(WalletMoney::of('3'))->compareTo(WalletMoney::of('3')))->toBe(0)
        ->and(fn () => WalletMoney::of('1')->minus(WalletMoney::of('2')))->toThrow(WalletViolation::class, 'WALLET_MONEY_NEGATIVE')
        ->and(fn () => WalletMoney::of('1.5'))->toThrow(WalletViolation::class, 'WALLET_MONEY_INVALID')
        ->and(fn () => WalletMoney::of('007'))->toThrow(WalletViolation::class, 'WALLET_MONEY_INVALID');
});

it('bounds deposits by an explicit versioned policy and credits the amount less its fee', function (): void {
    $free = new DepositPolicyTerms('synthetic-deposit-policy-0', true, WalletMoney::zero(), WalletMoney::of('1000'), WalletMoney::of('1000000'));
    $open = new DepositPolicyTerms('synthetic-open', true, WalletMoney::zero(), null, null);
    $charged = new DepositPolicyTerms('synthetic-fee', true, WalletMoney::of('150'), WalletMoney::of('1000'), null);
    expect($free->boundsError(WalletMoney::of('999')))->toBe('Deposit at least RWF 1000.')
        ->and($free->boundsError(WalletMoney::of('1000')))->toBeNull()
        ->and($free->boundsError(WalletMoney::of('1000000')))->toBeNull()
        ->and($free->boundsError(WalletMoney::of('1000001')))->toBe('Deposit at most RWF 1000000.')
        ->and($free->credited(WalletMoney::of('5000'))->amount())->toBe('5000')
        ->and($open->boundsError(WalletMoney::of('1')))->toBeNull()
        ->and($open->boundsError(WalletMoney::of('999999999999')))->toBeNull()
        ->and($charged->credited(WalletMoney::of('5000'))->amount())->toBe('4850')
        ->and((new DepositPolicyTerms('synthetic-edge', true, WalletMoney::of('150'), null, null))->boundsError(WalletMoney::of('150')))
        ->toBe('Deposit more than the RWF 150 fee.');
});
