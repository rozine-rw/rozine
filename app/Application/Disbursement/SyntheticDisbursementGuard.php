<?php

declare(strict_types=1);

namespace App\Application\Disbursement;

use App\Application\Environment\EnvironmentIsolation;
use Illuminate\Contracts\Config\Repository;
use LogicException;

/**
 * The synthetic funding source, destination and connection fixtures, the synthetic payout
 * provider, the worker and the local hooks exist only on local and testing installations with live
 * money off. Demo, UAT and production reach the unavailable adapters instead.
 */
final class SyntheticDisbursementGuard
{
    public function __construct(private EnvironmentIsolation $isolation, private Repository $config) {}

    public function allowed(): bool
    {
        return in_array($this->isolation->profile(), ['local', 'testing'], true) && $this->config->get('isolation.live_money_enabled') === false;
    }

    public function assertAllowed(): void
    {
        if (! $this->allowed()) {
            throw new LogicException('DISBURSEMENT_SYNTHETIC_ONLY: synthetic disbursements run only on local or testing with live money off.');
        }
    }
}
