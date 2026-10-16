<?php

declare(strict_types=1);

namespace App\Application\Wallet;

use DateTimeImmutable;

/** Exact retained cash evidence; it is neither a new posting nor a funding/issue decision. */
final readonly class CommittedCash
{
    public function __construct(
        public string $holdEntryId,
        public string $commitEntryId,
        public string $walletId,
        public string $reservationId,
        public string $originOperationId,
        public string $amount,
        public DateTimeImmutable $committedAt,
    ) {}
}
