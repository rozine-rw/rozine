<?php

declare(strict_types=1);

namespace App\Domain\Wallet;

/**
 * A current account case and the actions its recorded scope covers. The section 11.4 high-risk hold
 * covers withdrawals, new primary commitments and secondary trading, never deposits; any other case
 * is read from its own scope, so nothing here bypasses a scope that does cover deposits.
 */
final readonly class AccountRestriction
{
    /** @param list<string> $scope */
    public function __construct(public string $cause, public array $scope) {}

    public function blocksDeposit(): bool
    {
        return in_array('deposits', $this->scope, true);
    }
}
