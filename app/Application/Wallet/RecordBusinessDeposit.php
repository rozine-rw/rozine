<?php

declare(strict_types=1);

namespace App\Application\Wallet;

use App\Application\Wallet\Contracts\BusinessWalletStore;

/**
 * `business.wallet.deposit`: records the intent, its receipt and its queued dispatch outbox row in one
 * transaction under the actor's current mandate permission, then dispatches it once the caller's
 * outermost transaction has committed. Nothing is credited here; only a verified provider success
 * can credit, later and once.
 */
final class RecordBusinessDeposit
{
    public function __construct(private BusinessWalletStore $store, private DispatchBusinessDeposits $dispatch, private SyntheticWalletGuard $guard) {}

    /**
     * @param  array{currency: string, amount: string}  $amount
     * @return array<string, mixed>
     */
    public function handle(int $userId, int $contextRevision, string $businessId, string $requestId, array $amount, string $methodId): array
    {
        $result = $this->store->deposit($userId, $contextRevision, $businessId, $requestId, $amount, $methodId);
        if ($result['status'] === 'completed' && $this->guard->allowed()) {
            $intentId = (string) $result['data']['intent_id'];
            $this->store->afterCommit(fn () => $this->dispatch->handle($intentId));
        }

        return $result;
    }
}
