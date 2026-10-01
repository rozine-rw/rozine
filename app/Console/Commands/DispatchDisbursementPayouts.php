<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Application\Disbursement\DispatchDisbursements;
use Illuminate\Console\Command;
use LogicException;

/**
 * The payout outbox worker: claims queued intents after a fresh locked recheck and sends them
 * outside every transaction. Local and testing only; elsewhere no payout provider exists.
 */
class DispatchDisbursementPayouts extends Command
{
    protected $hidden = true;

    protected $signature = 'disbursements:dispatch {--intent= : Only this intent} {--limit=25 : Maximum intents to claim (1-500)}';

    protected $description = 'Send recorded synthetic payout intents from the outbox (local and testing only)';

    public function handle(DispatchDisbursements $dispatch): int
    {
        $limit = filter_var($this->option('limit'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 500]]);
        if ($limit === false) {
            $this->error('The limit must be an integer from 1 to 500.');

            return self::INVALID;
        }
        try {
            $sent = $dispatch->handle(is_string($this->option('intent')) ? $this->option('intent') : null, $limit);
        } catch (LogicException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }
        $this->info('Claimed '.$sent['claimed'].' payout dispatches; '.$sent['sent'].' acknowledged.');

        return self::SUCCESS;
    }
}
