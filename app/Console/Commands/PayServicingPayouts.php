<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Application\Business\Contracts\ServicingPayouts;
use Illuminate\Console\Command;
use Throwable;

/**
 * Pays on every recorded repayment not yet paid to its note's holders, oldest first. A repayment is
 * normally paid on as soon as it commits; this catches one whose payout failed. Where no servicing
 * pays Investors it finds nothing.
 */
class PayServicingPayouts extends Command
{
    protected $signature = 'servicing:payouts {--limit=25 : Maximum repayments to pay on (1-500)}';

    protected $description = 'Pay recorded repayments on to the note holders';

    public function handle(ServicingPayouts $payouts): int
    {
        $limit = filter_var($this->option('limit'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 500]]);
        if ($limit === false) {
            $this->error('The limit must be an integer from 1 to 500.');

            return self::INVALID;
        }
        $repayments = 0;
        $lots = 0;
        $failed = 0;
        foreach ($payouts->unpaid($limit) as $repaymentId) {
            try {
                $lots += $payouts->payOut($repaymentId);
                $repayments++;
            } catch (Throwable $exception) {
                report($exception);
                $failed++;
            }
        }
        $this->info("Paid on {$repayments} repayments to {$lots} lots; {$failed} failed.");

        return self::SUCCESS;
    }
}
