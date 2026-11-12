<?php

declare(strict_types=1);

namespace App\Application\Secondary\Contracts;

use DateTimeImmutable;

/**
 * Returns the held cash of every reservation that lapsed after five minutes (SEC-R05) and records
 * every listing past its seven days as expired (SEC-R02). It runs whether trading is on or off, so
 * nothing reserved before trading was switched off stays held.
 */
interface SecondaryMarketSweep
{
    /** @return array{released: int, expired: int} */
    public function sweep(DateTimeImmutable $at): array;
}
