<?php

declare(strict_types=1);

namespace App\Application\Wallet;

use App\Application\Environment\EnvironmentIsolation;
use App\Application\Wallet\Contracts\BusinessWalletStore;
use App\Application\Wallet\Contracts\DepositProvider;

/**
 * Applies a provider message to a Business deposit. The adapter authenticates it first; the store
 * then resolves its reference through the registry to a Business intent only, checks environment,
 * currency and amount, and records it once. No provider event passes through the command journal.
 */
final class ApplyBusinessProviderOutcome
{
    public function __construct(private DepositProvider $provider, private BusinessWalletStore $store, private EnvironmentIsolation $isolation) {}

    /**
     * @param  array<string, mixed>  $message
     * @return array{disposition: string, state: string, credited: bool, replayed: bool}
     */
    public function handle(array $message): array
    {
        return $this->store->applyOutcome($this->provider->verify($message), $this->isolation->profile());
    }
}
