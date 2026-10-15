<?php

declare(strict_types=1);

namespace App\Application\Wallet;

use App\Application\Wallet\Contracts\BusinessWithdrawals;

/**
 * `business.wallet.withdraw`: records the withdrawal, the hold on the Business's available cash, its
 * receipt and its dispatch outbox row in one transaction under the actor's current mandate
 * permission, then dispatches it once the caller's outermost transaction has committed. Only a
 * verified provider success pays it out, and a verified failure returns the cash.
 */
final class RecordBusinessWithdrawal
{
    public function __construct(private BusinessWithdrawals $withdrawals, private DispatchBusinessWithdrawals $dispatch, private SyntheticWalletGuard $guard) {}

    /**
     * @param  array{currency: string, amount: string}  $amount
     * @return array<string, mixed>
     */
    public function handle(int $userId, int $contextRevision, string $businessId, string $requestId, array $amount, string $methodId): array
    {
        $result = $this->withdrawals->withdraw($userId, $contextRevision, $businessId, $requestId, $amount, $methodId);
        if ($result['status'] === 'completed' && $this->guard->allowed()) {
            $withdrawalId = (string) $result['data']['withdrawal_id'];
            $this->withdrawals->afterCommit(fn () => $this->dispatch->handle($withdrawalId));
        }

        return $result;
    }
}
