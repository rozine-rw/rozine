<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Infrastructure\Secondary\EloquentSecondaryMarket;
use Illuminate\Console\Command;

/**
 * Returns the held cash of every resale reservation that lapsed after five minutes (SEC-R05) and
 * records every listing past its seven days as expired (SEC-R02). It runs whether trading is on or
 * off, so nothing reserved before trading was switched off stays held.
 */
class ExpireSecondaryMarket extends Command
{
    protected $signature = 'secondary:expire';

    protected $description = 'Release lapsed resale reservations and expire week-old listings';

    public function handle(EloquentSecondaryMarket $market): int
    {
        ['released' => $released, 'expired' => $expired] = $market->sweep(now('UTC')->toDateTimeImmutable());
        $this->info("Released {$released} reservations and expired {$expired} listings.");

        return self::SUCCESS;
    }
}
