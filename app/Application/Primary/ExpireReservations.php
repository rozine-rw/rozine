<?php

declare(strict_types=1);

namespace App\Application\Primary;

use App\Application\Primary\Contracts\PrimaryReservations;

final readonly class ExpireReservations
{
    public function __construct(private PrimaryReservations $reservations) {}

    public function handle(int $limit): int
    {
        return $this->reservations->expireDue($limit);
    }
}
