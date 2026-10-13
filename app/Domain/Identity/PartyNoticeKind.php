<?php

declare(strict_types=1);

namespace App\Domain\Identity;

/**
 * The in-app notices a Party receives (BRS v2.0 NTF-R01), each from one recorded event. A kind
 * names the facts it carries; the client words them from its catalogs, and the notice opens the
 * screen that already holds the record. A verification result and a refunded raise are also e-mailed.
 */
enum PartyNoticeKind: string
{
    case VerificationApproved = 'verification.approved';
    case VerificationRejected = 'verification.rejected';
    case DepositReceived = 'wallet.deposit_received';
    case RaiseRefunded = 'primary.refunded';

    public function mailed(): bool
    {
        return $this !== self::DepositReceived;
    }
}
