<?php

declare(strict_types=1);

namespace App\Application\Wallet;

use App\Application\Environment\EnvironmentIsolation;
use Illuminate\Contracts\Config\Repository;
use LogicException;

/**
 * The synthetic deposit provider, its policies and its hooks exist only on local and testing
 * installations with live money off. Demo, UAT and production never reach them.
 */
final class SyntheticWalletGuard
{
    public function __construct(private EnvironmentIsolation $isolation, private Repository $config) {}

    public function allowed(): bool
    {
        return in_array($this->isolation->profile(), ['local', 'testing'], true) && $this->config->get('isolation.live_money_enabled') === false;
    }

    public function assertAllowed(): void
    {
        if (! $this->allowed()) {
            throw new LogicException('WALLET_SYNTHETIC_ONLY: synthetic deposits run only on local or testing with live money off.');
        }
    }
}
