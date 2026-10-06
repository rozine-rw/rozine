<?php

declare(strict_types=1);

namespace App\Application\Auditor;

use App\Application\Auditor\Contracts\AuditorProfileStore;

final class SubmitAuditorAccreditation
{
    public function __construct(private AuditorProfileStore $store) {}

    /** @return array<string, mixed> */
    public function handle(int $userId, int $contextRevision, int $expectedRevision, string $licence, string $expiresOn, string $filename, string $content, string $requestId, bool $renew = false): array
    {
        return $this->store->submit($userId, $contextRevision, $expectedRevision, $licence, $expiresOn, $filename, $content, $requestId, $renew);
    }
}
