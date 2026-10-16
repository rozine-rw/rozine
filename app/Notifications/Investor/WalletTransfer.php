<?php

declare(strict_types=1);

namespace App\Notifications\Investor;

/** Which way money moved between an Investor's wallet and their bank or mobile-money account. */
enum WalletTransfer: string
{
    case Deposit = 'deposit';
    case Withdrawal = 'withdrawal';
}
