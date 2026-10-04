<?php

declare(strict_types=1);

namespace App\Application\Wallet;

use App\Application\Environment\EnvironmentIsolation;
use App\Application\Wallet\Contracts\BusinessWalletStore;
use App\Application\Wallet\Contracts\DepositProvider;
use LogicException;
use Throwable;

/**
 * Sends committed Business deposit intents to the provider from their outbox, after commit and
 * outside every lock, under the same claim and acknowledgement rules as Investor deposits.
 */
final class DispatchBusinessDeposits
{
    public function __construct(private BusinessWalletStore $store, private DepositProvider $provider, private SyntheticWalletGuard $guard,
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
