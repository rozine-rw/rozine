<?php

declare(strict_types=1);

namespace App\Infrastructure\Operations;

use App\Application\Operations\Contracts\ReconciliationStore;
use App\Application\Operations\Contracts\ReconciliationVarianceAlerts;
use App\Application\Operations\Contracts\ReconciliationVariances;
use Brick\Math\BigInteger;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * The unique account/date key deduplicates concurrent recorders as well as ordinary replays.
 * A later match never erases a case: resolution requires its own recorded decision (#813).
 *
 * @phpstan-import-type Account from ReconciliationStore
 */
final readonly class EloquentReconciliationVariances implements ReconciliationVariances
{
    public function __construct(private ReconciliationVarianceAlerts $alerts) {}

    public function record(string $businessDate, DateTimeInterface $cutoff, array $accounts): int
    {
        $day = CarbonImmutable::instance($cutoff)->setTimezone('Africa/Kigali');
        if ($day->toDateString() !== $businessDate || ! $day->equalTo($day->endOfDay())) {
            throw new RuntimeException('RECONCILIATION_CUTOFF_INVALID');
        }

        return DB::transaction(function () use ($businessDate, $cutoff, $accounts): int {
            $recorded = 0;
            foreach ($accounts as $account) {
                $difference = BigInteger::of($account['ledger'])->minus($account['statement']);
                if ($difference->isZero()) {
                    continue;
                }
                $reference = $account['statement_reference'] ?? '';
                if (trim($reference) === '') {
                    throw new RuntimeException('RECONCILIATION_STATEMENT_REFERENCE_REQUIRED');
                }
                $case = ['id' => strtolower((string) Str::ulid()), 'account_id' => $account['id'], 'business_date' => $businessDate,
                    'cutoff_at' => $cutoff->format('Y-m-d H:i:s.uP'), 'ledger' => $account['ledger'], 'statement' => $account['statement'],
                    'difference' => (string) $difference, 'statement_reference' => $reference, 'created_at' => now('UTC')->format('Y-m-d H:i:s.uP')];
                if (DB::table('reconciliation_variances')->insertOrIgnore([...$case,
                    'statement_source' => 'authoritative_statement', 'alert_audiences' => json_encode(['treasury', 'compliance', 'cma'], JSON_THROW_ON_ERROR)]) !== 1) {
                    $retained = DB::table('reconciliation_variances')->where('business_date', $businessDate)->where('account_id', $account['id'])->sole();
                    if (! CarbonImmutable::parse((string) $retained->cutoff_at)->equalTo(CarbonImmutable::instance($cutoff))
                        || [(string) $retained->ledger, (string) $retained->statement, (string) $retained->difference, (string) $retained->statement_reference]
                        !== [$case['ledger'], $case['statement'], $case['difference'], $reference]) {
                        throw new RuntimeException('RECONCILIATION_CUTOFF_CHANGED: retained evidence requires a recorded resolution, never replacement.');
                    }

                    continue;
                }
                $recorded++;
                DB::afterCommit(fn () => $this->alerts->opened($case));
            }

            return $recorded;
        });
    }
}
