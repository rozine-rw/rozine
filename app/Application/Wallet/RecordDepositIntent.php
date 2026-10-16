<?php

declare(strict_types=1);

namespace App\Application\Wallet;

use App\Application\Wallet\Contracts\WalletStore;

/**
 * `wallet.deposit`: records the intent, its receipt and its dispatch outbox row in one transaction,
 * then, once the caller's outermost transaction has committed, dispatches it; a rollback
 * discards the dispatch with the intent. Nothing is credited here; only a verified provider
 * success can credit, later and once. If the dispatch is lost, the outbox row remains for the
 * worker and the recorded result remains for the operation lookup.
 */
final class RecordDepositIntent
{
    public function __construct(private WalletStore $store, private DispatchDepositIntents $dispatch, private SyntheticWalletGuard $guard) {}

    /**
     * @param  array{currency: string, amount: string}  $amount
     * @return array<string, mixed>
     */
    public function handle(int $userId, int $contextRevision, string $requestId, array $amount, string $methodId): array
    {
        $result = $this->store->deposit($userId, $contextRevision, $requestId, $amount, $methodId);
        if ($result['status'] === 'completed' && $this->guard->allowed()) {
            $intentId = (string) $result['data']['intent_id'];
            $this->store->afterCommit(fn () => $this->dispatch->handle($intentId));
        }

        return $result;
    }
}
