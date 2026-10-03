<?php

declare(strict_types=1);

namespace App\Infrastructure\Primary;

use App\Application\Primary\Contracts\PrimaryAdmission;
use App\Domain\Operations\CommandRejection;
use Closure;

/**
 * Fails closed until the current-authority admission source exists: every new reserve or confirm
 * is refused `POLICY_INPUT_REQUIRED` inside the checkout's journal savepoint, with no hold, cash or
 * claim written. Nothing defaults to zero, and no terms are invented.
 */
final class UnavailablePrimaryAdmission implements PrimaryAdmission
{
    public function forReserve(int $userId, int $expectedCampaignRevision, int $quoteRevision): Closure
    {
        return self::refuse(...);
    }

    public function forConfirm(int $userId): Closure
    {
        return self::refuse(...);
    }

    private static function refuse(): never
    {
        throw new CommandRejection('POLICY_INPUT_REQUIRED');
    }
}
