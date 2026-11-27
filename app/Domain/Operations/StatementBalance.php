<?php

declare(strict_types=1);

namespace App\Domain\Operations;

use Brick\Math\BigInteger;
use DateTimeImmutable;
use InvalidArgumentException;

/** One authoritative, immutable cutoff statement balance and its opaque evidence reference. */
final readonly class StatementBalance
{
    public function __construct(public string $accountId, public string $amount, public DateTimeImmutable $asOf, public string $reference)
    {
        if (! in_array($accountId, ['deposits', 'withdrawals'], true) || trim($reference) === '' || strlen($reference) > 255
            || (string) BigInteger::of($amount) !== $amount) {
            throw new InvalidArgumentException('RECONCILIATION_STATEMENT_INVALID');
        }
    }
}
