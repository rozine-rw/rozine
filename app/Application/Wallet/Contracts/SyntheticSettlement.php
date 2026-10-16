<?php

declare(strict_types=1);

namespace App\Application\Wallet\Contracts;

/**
 * Staging's stand-in for a provider's callbacks. On UAT with live money off, a synthetic deposit or
 * withdrawal the provider acknowledged settles through a signed provider event, verified and applied
 * once like any other. Settlement is recoverable: whatever is still unsettled is found from the recorded
 * acknowledgements, not from memory, and each deposit always gets the same event, so a retry replays
 * instead of crediting twice. Everywhere else it does nothing; local and testing keep the manual
 * `local:wallet --event` hook.
 */
interface SyntheticSettlement
{
    /** Whether synthetic deposits settle by themselves here (UAT with live money off). */
    public function enabled(): bool;

    /**
     * After the response is sent, waits a few seconds, then settles everything still unsettled: at
     * most one wait per application lifetime, and none when nothing is left to settle or the run's
     * allowance is spent.
     */
    public function scheduleAfterResponse(): void;

    /**
     * Bounds every settlement attempt for the rest of this application's lifetime, the after-response
     * one at termination included, to $limit in all. The worker sets it from its limit; a web request
     * never does, so its after-response settlement keeps its own batch.
     */
    public function limitRun(int $limit): void;

    /**
     * Settles at most $limit acknowledged synthetic deposits and withdrawals that have no final
     * outcome yet, Investor and Business together, oldest first, and never more than the run's allowance has left. A
     * failure is reported and left for the next run; `remaining` counts the deposits still unsettled
     * afterwards, failures included.
     *
     * @return array{settled: int, failed: int, remaining: int}
     */
    public function settlePending(int $limit = 50): array;
}
