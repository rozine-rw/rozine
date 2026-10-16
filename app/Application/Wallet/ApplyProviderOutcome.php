<?php

declare(strict_types=1);

namespace App\Application\Wallet;

use App\Application\Environment\EnvironmentIsolation;
use App\Application\Wallet\Contracts\DepositProvider;
use App\Application\Wallet\Contracts\WalletStore;

/**
 * Applies a provider message. The adapter authenticates it first; then the store checks its
 * environment, currency and amount against the intent and records it once. A duplicate delivery
 * returns the recorded disposition; a changed body under a used event identity is a key conflict.
 * No provider event can impersonate the Investor or pass through the command journal.
 */
final class ApplyProviderOutcome
{
    public function __construct(private DepositProvider $provider, private WalletStore $store, private EnvironmentIsolation $isolation) {}

    /**
     * @param  array<string, mixed>  $message
     * @return array{disposition: string, state: string, credited: bool, replayed: bool}
     */
    public function handle(array $message): array
    {
        return $this->store->applyOutcome($this->provider->verify($message), $this->isolation->profile());
    }
}
