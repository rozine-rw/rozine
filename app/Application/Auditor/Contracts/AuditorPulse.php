<?php

declare(strict_types=1);

namespace App\Application\Auditor\Contracts;

/**
 * A short fingerprint of everything the Auditor home shows (Erastus, 24 Nov 2026: the home is live
 * and picks up audits in the partner's districts by itself). It changes when an audit is offered to,
 * accepted by or taken from the partner, when one of their reports moves, when their profile or
 * coverage changes and when a notice arrives, so the page asks cheaply and reloads only when it
 * moved. It reveals nothing but that something changed.
 */
interface AuditorPulse
{
    public function version(int $userId): string;
}
