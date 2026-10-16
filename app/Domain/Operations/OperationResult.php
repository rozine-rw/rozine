<?php

declare(strict_types=1);

namespace App\Domain\Operations;

final readonly class OperationResult
{
    /**
     * @param  array<string, mixed>  $data
     * @param  list<string>  $allowedActions
     */
    public function __construct(
        public string $code,
        public array $data,
        public int $revision,
        public array $allowedActions = [],
        public string $policyVersion = 'engineering-2026-09-23.4',
    ) {}
}
