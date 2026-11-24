<?php

declare(strict_types=1);

namespace App\Application\Environment;

use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Contracts\Config\Repository;

/**
 * Staging's simulated money after funding (Erastus, 14 Nov 2026): on UAT with live money off, a raise
 * that reaches 100% issues its notes and pays the Business into its linked synthetic account, the
 * Business repays from its wallet, Investors are paid, and resale is open, so every journey after
 * funding can be tested. Nothing real moves: the payout provider and the accounts are synthetic.
 * Every other profile (local and testing keep their own fixtures; demo and production) never
 * reaches it, whatever `app.uat_money_simulation` says. Within it, only the named synthetic Businesses
 * in `app.uat_money_simulation_businesses` are disbursed, repay and have their notes resold.
 */
final readonly class UatMoneySimulation
{
    public function __construct(private EnvironmentIsolation $isolation, private Repository $config) {}

    public function active(): bool
    {
        return $this->isolation->profile() === 'uat' && $this->config->get('isolation.live_money_enabled') === false
            && $this->config->get('app.uat_money_simulation') === true;
    }

    /** Whether the simulation is on and names this Business as one of its synthetic test Businesses. */
    public function covers(string $businessId): bool
    {
        $named = $this->config->get('app.uat_money_simulation_businesses');

        return $this->active() && is_array($named)
            && in_array(strtolower($businessId), array_map(fn (mixed $id): string => strtolower(trim((string) $id)), $named), true);
    }

    /**
     * Whether a demo audit's synthetic capture and statement month apply: a simulated Business's audit,
     * or a monthly audit `staging:open-monthly-audit` opened, even for a Business the simulation does not
     * name (an audit moves no money). Only that command opens a routine audit for the very month it was
     * dispatched in; the scheduler always audits the month before. An unreadable dispatch time is not one.
     *
     * @param  array{kind?: string, original_dispatch_at?: string, period?: string}  $engagement
     */
    public function coversAudit(string $businessId, array $engagement): bool
    {
        if ($this->covers($businessId)) {
            return true;
        }
        if (! $this->active() || ($engagement['kind'] ?? null) !== 'routine' || ! isset($engagement['period'], $engagement['original_dispatch_at'])) {
            return false;
        }
        try {
            $dispatched = CarbonImmutable::parse($engagement['original_dispatch_at'])->setTimezone('Africa/Kigali')->format('Y-m');
        } catch (InvalidFormatException) {
            return false;
        }

        return $engagement['period'] === $dispatched;
    }
}
