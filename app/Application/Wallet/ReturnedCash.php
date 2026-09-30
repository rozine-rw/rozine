<?php

declare(strict_types=1);

namespace App\Application\Wallet;

use DateTimeImmutable;

/** Exact retained cash evidence; it is neither a cancellation nor an inventory decision. */
final readonly class ReturnedCash
{
    public function __construct(
        public string $holdEntryId,
        public ?string $commitEntryId,
        public string $returnEntryId,
        public string $returnKind,
        public string $walletId,
        public string $reservationId,
        public string $originOperationId,
        public string $amount,
        public DateTimeImmutable $returnedAt,
    ) {}
}
