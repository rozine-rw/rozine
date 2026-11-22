<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Application\Identity\Contracts\AccountClosures;

/**
 * Self-service account closure (soft delete): what blocks it, and closing once nothing does.
 *
 * @phpstan-import-type Blocker from AccountClosures
 */
final readonly class CloseAccount
{
    public function __construct(private AccountClosures $closures) {}

    /** @return array{roles: list<string>, blockers: list<Blocker>} */
    public function standing(int $userId): array
    {
        return $this->closures->standing($userId);
    }

    /** @return list<Blocker> */
    public function handle(int $userId, ?string $reason): array
    {
        $reason = $reason === null ? null : trim($reason);

        return $this->closures->close($userId, $reason === '' ? null : $reason);
    }
}
