<?php

declare(strict_types=1);

namespace App\Application\Environment;

use App\Application\Environment\Contracts\StagingMailTesterStore;

/**
 * The superadmin's list of named staging mail testers. It exists only on staging; everywhere else
 * the page and its commands are not found.
 *
 * @phpstan-import-type Tester from StagingMailTesterStore
 */
final class ManageStagingMailTesters
{
    public function __construct(private StagingMailTesterStore $store, private EnvironmentIsolation $isolation) {}

    public function available(): bool
    {
        return $this->isolation->profile() === 'uat';
    }

    /**
     * The domains and addresses set on the server (STAGING_MAIL_RECIPIENTS), which always receive
     * staging mail and cannot be changed here.
     *
     * @return list<string>
     */
    public function serverRecipients(): array
    {
        return $this->isolation->stagingMailServerRecipients();
    }

    /**
     * The named testers whose address contains the search, in any case; all of them for an empty one.
     *
     * @return list<Tester>
     */
    public function list(int $actorId, string $search = ''): array
    {
        $needle = mb_strtolower(trim($search));

        return array_values(array_filter($this->store->list($actorId),
            fn (array $tester): bool => $needle === '' || str_contains($tester['email'], $needle)));
    }

    /** @return array<string, mixed> */
    public function add(int $actorId, string $email, string $reason, string $requestId): array
    {
        return $this->store->add($actorId, $email, $reason, $requestId);
    }

    /** @return array<string, mixed> */
    public function remove(int $actorId, string $testerId, string $reason, string $requestId): array
    {
        return $this->store->remove($actorId, $testerId, $reason, $requestId);
    }
}
