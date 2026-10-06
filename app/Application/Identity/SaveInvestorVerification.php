<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Application\Identity\Contracts\InvestorVerificationStore;

final class SaveInvestorVerification
{
    public function __construct(private InvestorVerificationStore $store) {}

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function handle(int $userId, int $contextRevision, int $expectedRevision, string $step, array $input, string $requestId): array
    {
        return $this->store->save($userId, $contextRevision, $expectedRevision, $step, $input, $requestId);
    }
}
