<?php

declare(strict_types=1);

namespace App\Application\Primary;

use App\Application\Wallet\PostingReceipt;
use App\Domain\Primary\PrimaryReservation;

/** A new disclosure or a committed purchase, still inside the caller's transaction. */
final readonly class ReservationConfirmation
{
    public function __construct(public string $id, public int $revision, public PrimaryReservation $reservation,
        public ?string $commitmentId, public ?PostingReceipt $posting) {}
}
