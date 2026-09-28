<?php

declare(strict_types=1);

namespace App\Application\Wallet\Contracts;

use App\Application\Wallet\CommittedCash;
use App\Application\Wallet\LockedWallet;
use App\Application\Wallet\PostingSource;
use App\Domain\Wallet\WalletMoney;

/**
 * Read-only cash prerequisite for S3-C funding and settlement. The caller owns the transaction,
 * first locks Business/campaign/Primary evidence, then every affected wallet in Party order.
 * Re-locks and validates the supplied wallet; never acquires Business or campaign locks.
 * Requires the exact original reservation hold and commit, with no other source movement.
 * Only primary_hold + primary_commit is accepted. In particular primary_release, primary_refund,
 * primary_issue (S3-D settlement) and any unknown movement exclude the source from funding.
 * Returns relational ledger evidence without writing, replaying a posting or creating a wallet.
 * This does not prove eligibility, full campaign funding, receipt disclosure or permission to pay.
 */
interface PrimaryCommittedCash
{
    public function requireCommitted(LockedWallet $wallet, WalletMoney $amount, PostingSource $source): CommittedCash;
}
