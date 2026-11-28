<?php

declare(strict_types=1);

namespace App\Infrastructure\Operations;

use App\Application\Business\Contracts\ServicingStatements;
use App\Application\Environment\UatMoneySimulation;
use App\Application\Operations\Contracts\OperationsDashboardStore;
use App\Application\Operations\Contracts\StaffServicingStore;
use Brick\Math\BigDecimal;
use Brick\Math\BigInteger;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\DB;

/** UAT statements are explicitly simulated. Missing coverage never becomes a zero arrears figure. */
final readonly class EloquentStaffServicingStore implements StaffServicingStore
{
    public function __construct(private OperationsDashboardStore $dashboard, private ServicingStatements $statements, private UatMoneySimulation $simulation) {}

    public function read(): array
    {
        return DB::transaction(function (): array {
            if (DB::transactionLevel() === 1) {
                DB::statement('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ READ ONLY');
            }
            $active = $this->simulation->active();
            $asOf = now()->toImmutable();
            $notes = [];
            $missing = 0;
            $principal = BigInteger::zero();
            $atRisk = BigInteger::zero();
            foreach ($this->dashboard->noteStates() as $note) {
                if ($note['state'] !== 'repaying') {
                    continue;
                }
                $statement = $this->statements->forNote($note['business_id'], $note['campaign_id'], $asOf);
                if ($statement === null) {
                    $missing++;
                    $notes[] = ['note_id' => $note['campaign_id'], 'business_id' => $note['business_id'], 'availability' => 'unavailable',
                        'reason' => $active && ! $this->simulation->covers($note['business_id']) ? 'outside_simulation_coverage' : 'servicing_unavailable',
                        'statement' => null, 'policy_stage_due' => null];

                    continue;
                }
                $outstanding = BigInteger::zero();
                foreach ($statement['schedule'] as $instalment) {
                    $outstanding = $outstanding->plus($instalment['outstanding']['principal']['amount']);
                }
                $dpd = $statement['servicing']['dpd'];
                $principal = $principal->plus($outstanding);
                if ($dpd !== null && $dpd > 30) {
                    $atRisk = $atRisk->plus($outstanding);
                }
                $notes[] = ['note_id' => $note['campaign_id'], 'business_id' => $note['business_id'], 'availability' => $active ? 'simulation' : 'recorded',
                    'reason' => null, 'statement' => $statement, 'principal_outstanding' => ['currency' => 'RWF', 'amount' => (string) $outstanding],
                    'policy_stage_due' => match (true) {
                        $dpd === null => null, $dpd >= 31 => 'default', $dpd >= 30 => 'legal_demand', $dpd >= 7 => 'escalation', default => 'late',
                    }];
            }
            $complete = $missing === 0 && $active;
            $measurable = $complete && ! $principal->isZero();

            return ['mode' => $active ? 'simulation' : 'unavailable', 'as_of' => $asOf->toIso8601String(), 'notes' => $notes,
                'coverage' => ['issued_notes' => count($notes), 'available_notes' => count($notes) - $missing, 'complete' => $complete],
                'par30' => ['state' => ! $complete ? 'unavailable' : ($principal->isZero() ? 'no_outstanding_principal' : 'simulation'),
                    'percent' => $measurable ? (string) BigDecimal::of($atRisk)->multipliedBy(100)->dividedBy($principal, 6, RoundingMode::HalfUp) : null,
                    'principal_outstanding' => $complete ? ['currency' => 'RWF', 'amount' => (string) $principal] : null,
                    'principal_at_risk' => $complete ? ['currency' => 'RWF', 'amount' => (string) $atRisk] : null,
                    'threshold_percent' => '1.5', 'threshold_exceeded' => $measurable ? $atRisk->multipliedBy(1000)->isGreaterThan($principal->multipliedBy(15)) : null,
                    'basis' => 'outstanding_principal_on_notes_more_than_30_kigali_days_late'],
                'pd' => ['state' => 'not_calibrated', 'value' => null],
                'unavailable_capabilities' => ['late_charges', 'collection_actions', 'automatic_collection', 'supervisor_alert_delivery']];
        });
    }
}
