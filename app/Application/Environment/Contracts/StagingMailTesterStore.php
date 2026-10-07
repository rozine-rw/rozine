<?php

declare(strict_types=1);

namespace App\Application\Environment\Contracts;

/**
 * The named testers a superadmin lets staging email (`staging.mail.testers.manage`). Adding and
 * removing one are journalled commands with a written reason; a refusal comes back as a rejected
 * result.
 *
 * @phpstan-type Tester array{id: string, email: string, added_by: string, added_at: string}
 */
interface StagingMailTesterStore
{
    /** Whether this exact address, in any case, is a named tester. Read by the delivery guard. */
    public function includes(string $email): bool;

    /** @return list<Tester> */
    public function list(int $actorId): array;

    /** @return array<string, mixed> */
    public function add(int $actorId, string $email, string $reason, string $requestId): array;

    /** @return array<string, mixed> */
    public function remove(int $actorId, string $testerId, string $reason, string $requestId): array;
}
