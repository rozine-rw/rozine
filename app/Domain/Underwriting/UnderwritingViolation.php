<?php

declare(strict_types=1);

namespace App\Domain\Underwriting;

use RuntimeException;

final class UnderwritingViolation extends RuntimeException
{
    public function __construct(public readonly string $reasonCode)
    {
        parent::__construct($reasonCode);
    }
}
