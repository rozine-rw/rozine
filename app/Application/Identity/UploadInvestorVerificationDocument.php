<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Application\Identity\Contracts\InvestorVerificationStore;

final class UploadInvestorVerificationDocument
{
    public function __construct(private InvestorVerificationStore $store) {}

    /** @return array<string, mixed> */
    public function handle(int $userId, int $contextRevision, int $expectedRevision, string $slot, string $filename, string $content, string $requestId): array
    {
        return $this->store->upload($userId, $contextRevision, $expectedRevision, $slot, $filename, $content, $requestId);
    }
}
