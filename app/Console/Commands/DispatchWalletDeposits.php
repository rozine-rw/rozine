<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Application\Wallet\Contracts\SyntheticSettlement;
use App\Application\Wallet\DispatchBusinessDeposits;
use App\Application\Wallet\DispatchBusinessWithdrawals;
use App\Application\Wallet\DispatchDepositIntents;
use App\Application\Wallet\DispatchWithdrawals;
use Illuminate\Console\Command;

/**
 * The deposit and withdrawal outbox worker: sends committed Investor and Business deposit intents,
 * then Investor and Business withdrawals, that the request path did not reach, and resends an interrupted send only for a provider with safe idempotent sends. On UAT it
 * then settles the acknowledged synthetic deposits still without a final outcome, so a settlement
 * lost to a failure or a restart is recovered here. `--limit` bounds the run: at most that many
 * sends across the outboxes (Investor deposits, then Business deposits, then Investor and Business withdrawals), and at
 * most that many settlement attempts across them all (oldest first), the provider's after-response settlement when the run ends
 * included. The worker exits non-zero while anything is left for the next run: a dispatch still
 * waiting to be sent, or a synthetic deposit still unsettled. Local, testing and UAT only.
 */
class DispatchWalletDeposits extends Command
{
    protected $hidden = true;

    protected $signature = 'wallet:dispatch-deposits {--limit=50 : Maximum deposits to send, and to settle, in this run across Investor and Business (1-500)}';

    protected $description = 'Send committed synthetic deposit intents from the outbox and settle them on UAT (local, testing and UAT only)';

    public function handle(DispatchDepositIntents $dispatch, DispatchBusinessDeposits $business, DispatchWithdrawals $withdrawals,
        DispatchBusinessWithdrawals $businessWithdrawals, SyntheticSettlement $settlement): int
    {
        $limit = filter_var($this->option('limit'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 500]]);
        if ($limit === false) {
            $this->error('The limit must be an integer from 1 to 500.');

            return self::INVALID;
        }
        $settlement->limitRun($limit);
        $sent = $dispatch->handle(null, $limit);
        $this->info('Claimed '.$sent['claimed'].' deposit dispatches; '.$sent['acknowledged'].' acknowledged.');
        $allowance = max(0, $limit - $sent['claimed']);
        $sent = $allowance > 0 ? $business->handle(null, $allowance) : ['claimed' => 0, 'acknowledged' => 0];
        $this->info('Claimed '.$sent['claimed'].' Business deposit dispatches; '.$sent['acknowledged'].' acknowledged.');
        $allowance = max(0, $allowance - $sent['claimed']);
        $sent = $allowance > 0 ? $withdrawals->handle(null, $allowance) : ['claimed' => 0, 'acknowledged' => 0];
        $this->info('Claimed '.$sent['claimed'].' withdrawal dispatches; '.$sent['acknowledged'].' acknowledged.');
        $allowance = max(0, $allowance - $sent['claimed']);
        $sent = $allowance > 0 ? $businessWithdrawals->handle(null, $allowance) : ['claimed' => 0, 'acknowledged' => 0];
        $this->info('Claimed '.$sent['claimed'].' Business withdrawal dispatches; '.$sent['acknowledged'].' acknowledged.');
        $unsent = $dispatch->backlog() + $business->backlog() + $withdrawals->backlog() + $businessWithdrawals->backlog();
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
