<?php

declare(strict_types=1);

namespace App\Application\Operations;

use App\Application\Operations\Contracts\PolicyChangeStore;
use App\Application\Operations\Contracts\PolicySettings;

/**
 * The Policies page's changeable settings (Erastus, 26 Nov 2026): staff holding `policies.change`
 * propose a new value with a reason, and it takes effect only once both founders have each approved
 * it. Either founder may refuse it.
 *
 * @phpstan-import-type Board from PolicyChangeStore
 */
final readonly class ChangePlatformPolicies
{
    public function __construct(private PolicyChangeStore $store, private PolicySettings $settings) {}

    /** @return Board */
    public function board(int $actorId): array
    {
        return $this->store->board($actorId);
    }

    /** @return array<string, int> */
    public function values(): array
    {
        return $this->settings->values();
    }

    /** @return array<string, mixed> */
    public function propose(int $actorId, string $key, string $value, string $reason, string $requestId): array
    {
        return $this->store->propose($actorId, $key, $value, $reason, $requestId);
    }

    /** @return array<string, mixed> */
    public function decide(int $actorId, string $changeId, string $decision, string $reason, string $requestId): array
    {
        return $this->store->decide($actorId, $changeId, $decision, $reason, $requestId);
    }
}
