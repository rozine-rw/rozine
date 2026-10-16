<?php

declare(strict_types=1);

namespace App\Application\Primary;

use App\Application\Wallet\PostingReceipt;
use App\Domain\Primary\PrimaryReservation;

/** Returned held cash and its immutable terminal evidence, inside the caller transaction. */
final readonly class ReservationRelease
{
    public function __construct(public string $id, public int $revision, public PrimaryReservation $reservation,
        public PostingReceipt $posting) {}
}
