<?php

declare(strict_types=1);

namespace App\Application\Wallet;

/**
 * The immutable journal entry a primary posting recorded. `replayed` is true when an identical
 * retry of the same (kind, source) returned the original posting instead of a new one. An issue
 * also carries its cause: the disbursement closing that issued it.
 */
final readonly class PostingReceipt
{
    public function __construct(
        public string $entryId,
        public string $kind,
        public string $walletId,
        public string $sourceType,
        public string $sourceId,
        public string $originOperationId,
        public string $amount,
        public string $recordedAt,
        public bool $replayed,
        public ?string $causeType = null,
        public ?string $causeId = null,
    ) {}
}
