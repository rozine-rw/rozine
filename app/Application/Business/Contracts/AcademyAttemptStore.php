<?php

declare(strict_types=1);

namespace App\Application\Business\Contracts;

use Closure;

/**
 * A Business's Academy test attempts (BRS v3.0 RDY-R07), journaled under the acting Party with
 * only a digest of the answers.
 *
 * @phpstan-import-type Attempt from \App\Domain\Business\ReadinessAcademy
 *
 * @phpstan-type Scored array{attempt: int, questions: list<int>, answers: array<int, int>, score: int, passed: bool}
 */
interface AcademyAttemptStore
{
    /**
     * Every attempt the Business has made, oldest first. Read only behind the caller's own
     * Business authority.
     *
     * @return list<Attempt>
     */
    public function forBusiness(string $businessId): array;

    /**
     * Records one scored attempt at `$module` for a Business the person may view. `$decide`
     * receives the Business id under that authority, with that module's attempts locked, and
     * returns the scored attempt or refuses.
     *
     * @param  array<string, mixed>  $input  the answers as sent, digested into the journal
     * @param  Closure(string): Scored  $decide
     * @return array<string, mixed>
     */
    public function submit(int $userId, int $contextRevision, string $businessId, string $module, string $requestId, array $input, Closure $decide): array;
}
