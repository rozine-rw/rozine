<?php

declare(strict_types=1);

namespace App\Application\Wallet\Contracts;

use App\Domain\Wallet\PayoutAccount;

/**
 * Confirms that an account is held in the Investor's own verified name before it can be linked
 * (BRS v2.0 KYC-R02; Investor Terms "Payout accounts and verification"). Only the synthetic
 * verifier exists; no live name-match or verification-debit provider has been contracted yet.
 */
interface PayoutAccountVerifier
{
    /** The verification source recorded on the linked account, or null when nothing can verify one. */
    public function source(): ?string;

    /** Why this account cannot be linked for this holder, or null when it is theirs. */
    public function refusal(PayoutAccount $account, string $holderName): ?string;
}
