<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Application\Identity\Contracts\IdentityAccessStore;

final class RequestStaffRecovery
{
    public function __construct(private IdentityAccessStore $access) {}

    /** @param list<int> $recipients
     * @return array<string, mixed>
     */
    public function handle(int $operatorId, #[\SensitiveParameter] string $password, #[\SensitiveParameter] string $code, string $kind, array $recipients, string $reason, string $requestId): array
    {
        return $this->access->requestStaffRecovery($operatorId, $password, $code, $kind, $recipients, $reason, $requestId);
    }
}
