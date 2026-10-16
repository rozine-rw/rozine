<?php

declare(strict_types=1);

namespace App\Domain\Wallet;

use RuntimeException;

/** A wallet rule was broken by trusted input: an integrity failure, never an Investor refusal. */
final class WalletViolation extends RuntimeException
{
    public function __construct(public readonly string $reason)
    {
        parent::__construct($reason);
    }
}
