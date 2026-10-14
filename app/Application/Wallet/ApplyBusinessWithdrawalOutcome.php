<?php

declare(strict_types=1);

namespace App\Application\Wallet;

use App\Application\Environment\EnvironmentIsolation;
use App\Application\Wallet\Contracts\BusinessWithdrawals;
use App\Application\Wallet\Contracts\WithdrawalProvider;

/**
 * Applies a provider's outcome for a Business withdrawal. The adapter authenticates it first; the
 * store then checks it against the withdrawal and records it once. A success pays the held cash out
 * and a failure returns it to the Business's available balance, each at most once.
 */
final class ApplyBusinessWithdrawalOutcome
{
    public function __construct(private WithdrawalProvider $provider, private BusinessWithdrawals $withdrawals, private EnvironmentIsolation $isolation) {}

    /**
     * @param  array<string, mixed>  $message
     * @return array{disposition: string, state: string, settled: bool, replayed: bool}
     */
    public function handle(array $message): array
    {
        return $this->withdrawals->applyOutcome($this->provider->verify($message), $this->isolation->profile());
    }
}
