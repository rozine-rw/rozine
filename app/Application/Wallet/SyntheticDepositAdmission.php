<?php

declare(strict_types=1);

namespace App\Application\Wallet;

use App\Application\Environment\EnvironmentIsolation;
use App\Application\Environment\UatMoneySimulation;
use App\Application\Wallet\Contracts\SyntheticWalletTesters;

/**
 * Who may start a new synthetic deposit. On UAT only a named wallet tester may (asked again for every
 * deposit, beside the Investor role the deposit already requires), and a Business may link an account,
 * deposit and withdraw only while UAT's money simulation is on (Erastus, 14 Nov 2026), so it can be
 * paid its raise and repay it; local and testing keep their fixtures open. It decides new
 * intents only: a deposit already accepted still settles to its outcome.
 */
final class SyntheticDepositAdmission
{
    public function __construct(private SyntheticWalletGuard $guard, private EnvironmentIsolation $isolation, private SyntheticWalletTesters $testers,
        private UatMoneySimulation $simulation) {}

    public function admitsInvestor(string $partyId): bool
    {
        return ! $this->onUat() || $this->testers->authorized($partyId);
    }

    public function admitsBusiness(): bool
    {
        return ! $this->onUat() || $this->simulation->active();
    }

    private function onUat(): bool
    {
        return $this->guard->allowed() && $this->isolation->profile() === 'uat';
    }
}
