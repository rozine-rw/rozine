<?php

declare(strict_types=1);

namespace App\Application\Wallet\Contracts;

use App\Application\Wallet\LockedWallet;
use App\Application\Wallet\PostingCause;
use App\Application\Wallet\PostingReceipt;
use App\Application\Wallet\PostingSource;
use App\Domain\Wallet\WalletMoney;

/**
 * Primary purchase postings for S3-C. Each call posts ONE balanced immutable journal entry inside
 * the CALLER's open database transaction; it never begins, commits or retries a transaction, and
 * it makes no provider call. Outside a transaction it refuses `WALLET_POSTING_TRANSACTION_REQUIRED`.
 *
 * Lock order (agreed on #96 5868828540): campaign → reservation → investor wallet → ledger rows.
 * The deposit path locks only wallet → intent/event and never a campaign, so the two cannot form a
 * cycle. A multi-investor close locks reservations in stable id order, then calls `lockForParty`
 * for each wallet in stable Party id order, before any posting.
 *
 * Source binding: a hold opens the lifecycle for its (source type, source id) under the source's
 * originating operation. Commit and release follow that hold, for exactly its amount, on the same
 * wallet and originating operation, and end it exactly once; a refund follows the commit the same
 * way, once. Anything else refuses `WALLET_POSTING_STATE_INVALID` (nothing to follow, or already
 * ended) or `WALLET_POSTING_CONFLICT` (another wallet, amount or originating operation).
 *
 * Same-source retries: repeating a (kind, source) returns the ORIGINAL receipt, with `replayed`
 * true, only when wallet, amount, currency (always RWF) and movement all match exactly; any
 * difference refuses `WALLET_POSTING_CONFLICT` and never posts again.
 *
 * Buckets never go negative: a hold refuses `INSUFFICIENT_AVAILABLE_FUNDS` (422) when available is
 * short, and PostgreSQL re-checks every Investor bucket when the entry commits.
 */
interface WalletPostings
{
    /** Locks the Party's wallet FOR UPDATE, creating the one wallet per Party on first use. */
    public function lockForParty(string $partyId): LockedWallet;

    /** available → held. */
    public function hold(LockedWallet $wallet, WalletMoney $amount, PostingSource $source): PostingReceipt;

    /** held → committed, for exactly the held amount. */
    public function commit(LockedWallet $wallet, WalletMoney $amount, PostingSource $source): PostingReceipt;

    /** held → available (release, expiry or cancel before commit), for exactly the held amount. */
    public function release(LockedWallet $wallet, WalletMoney $amount, PostingSource $source): PostingReceipt;

    /** committed → available (pre-funding cancellation or unfunded expiry), for exactly the committed amount. */
    public function refund(LockedWallet $wallet, WalletMoney $amount, PostingSource $source): PostingReceipt;

    /**
     * committed → the system `disbursement_settlement` account, for exactly the committed amount of
     * a `primary_commitment` source, on a verified and reconciled disbursement success. It keeps the
     * commitment's originating operation and records the issuing closing as its cause. Issue and
     * refund end a commitment once between them: after either, the other refuses
     * `WALLET_POSTING_STATE_INVALID`. A retry with another cause refuses `WALLET_POSTING_CONFLICT`.
     */
    public function issue(LockedWallet $wallet, WalletMoney $amount, PostingSource $source, PostingCause $cause): PostingReceipt;
}
