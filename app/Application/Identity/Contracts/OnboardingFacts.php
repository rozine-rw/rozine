<?php

declare(strict_types=1);

namespace App\Application\Identity\Contracts;

/**
 * What the dashboard hub is built from: the account's own sign-up dates, its identity check and its
 * application to become a Business representative or an Auditor, with what Compliance sent back.
 *
 * @phpstan-import-type Facts from \App\Domain\Identity\OnboardingHub
 */
interface OnboardingFacts
{
    /** @return Facts|null null for an account with no Party of its own */
    public function facts(int $userId): ?array;
}
