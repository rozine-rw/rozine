<?php

declare(strict_types=1);

namespace App\Application\Wallet;

use App\Application\Environment\EnvironmentIsolation;
use App\Application\Wallet\Contracts\WalletStore;
use App\Application\Wallet\Contracts\WithdrawalProvider;

/**
 * Applies a provider's withdrawal outcome. The adapter authenticates it first; then the store checks
 * its environment, currency and amount against the withdrawal and records it once. A success pays the
 * held cash out and a failure returns it to the available balance, each at most once.
 */
final class ApplyWithdrawalOutcome
{
    public function __construct(private WithdrawalProvider $provider, private WalletStore $store, private EnvironmentIsolation $isolation) {}

    /**
     * @param  array<string, mixed>  $message
     * @return array{disposition: string, state: string, settled: bool, replayed: bool}
     */
    public function handle(array $message): array
    {
        return $this->store->applyWithdrawalOutcome($this->provider->verify($message), $this->isolation->profile());
    }
}
