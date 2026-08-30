<?php

namespace App\Domain\Pulse;

enum PulseSector: string
{
    case Agriculture = 'Agriculture';
    case RetailAndTrade = 'Retail & trade';
    case Logistics = 'Logistics';
    case Manufacturing = 'Manufacturing';
    case Services = 'Services';
    case Energy = 'Energy';
    case Other = 'Other';

    /**
     * Get the points this sector adds to the Pulse strength score.
     */
    public function score(): int
    {
        return match ($this) {
            self::RetailAndTrade => 6,
            self::Logistics, self::Services => 5,
            self::Agriculture, self::Manufacturing => 4,
            self::Energy => 3,
            self::Other => 2,
        };
    }
}
