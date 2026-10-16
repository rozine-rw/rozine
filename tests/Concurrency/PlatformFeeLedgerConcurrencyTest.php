<?php

declare(strict_types=1);

use App\Application\Disbursement\Contracts\PlatformFeeLedger;
use App\Domain\Disbursement\PlatformFee;
use App\Domain\Wallet\WalletViolation;
use Illuminate\Support\Str;

it('posts a platform fee only inside the caller\'s issue transaction', function (): void {
    $id = fn (): string => strtolower((string) Str::ulid());

    expect(fn () => app(PlatformFeeLedger::class)->post($id(), $id(), $id(), $id(), PlatformFee::of('3000000'), null))
        ->toThrow(WalletViolation::class, 'WALLET_POSTING_TRANSACTION_REQUIRED');
});
