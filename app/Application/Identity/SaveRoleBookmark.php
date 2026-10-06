<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Application\Identity\Contracts\IdentityAccessStore;

final class SaveRoleBookmark
{
    public function __construct(private IdentityAccessStore $access) {}

    /**
     * @param  array<string, mixed>  $parameters
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function handle(int $userId, string $role, string $route, array $parameters, array $query, int $expectedContext, string $requestId): array
    {
        return $this->access->saveBookmark($userId, $role, $route, $parameters, $query, $expectedContext, $requestId);
    }
}
