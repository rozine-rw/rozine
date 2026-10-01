<?php

declare(strict_types=1);

namespace App\Application\Wallet\Contracts;

use App\Application\Wallet\LockedWallet;
use App\Application\Wallet\PostingSource;
use App\Application\Wallet\ReturnedCash;
use App\Domain\Wallet\WalletMoney;

/**
 * Read-only proof that the original reservation principal returned to its wallet.
 * The caller owns a READ COMMITTED transaction and locks Business/campaign/Primary
 * evidence before acquiring affected wallets in Party order. Source ownership is
 * checked before locking the supplied wallet; movements are then read afresh.
 * Accepts only hold + release, or hold + commit + refund, with exact original cash.
 * Never writes or replays a posting, creates a wallet, or acquires campaign locks.
 * Cash evidence alone does not authorize cancellation or make ordinals reusable.
 */
interface PrimaryReturnedCash
{
    public function requireReturned(LockedWallet $wallet, WalletMoney $amount, PostingSource $source): ReturnedCash;
}
