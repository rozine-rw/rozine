<?php

declare(strict_types=1);

namespace App\Infrastructure\Wallet;

use App\Application\Wallet\Contracts\PayoutAccountVerifier;
use App\Domain\Wallet\PayoutAccount;
use LogicException;

/**
 * Bound everywhere the synthetic verifier is not allowed. No live name-match or verification-debit
 * provider exists yet, so no account can be linked.
 */
final class UnavailablePayoutAccountVerifier implements PayoutAccountVerifier
{
    public function source(): ?string
    {
        return null;
    }

    public function refusal(PayoutAccount $account, string $holderName): ?string
    {
        throw new LogicException('PAYOUT_VERIFIER_UNAVAILABLE');
    }
}
