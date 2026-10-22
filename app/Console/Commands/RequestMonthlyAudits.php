<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Application\Auditor\RequestMonthlyAudits as MonthlyAudits;
use Illuminate\Console\Command;

class RequestMonthlyAudits extends Command
{
    /** @var string */
    protected $signature = 'audits:request-monthly';

    /** @var string */
    protected $description = 'Dispatch the monthly audit of every funded business for the month just ended';

    public function handle(MonthlyAudits $audits): int
    {
        $this->info('Requested '.$audits->handle(now()->toDateTimeImmutable()).' monthly audits.');

        return self::SUCCESS;
    }
}
