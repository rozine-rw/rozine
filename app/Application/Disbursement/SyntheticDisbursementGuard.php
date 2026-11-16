<?php

declare(strict_types=1);

namespace App\Application\Disbursement;

use App\Application\Environment\EnvironmentIsolation;
use App\Application\Environment\UatMoneySimulation;
use Illuminate\Contracts\Config\Repository;
use LogicException;

/**
 * The synthetic funding source, destination and connection fixtures, the worker's fixtures and the
 * local hooks exist only on local and testing installations with live money off. The synthetic payout
 * provider, the dispatcher and the reconciler also run on UAT while its money simulation is on
 * (`UatMoneySimulation`), where the funding source is the real retained one and the destination is
 * the Business's own linked account. Demo and production reach the unavailable adapters instead.
 */
final class SyntheticDisbursementGuard
{
    public function __construct(private EnvironmentIsolation $isolation, private Repository $config, private UatMoneySimulation $simulation) {}

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

    /** Whether the synthetic payout provider, dispatcher and reconciler may run here. */
    public function providerAllowed(): bool
    {
        return $this->allowed() || $this->simulation->active();
    }

    public function assertProviderAllowed(): void
    {
        if (! $this->providerAllowed()) {
            throw new LogicException('DISBURSEMENT_SYNTHETIC_ONLY: the synthetic payout provider runs only on local, testing or the UAT money simulation.');
        }
    }

    /** Whether this is the UAT money simulation, where the provider settles every sent payout on its next query. */
    public function simulating(): bool
    {
        return $this->simulation->active();
    }
}
