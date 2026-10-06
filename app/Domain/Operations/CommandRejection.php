<?php

declare(strict_types=1);

namespace App\Domain\Operations;

use RuntimeException;

final class CommandRejection extends RuntimeException
{
    /**
     * @param  array<string, list<string>>  $fieldErrors
     * @param  array<string, mixed>  $data  Authorized facts retained in the refusal receipt.
     */
    public function __construct(
        public readonly string $reason,
        public readonly int $status = 409,
        public readonly ?int $revision = null,
        public readonly array $fieldErrors = [],
        public readonly array $data = [],
    ) {
        parent::__construct($reason);
    }
}
