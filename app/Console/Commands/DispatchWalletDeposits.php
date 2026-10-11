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
 * then settles the acknowledged synthetic deposits still without a final outcome, so a settlement
 * lost to a failure or a restart is recovered here. `--limit` bounds the run: at most that many
 * sends across both outboxes (Investor ones first), and at most that many settlements across both
 * owners (oldest first). The worker exits non-zero while anything is left for the next run: a
 * dispatch still waiting to be sent, or a synthetic deposit still unsettled. Local, testing and UAT
 * only.
 */
class DispatchWalletDeposits extends Command
{
    protected $hidden = true;

    protected $signature = 'wallet:dispatch-deposits {--limit=50 : Maximum deposits to send, and to settle, in this run across Investor and Business (1-500)}';

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
        $allowance = max(0, $limit - $sent['claimed']);
        $sent = $allowance > 0 ? $business->handle(null, $allowance) : ['claimed' => 0, 'acknowledged' => 0];
        $this->info('Claimed '.$sent['claimed'].' Business deposit dispatches; '.$sent['acknowledged'].' acknowledged.');
        $unsent = $dispatch->backlog() + $business->backlog();
        if ($unsent > 0) {
            $this->warn($unsent.' deposit dispatches left for the next run.');
        }
        if ($settlement->enabled()) {
            $settled = $settlement->settlePending($limit);
            $this->info('Settled '.$settled['settled'].' synthetic deposits; '.$settled['remaining'].' left for the next run.');
            if ($settled['failed'] > 0 || $settled['remaining'] > 0) {
                return self::FAILURE;
            }
        }

        return $unsent === 0 ? self::SUCCESS : self::FAILURE;
    }
}
