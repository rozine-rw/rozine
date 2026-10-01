<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Application\Disbursement\OpenFundedDisbursements;
use App\Application\Disbursement\ReconcileDisbursements;
use App\Application\Disbursement\SyntheticDisbursementGuard;
use App\Domain\Operations\CommandRejection;
use Illuminate\Console\Command;

/**
 * The scheduled disbursement reconciler. It opens a disbursement for each newly funded campaign,
 * then queries every dispatched payout's same operation and reconciles it; it never sends. Where
 * no payout provider or funding source exists it reports that and does nothing.
 */
class ReconcileDisbursementPayouts extends Command
{
    protected $signature = 'disbursements:reconcile {--limit=25 : Maximum payouts to query (1-500)}';

    protected $description = 'Open funded disbursements and reconcile dispatched payouts without resending';

    public function handle(OpenFundedDisbursements $open, ReconcileDisbursements $reconcile, SyntheticDisbursementGuard $guard): int
    {
        $limit = filter_var($this->option('limit'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 500]]);
        if ($limit === false) {
            $this->error('The limit must be an integer from 1 to 500.');

            return self::INVALID;
        }
        if (! $guard->allowed()) {
            $this->line('Disbursement reconciliation is unavailable here: no funding source or payout provider is configured.');

            return self::SUCCESS;
        }
        try {
            $opened = $open->handle($limit);
            $this->line('Opened '.$opened['opened'].' funded disbursements ('.$opened['known'].' already open).');
        } catch (CommandRejection $rejection) {
            $this->line('Funding source: '.$rejection->reason);
        }
        $result = $reconcile->handle(null, $limit);
        ksort($result['decisions']);
        $this->info('Queried '.$result['queried'].' payouts; '.$result['observed'].' answered. Decisions: '
            .($result['decisions'] === [] ? 'none' : implode(', ', array_map(fn (string $decision, int $count): string => $decision.' '.$count,
                array_keys($result['decisions']), $result['decisions']))).'.');

        return self::SUCCESS;
    }
}
