<?php

declare(strict_types=1);

namespace App\Application\Wallet;

use App\Application\Environment\EnvironmentIsolation;
use App\Application\Wallet\Contracts\WalletStore;
use App\Application\Wallet\Contracts\WithdrawalProvider;
use LogicException;
use Throwable;

/**
 * Sends committed withdrawals to the provider from the outbox, after commit and outside every lock,
 * exactly as deposits are sent: a claim commits before the call, an interrupted send is resent only
 * for a provider with safe idempotent sends, and a send that throws is recorded unacknowledged.
 */
final class DispatchWithdrawals
{
    public function __construct(private WalletStore $store, private WithdrawalProvider $provider, private SyntheticWalletGuard $guard,
        private EnvironmentIsolation $isolation) {}

    /** @return array{claimed: int, acknowledged: int} */
    public function handle(?string $withdrawalId = null, int $limit = 50): array
    {
        $this->guard->assertAllowed();
        if ($this->store->transactionOpen()) {
            throw new LogicException('WALLET_DISPATCH_TRANSACTION_OPEN: a provider is called only after the outermost commit.');
        }
        $instructions = $this->store->claimWithdrawals($withdrawalId, $limit, $this->provider->idempotentSends(), $this->isolation->profile());
        $acknowledged = 0;
        foreach ($instructions as $instruction) {
            try {
                $sent = $this->provider->send($instruction);
            } catch (Throwable) {
                $sent = false;
            }
            $this->store->recordWithdrawalDispatch($instruction->withdrawalId, $sent);
            $acknowledged += $sent ? 1 : 0;
        }

        return ['claimed' => count($instructions), 'acknowledged' => $acknowledged];
    }

    /** How many withdrawal dispatches are still waiting for a run to claim them. */
    public function backlog(): int
    {
        $this->guard->assertAllowed();

        return $this->store->claimableWithdrawals($this->provider->idempotentSends());
    }
}
