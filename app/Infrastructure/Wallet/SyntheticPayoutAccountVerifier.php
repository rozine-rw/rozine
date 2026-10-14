<?php

declare(strict_types=1);

namespace App\Infrastructure\Wallet;

use App\Application\Wallet\Contracts\PayoutAccountVerifier;
use App\Domain\Wallet\PayoutAccount;

/**
 * The offline verifier for local, testing and UAT only. It contacts no provider and debits nothing:
 * every account is taken as the holder's own, except a number ending in 0000, which stands for an
 * account in someone else's name so testers can see the refusal.
 */
final class SyntheticPayoutAccountVerifier implements PayoutAccountVerifier
{
    public function source(): string
    {
        return 'synthetic';
    }

    public function refusal(PayoutAccount $account, string $holderName): ?string
    {
        return str_ends_with($account->reference, '0000')
            ? 'This account is not in your verified name. Link an account you hold yourself.'
            : null;
    }
}
