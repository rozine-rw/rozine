<?php

declare(strict_types=1);

namespace App\Domain\Operations;

use RuntimeException;

final class CommandRejection extends RuntimeException
{
    /** @param array<string, list<string>> $fieldErrors */
    public function __construct(
        public readonly string $reason,
        public readonly int $status = 409,
        public readonly ?int $revision = null,
        public readonly array $fieldErrors = [],
    ) {
        parent::__construct($reason);
    }
}
