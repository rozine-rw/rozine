<?php

declare(strict_types=1);

namespace App\Application\Wallet\Contracts;

use App\Application\Wallet\LockedWallet;
use App\Application\Wallet\PostingSource;
use App\Application\Wallet\ReturnedCash;
use App\Domain\Wallet\WalletMoney;

/**
 * Read-only proof that the original reservation principal returned to its wallet.
 * For requireReturned, the caller owns a READ COMMITTED transaction and locks Business/campaign/Primary
 * evidence before acquiring affected wallets in Party order. Source ownership is
 * checked before locking the supplied wallet; movements are then read afresh.
 * Accepts only hold + release, or hold + commit + refund, with exact original cash.
 * Never writes or replays a posting, creates a wallet, or acquires campaign locks.
 * Cash evidence alone does not authorize cancellation or make ordinals reusable.
 */
interface PrimaryReturnedCash
{
    public function requireReturned(LockedWallet $wallet, WalletMoney $amount, PostingSource $source): ReturnedCash;

    /** SELECT-only inspection of the original hold and full held release with Party, native and crypto bindings.
     * Does not require/acquire wallet or campaign locks and never writes retirement/posting evidence.
     * It authenticates retained cash only; the caller separately authenticates every reservation revision.
     *
     * @return array{hold_entry_id: string, hold_sha256: string, return_entry_id: string, return_sha256: string, wallet_id: string}
     */
    public function inspectHeldReturn(string $partyId, WalletMoney $amount, PostingSource $source): array;
}
