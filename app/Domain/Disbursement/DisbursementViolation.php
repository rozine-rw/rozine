<?php

declare(strict_types=1);

namespace App\Domain\Disbursement;

use RuntimeException;

/** A disbursement rule was broken by trusted input: an integrity failure, never a staff refusal. */
final class DisbursementViolation extends RuntimeException
{
    public function __construct(public readonly string $reason)
    {
        parent::__construct($reason);
    }
}
