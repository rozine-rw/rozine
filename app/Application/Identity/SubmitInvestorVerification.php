<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Application\Identity\Contracts\InvestorVerificationStore;

final class SubmitInvestorVerification
{
    public function __construct(private InvestorVerificationStore $store) {}

    /** @return array<string, mixed> */
    public function handle(int $userId, int $contextRevision, int $expectedRevision, string $requestId): array
    {
        return $this->store->submit($userId, $contextRevision, $expectedRevision, $requestId);
    }
}
