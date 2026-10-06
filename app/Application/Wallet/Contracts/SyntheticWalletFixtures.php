<?php

declare(strict_types=1);

namespace App\Application\Wallet\Contracts;

/**
 * The local and testing hooks behind `local:wallet`. Everything here is synthetic and refuses to
 * run where the synthetic provider is not allowed; it is not a staff hold/release workflow, a live
 * policy or a provider integration.
 */
interface SyntheticWalletFixtures
{
    /**
     * Gives the Party a verified synthetic MTN method and makes sure an explicit synthetic policy
     * (fee 0) is in force, appending a new version only when none is.
     *
     * @return array{method_id: string, policy_version: string}
     */
    public function prepareInvestor(string $partyId): array;

    /** Appends a withdrawn policy version, leaving deposits unavailable. Returns its version. */
    public function withdrawPolicy(): string;

    /** Appends a section 11.4 high-risk hold for the Party, in effect now for 24 hours. */
    public function restrict(string $partyId): string;

    /**
     * A signed synthetic provider event for the deposit recorded under this request id.
     *
     * @return array{intent_id: string, message: array<string, string>}
     */
    public function event(string $requestId, string $state, ?string $eventId, ?string $amount): array;
}
