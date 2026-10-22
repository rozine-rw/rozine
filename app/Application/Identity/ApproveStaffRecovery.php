<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Application\Identity\Contracts\IdentityAccessStore;

final class ApproveStaffRecovery
{
    public function __construct(private IdentityAccessStore $access) {}

    /** @param list<int> $expectedRecipients
     * @return array<string, mixed>
     */
    public function handle(int $operatorId, #[\SensitiveParameter] string $password, #[\SensitiveParameter] string $code, string $proposalId, string $payloadSha256, string $reason, string $requestId, string $expectedKind, array $expectedRecipients): array
    {
        return $this->access->approveStaffRecovery($operatorId, $password, $code, $proposalId, $payloadSha256, $reason, $requestId, $expectedKind, $expectedRecipients);
    }
}
