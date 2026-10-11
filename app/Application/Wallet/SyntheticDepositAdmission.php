<?php

declare(strict_types=1);

namespace App\Application\Wallet;

use App\Application\Environment\EnvironmentIsolation;
use App\Application\Wallet\Contracts\SyntheticWalletTesters;

/**
 * Who may start a new synthetic deposit. On UAT only a named wallet tester may (asked again for every
 * deposit, beside the Investor role the deposit already requires), and new Business deposits stay off
 * until they are approved on their own; local and testing keep their fixtures open. It decides new
 * intents only: a deposit already accepted still settles to its outcome.
 */
final class SyntheticDepositAdmission
{
    public function __construct(private SyntheticWalletGuard $guard, private EnvironmentIsolation $isolation, private SyntheticWalletTesters $testers) {}

    public function admitsInvestor(string $partyId): bool
    {
        return ! $this->onUat() || $this->testers->authorized($partyId);
    }

    public function admitsBusiness(): bool
    {
        return ! $this->onUat();
    }

    private function onUat(): bool
    {
        return $this->guard->allowed() && $this->isolation->profile() === 'uat';
    }
}
