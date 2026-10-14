<?php

declare(strict_types=1);

namespace App\Domain\Identity;

/**
 * The in-app notices a Party receives (BRS v2.0 NTF-R01), each from one recorded event. A kind
 * names the facts it carries; the client words them from its catalogs, and the notice opens the
 * screen that already holds the record. A verification result, a refunded raise, a sent withdrawal
 * and a Business's expired raise are also e-mailed. A Business's notices belong to its entity Party.
 */
enum PartyNoticeKind: string
{
    case VerificationApproved = 'verification.approved';
    case VerificationRejected = 'verification.rejected';
    case DepositReceived = 'wallet.deposit_received';
    case WithdrawalSent = 'wallet.withdrawal_sent';
    case WithdrawalReturned = 'wallet.withdrawal_returned';
    case RaiseRefunded = 'primary.refunded';
    case PasswordChanged = 'security.password_changed';
    case BusinessDepositReceived = 'business.deposit_received';
    case BusinessRaiseExpired = 'business.raise_expired';

    public function mailed(): bool
    {
        return in_array($this, [self::VerificationApproved, self::VerificationRejected, self::RaiseRefunded, self::WithdrawalSent,
            self::BusinessRaiseExpired], true);
    }
}
