<?php

declare(strict_types=1);

namespace App\Application\Business;

use App\Application\Business\Contracts\BusinessApplicationStore;

final class ProjectBusinessApplicationOperation
{
    public function __construct(private BusinessApplicationStore $store) {}

    /**
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    public function handle(int $userId, int $contextRevision, array $result): array
    {
        return $this->store->projectOperation($userId, $contextRevision, $result);
    }
}
