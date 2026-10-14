<?php

declare(strict_types=1);

namespace App\Application\Wallet;

use App\Application\Wallet\Contracts\BusinessWalletStore;
use App\Application\Wallet\Contracts\BusinessWithdrawals;
use App\Domain\Operations\CommandRejection;

/** The Business wallet operation lookup after a lost answer. It reads the recorded result and never resends. */
final class FindBusinessWalletOperation
{
    public function __construct(private BusinessWalletStore $store, private BusinessWithdrawals $withdrawals) {}

    /** @return array<string, mixed> */
    public function handle(int $userId, int $contextRevision, string $businessId, string $command, string $requestId): array
    {
        return match ($command) {
            'business.wallet.deposit' => $this->store->findDeposit($userId, $contextRevision, $businessId, $requestId),
            'business.wallet.withdraw' => $this->withdrawals->findWithdrawal($userId, $contextRevision, $businessId, $requestId),
            default => throw new CommandRejection('OPERATION_NOT_FOUND', 404),
        };
    }
}
