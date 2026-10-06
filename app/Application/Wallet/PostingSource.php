<?php

declare(strict_types=1);

namespace App\Application\Wallet;

use App\Domain\Wallet\PrimaryPosting;
use App\Domain\Wallet\WalletViolation;

/**
 * What a primary posting is for: an allow-listed source type (`primary_reservation` or
 * `primary_commitment`), its id, and the operation that originated it. Every later movement of the
 * same source must name the same originating operation, so an independent command key can never
 * release, commit or refund another command's hold.
 */
final readonly class PostingSource
{
    public function __construct(public string $type, public string $id, public string $originOperationId)
    {
        if (! in_array($type, PrimaryPosting::SOURCES, true) || preg_match('/^[0-9a-hjkmnp-tv-z]{26}$/D', $id) !== 1
            || preg_match('/^[0-9a-hjkmnp-tv-z]{26}$/D', $originOperationId) !== 1) {
            throw new WalletViolation('WALLET_POSTING_SOURCE_INVALID');
        }
    }
}
