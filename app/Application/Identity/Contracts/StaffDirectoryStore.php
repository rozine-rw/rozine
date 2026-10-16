<?php

declare(strict_types=1);

namespace App\Application\Identity\Contracts;

/**
 * The Admin console's Staff & Roles directory: every staff account with its user's name and email,
 * its roles and whether it is enabled. A disabled account is the console's frozen operator; the
 * identity audit log records who changed its access, when and why. Reads only and never locks; the
 * caller checks the staff permission.
 *
 * @phpstan-type StaffRow array{user_id: int, name: string, email: string, roles: list<string>, enabled: bool}
 * @phpstan-type Counts array{all: int, active: int, frozen: int, approvers: int}
 * @phpstan-type Directory array{rows: list<StaffRow>, matching: int, counts: Counts}
 * @phpstan-type HistoryEntry array{
 *     id: string, at: string, actor: string|null, action: string, reason: string,
 *     before: array<string, mixed>, after: array<string, mixed>
 * }
 * @phpstan-type Member array{row: StaffRow, history: list<HistoryEntry>}
 */
interface StaffDirectoryStore
{
    /**
     * Every staff account matching a name or email search, by name, under a chip; the counts are of
     * the search's matches before the chip applies.
     *
     * @param  'all'|'active'|'frozen'  $chip
     * @return Directory
     */
    public function directory(string $chip, string $search): array;

    /**
     * One staff account and every identity audit event recorded against it, newest first. The
     * actor is the acting user's name, or null for the server console.
     *
     * @return Member|null
     */
    public function member(int $userId): ?array;
}
