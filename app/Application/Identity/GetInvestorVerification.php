<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Application\Identity\Contracts\InvestorVerificationStore;

/** @phpstan-import-type Submission from InvestorVerificationStore */
final class GetInvestorVerification
{
    public function __construct(private InvestorVerificationStore $store) {}

    /** @return Submission */
    public function handle(int $userId): array
    {
        return $this->store->get($userId);
    }
}
