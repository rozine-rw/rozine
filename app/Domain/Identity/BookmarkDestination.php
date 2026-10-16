<?php

declare(strict_types=1);

namespace App\Domain\Identity;

/** @phpstan-type Destination array{route: string, parameters: array<string, string>, query: array<string, string>} */
final class BookmarkDestination
{
    /**
     * Checkpoint 1 only exposes role homes. Later destinations require their own
     * parameter validation and record authorization before joining this allowlist.
     *
     * @param  array<string, mixed>  $parameters
     * @param  array<string, mixed>  $query
     * @return Destination
     */
    public function validate(string $role, string $route, array $parameters, array $query): array
    {
        if (! in_array($role, ['investor', 'business', 'auditor'], true)
            || $route !== $role.'.home' || $parameters !== []
            || array_diff(array_keys($query), ['section']) !== []
            || (array_key_exists('section', $query) && ! in_array($query['section'], ['overview', 'access'], true))) {
            throw new IdentityViolation('BOOKMARK_DESTINATION_INVALID', 422);
        }

        return ['route' => $route, 'parameters' => [], 'query' => isset($query['section']) ? ['section' => $query['section']] : []];
    }
}
