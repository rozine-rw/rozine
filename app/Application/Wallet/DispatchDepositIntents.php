<?php

declare(strict_types=1);

namespace App\Application\Wallet;

use App\Application\Environment\EnvironmentIsolation;
use App\Application\Wallet\Contracts\DepositProvider;
use App\Application\Wallet\Contracts\WalletStore;
use LogicException;
use Throwable;

/**
 * Sends committed deposit intents to the provider from the outbox, after commit and outside every
 * lock. A claim commits before the call, so a crash leaves a claimed row the next run resends only
 * when the provider guarantees safe idempotent sends; otherwise it stays for reconciliation of the
 * same operation. A send that throws is recorded unacknowledged and never retried automatically.
 */
final class DispatchDepositIntents
{
    public function __construct(private WalletStore $store, private DepositProvider $provider, private SyntheticWalletGuard $guard,
        private EnvironmentIsolation $isolation) {}

    /** @return array{claimed: int, acknowledged: int} */
    public function handle(?string $intentId = null, int $limit = 50): array
    {
        $this->guard->assertAllowed();
        if ($this->store->transactionOpen()) {
            throw new LogicException('WALLET_DISPATCH_TRANSACTION_OPEN: a provider is called only after the outermost commit.');
        }
        $instructions = $this->store->claimDispatches($intentId, $limit, $this->provider->idempotentSends(), $this->isolation->profile());
        $acknowledged = 0;
        foreach ($instructions as $instruction) {
            try {
                $sent = $this->provider->initiate($instruction);
            } catch (Throwable) {
                $sent = false;
            }
            $this->store->recordDispatch($instruction->intentId, $sent);
            $acknowledged += $sent ? 1 : 0;
        }

        return ['claimed' => count($instructions), 'acknowledged' => $acknowledged];
    }
}
