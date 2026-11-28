<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Application\Operations\RecordReconciliationDay as RecordDay;
use Illuminate\Console\Command;

class RecordReconciliationDay extends Command
{
    protected $signature = 'reconciliation:record-day';

    protected $description = 'Record clearing-account variances at the previous Kigali day cutoff without changing money';

    public function handle(RecordDay $record): int
    {
        $result = $record->handle();
        $this->info($result['state'] === 'unavailable' ? 'Statement coverage is unavailable; the day is not reconciled.'
            : 'Recorded '.$result['recorded'].' new reconciliation variance cases.');

        return self::SUCCESS;
    }
}
