<?php

declare(strict_types=1);

namespace App\Application\Auditor;

use App\Application\Auditor\Contracts\AuditorProfileStore;

/** The partner's own registered address, from the Profile's Personal & contact section. */
final class SetAuditorAddress
{
    public function __construct(private AuditorProfileStore $store) {}

    /**
     * @param  array<string, mixed>  $address
     * @return array<string, mixed>
     */
    public function handle(int $userId, int $contextRevision, int $expectedRevision, array $address, string $requestId): array
    {
        return $this->store->address($userId, $contextRevision, $expectedRevision, $address, $requestId);
    }
}
