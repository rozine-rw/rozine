<?php

declare(strict_types=1);

namespace App\Application\Primary;

/** Original whole-RWF gross components, without fees, paid amounts or current payable claims. */
final readonly class RetainedNoteInstalment
{
    public function __construct(public int $index, public string $dueDate, public string $principal,
        public string $contractualReturn, public string $gross) {}
}
