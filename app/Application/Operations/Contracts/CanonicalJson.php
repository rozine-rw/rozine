<?php

declare(strict_types=1);

namespace App\Application\Operations\Contracts;

interface CanonicalJson
{
    /** @param array<string, mixed> $value */
    public function encode(array $value): string;
}
