<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Domain\Identity\IdentityViolation;
use RuntimeException;

/**
 * A Staff & Roles change the identity store refused, carrying its reason code so the console can
 * explain it on the form. The store's own violation is kept as the previous exception, so a reason
 * the console does not explain is rethrown and rendered as before.
 */
final class StaffAccessRefused extends RuntimeException
{
    public function __construct(public readonly string $reason, IdentityViolation $violation)
    {
        parent::__construct($reason, $violation->status, $violation);
    }

    /** The store's violation, rendered as any other identity refusal. */
    public function violation(): IdentityViolation
    {
        /** @var IdentityViolation */
        return $this->getPrevious();
    }
}
