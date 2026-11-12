<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Application\Business\EvaluateBusinessReadiness;
use Illuminate\Console\Command;

class EvaluateReadiness extends Command
{
    /** @var string */
    protected $signature = 'readiness:evaluate';

    /** @var string */
    protected $description = 'Settle each Business\'s readiness stage, trigger its requirements and turn them Overdue on the exact Kigali day';

    public function handle(EvaluateBusinessReadiness $readiness): int
    {
        $this->info('Evaluated readiness for '.$readiness->handle().' businesses.');

        return self::SUCCESS;
    }
}
