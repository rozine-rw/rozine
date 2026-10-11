<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Application\Wallet\Contracts\SyntheticSettlement;
use App\Application\Wallet\DispatchBusinessDeposits;
use App\Application\Wallet\DispatchDepositIntents;
use Illuminate\Console\Command;

/**
 * The deposit outbox worker: sends committed Investor and Business intents the request path did not
 * reach, and resends an interrupted send only for a provider with safe idempotent sends. On UAT it
 * then settles every acknowledged synthetic deposit still without a final outcome, so a settlement
 * lost to a failure or a restart is recovered here. Local, testing and UAT only.
 */
class DispatchWalletDeposits extends Command
{
    protected $hidden = true;

    protected $signature = 'wallet:dispatch-deposits {--limit=50 : Maximum outbox rows to send (1-500)}';

    protected $description = 'Send committed synthetic deposit intents from the outbox and settle them on UAT (local, testing and UAT only)';

    public function handle(DispatchDepositIntents $dispatch, DispatchBusinessDeposits $business, SyntheticSettlement $settlement): int
    {
        $limit = filter_var($this->option('limit'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 500]]);
        if ($limit === false) {
            $this->error('The limit must be an integer from 1 to 500.');

            return self::INVALID;
        }
        $sent = $dispatch->handle(null, $limit);
        $this->info('Claimed '.$sent['claimed'].' deposit dispatches; '.$sent['acknowledged'].' acknowledged.');
        $sent = $business->handle(null, $limit);
        $this->info('Claimed '.$sent['claimed'].' Business deposit dispatches; '.$sent['acknowledged'].' acknowledged.');
        if ($settlement->enabled()) {
            $settled = $settlement->settlePending($limit);
            $this->info('Settled '.$settled['settled'].' synthetic deposits; '.$settled['failed'].' left for the next run.');

            return $settled['failed'] === 0 ? self::SUCCESS : self::FAILURE;
        }

        return self::SUCCESS;
    }
}
