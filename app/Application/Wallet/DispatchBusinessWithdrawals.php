<?php

declare(strict_types=1);

namespace App\Application\Wallet;

use App\Application\Environment\EnvironmentIsolation;
use App\Application\Wallet\Contracts\BusinessWithdrawals;
use App\Application\Wallet\Contracts\WithdrawalProvider;
use LogicException;
use Throwable;

/**
 * Sends committed Business withdrawals to the provider from their outbox, after commit and outside
 * every lock, exactly as Investor withdrawals are sent.
 */
final class DispatchBusinessWithdrawals
{
    public function __construct(private BusinessWithdrawals $withdrawals, private WithdrawalProvider $provider, private SyntheticWalletGuard $guard,
        private EnvironmentIsolation $isolation) {}

    /** @return array{claimed: int, acknowledged: int} */
    public function handle(?string $withdrawalId = null, int $limit = 50): array
    {
        $this->guard->assertAllowed();
        if ($this->withdrawals->transactionOpen()) {
            throw new LogicException('WALLET_DISPATCH_TRANSACTION_OPEN: a provider is called only after the outermost commit.');
        }
        $instructions = $this->withdrawals->claim($withdrawalId, $limit, $this->provider->idempotentSends(), $this->isolation->profile());
        $acknowledged = 0;
        foreach ($instructions as $instruction) {
            try {
                $sent = $this->provider->send($instruction);
            } catch (Throwable) {
                $sent = false;
            }
            $this->withdrawals->recordDispatch($instruction->withdrawalId, $sent);
            $acknowledged += $sent ? 1 : 0;
        }

        return ['claimed' => count($instructions), 'acknowledged' => $acknowledged];
    }

    /** How many Business withdrawal dispatches are still waiting for a run to claim them. */
    public function backlog(): int
    {
        $this->guard->assertAllowed();

        return $this->withdrawals->claimable($this->provider->idempotentSends());
    }
}
