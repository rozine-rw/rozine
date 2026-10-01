<?php

declare(strict_types=1);

namespace App\Application\Primary;

use App\Application\Wallet\PostingReceipt;
use App\Domain\Primary\PrimaryReservation;

/** Internal outcome; commit of the caller's outer transaction is still required. */
final readonly class ReservedCheckout
{
    public function __construct(
        public string $id,
        public string $campaignId,
        public string $partyId,
        public string $originOperationId,
        public PrimaryReservation $reservation,
        public PostingReceipt $hold,
    ) {}
}
