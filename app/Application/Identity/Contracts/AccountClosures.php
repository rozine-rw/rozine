<?php

declare(strict_types=1);

namespace App\Application\Identity\Contracts;

/**
 * What still stands between an account and its closure, and the closure itself. An account closes
 * only once nothing is owed to it or by it and nothing it started is still running (BRS DR-7: a
 * deletion request is honoured except where records are required by law, which the soft delete
 * keeps).
 *
 * @phpstan-type Blocker array{code: string, role: 'investor'|'business'|'auditor'|'staff', business_id: string|null}
 */
interface AccountClosures
{
    /**
     * The roles the account holds and what blocks its closure, in the order the page lists them.
     *
     * @return array{roles: list<string>, blockers: list<Blocker>}
     */
    public function standing(int $userId): array;

    /**
     * Close the account when nothing blocks it, checked again under a lock on the account. Returns
     * the blockers found instead when something does, and closes nothing.
     *
     * @return list<Blocker>
     */
    public function close(int $userId, ?string $reason): array;
}
