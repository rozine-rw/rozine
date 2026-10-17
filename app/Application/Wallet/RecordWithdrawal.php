<?php

declare(strict_types=1);

namespace App\Application\Wallet;

use App\Application\Wallet\Contracts\WalletStore;

/**
 * `wallet.withdraw`: records the withdrawal, the hold on its available cash, its receipt and its
 * dispatch outbox row in one transaction, then, once the caller's outermost transaction has
 * committed, dispatches it; a rollback discards the dispatch with the withdrawal. Nothing is paid out
 * here; only a verified provider success can pay it out, and a verified failure returns the cash.
 */
final class RecordWithdrawal
{
    public function __construct(private WalletStore $store, private DispatchWithdrawals $dispatch, private SyntheticWalletGuard $guard) {}

    /**
     * @param  array{currency: string, amount: string}  $amount
     * @return array<string, mixed>
     */
    public function handle(int $userId, int $contextRevision, string $requestId, array $amount, string $methodId): array
    {
        $result = $this->store->withdraw($userId, $contextRevision, $requestId, $amount, $methodId);
        if ($result['status'] === 'completed' && $this->guard->allowed()) {
            $withdrawalId = (string) $result['data']['withdrawal_id'];
            $this->store->afterCommit(fn () => $this->dispatch->handle($withdrawalId));
        }

        return $result;
    }
}
