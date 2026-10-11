<?php

declare(strict_types=1);

namespace App\Application\Wallet\Contracts;

use Closure;

/**
 * The named UAT wallet testers: the Parties an operator explicitly allowed to start synthetic
 * deposits there, separate from the staging mail testers. Each grant and revocation is an appended,
 * audited change with the operator's reason, at the Party's next revision; the highest revision
 * decides. A revocation stops new deposits only; deposits already accepted still reach their
 * outcome, and no change is ever rewritten.
 */
interface SyntheticWalletTesters
{
    /**
     * Whether the Party is a named tester now. A deposit asks this while it holds its account lock,
     * which a grant or revocation takes too, so a deposit is recorded either before a revocation or
     * not at all.
     */
    public function authorized(string $partyId): bool;

    /**
     * Names the Party, through its account, as a tester unless it already is one, and runs $prepare in
     * the same transaction under the account's lock, so a refusal from $prepare leaves no grant.
     *
     * @template TPrepared
     *
     * @param  Closure(): TPrepared  $prepare
     * @return array{change_id: string|null, prepared: TPrepared}
     */
    public function grant(string $partyId, int $userId, string $reason, string $requestId, Closure $prepare): array;

    /** Revokes a named tester under the account's lock and returns the change id; refuses a Party that is not one. */
    public function revoke(string $partyId, int $userId, string $reason, string $requestId): string;
}
