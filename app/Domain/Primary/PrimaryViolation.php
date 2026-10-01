<?php

declare(strict_types=1);

namespace App\Domain\Primary;

use RuntimeException;

final class PrimaryViolation extends RuntimeException
{
    public function __construct(public readonly string $reasonCode)
    {
        parent::__construct($reasonCode);
    }
}
