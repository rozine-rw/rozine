<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Application\Wallet\DispatchDepositIntents;
use Illuminate\Console\Command;

/**
 * The deposit outbox worker: sends committed intents the request path did not reach, and resends an
 * interrupted send only for a provider with safe idempotent sends. Local and testing only.
 */
class DispatchWalletDeposits extends Command
{
    protected $hidden = true;

    protected $signature = 'wallet:dispatch-deposits {--limit=50 : Maximum outbox rows to send (1-500)}';

    protected $description = 'Send committed synthetic deposit intents from the outbox (local and testing only)';

    public function handle(DispatchDepositIntents $dispatch): int
    {
        $limit = filter_var($this->option('limit'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 500]]);
        if ($limit === false) {
            $this->error('The limit must be an integer from 1 to 500.');

            return self::INVALID;
        }
        $sent = $dispatch->handle(null, $limit);
        $this->info('Claimed '.$sent['claimed'].' deposit dispatches; '.$sent['acknowledged'].' acknowledged.');

        return self::SUCCESS;
    }
}
