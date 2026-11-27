<?php

declare(strict_types=1);

namespace App\Application\Operations;

use App\Application\Operations\Contracts\ReconciliationStore;
use App\Application\Operations\Contracts\ReconciliationVariances;
use App\Domain\Operations\ReconciliationBalances;
use Carbon\CarbonImmutable;

/** Record the previous Kigali day's exact cutoff, without posting or repairing any money. */
final readonly class RecordReconciliationDay
{
    public function __construct(private ReconciliationStore $source, private ReconciliationVariances $variances) {}

    /** @return array{state: 'available'|'unavailable', recorded: int} */
    public function handle(): array
    {
        $cutoff = CarbonImmutable::now('Africa/Kigali')->subDay()->endOfDay();
        $accounts = $this->source->accounts($cutoff);
        if (! ReconciliationBalances::complete($accounts)) {
            return ['state' => 'unavailable', 'recorded' => 0];
        }

        return ['state' => 'available', 'recorded' => $this->variances->record($cutoff->toDateString(), $cutoff, $accounts)];
    }
}
