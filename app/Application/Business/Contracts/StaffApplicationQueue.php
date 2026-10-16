<?php

declare(strict_types=1);

namespace App\Application\Business\Contracts;

interface StaffApplicationQueue
{
    /** @return array<string, mixed> */
    public function page(string $tab, string $search, ?string $before, int $limit, ?string $applicationId): array;
}
