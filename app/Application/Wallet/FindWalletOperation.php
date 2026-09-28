<?php

declare(strict_types=1);

namespace App\Application\Wallet;

use App\Application\Wallet\Contracts\WalletStore;
use App\Domain\Operations\CommandRejection;

/** The operation lookup after a lost answer. It reads the recorded result and never resends. */
final class FindWalletOperation
{
    public function __construct(private WalletStore $store) {}

    /** @return array<string, mixed> */
    public function handle(int $userId, int $contextRevision, string $command, string $requestId): array
    {
        if ($command !== 'wallet.deposit') {
            throw new CommandRejection('OPERATION_NOT_FOUND', 404);
        }

        return $this->store->findDeposit($userId, $contextRevision, $requestId);
    }
}
