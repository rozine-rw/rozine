<?php

declare(strict_types=1);

namespace App\Domain\Identity;

use RuntimeException;

final class IdentityViolation extends RuntimeException
{
    public function __construct(public readonly string $reason, public readonly int $status = 403)
    {
        parent::__construct($reason);
    }
}
