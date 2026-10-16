<?php

declare(strict_types=1);

namespace App\Application\Wallet;

use App\Domain\Wallet\WalletViolation;

/**
 * Why a terminal posting happened, kept beside the source's immutable originating operation. An
 * issue names the disbursement closing that reconciled the payout; it never replaces the
 * commitment's own originating operation.
 */
final readonly class PostingCause
{
    public const array TYPES = ['disbursement_closing'];

    public function __construct(public string $type, public string $id)
    {
        if (! in_array($type, self::TYPES, true) || preg_match('/^[0-9a-hjkmnp-tv-z]{26}$/D', $id) !== 1) {
            throw new WalletViolation('WALLET_POSTING_CAUSE_INVALID');
        }
    }
}
