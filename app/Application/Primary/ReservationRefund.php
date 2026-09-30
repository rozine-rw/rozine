<?php

declare(strict_types=1);

namespace App\Application\Primary;

use App\Application\Wallet\ReturnedCash;

/** Full returned principal; the original confirmation and unit claims remain retained. */
final readonly class ReservationRefund
{
    public function __construct(public string $id, public int $revision, public string $commitmentId, public ReturnedCash $cash) {}
}
