<?php

declare(strict_types=1);

namespace App\Infrastructure\Business;

use App\Application\Business\Contracts\MonthlySubmissionStore;
use App\Application\Business\WithBusinessAuthority;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Application\Operations\Contracts\OperationJournal;
use App\Domain\Business\MonthlySubmission;
use App\Domain\Operations\CommandRejection;
use App\Domain\Operations\OperationResult;
use App\Models\BusinessMonthlySubmission;
use Closure;
use Illuminate\Support\Facades\DB;

/**
 * The monthly submission adapter. A submission is journaled under the acting Party against the
 * Business; its permitted input keeps only a digest of the figures. An advisory lock per business
 * and period means two sends never both land: the second is refused as already sent.
 *
 * @phpstan-import-type Stored from MonthlySubmissionStore
 */
final class EloquentMonthlySubmissionStore implements MonthlySubmissionStore
{
    public function __construct(private WithBusinessAuthority $authority, private OperationJournal $journal, private CanonicalJson $json) {}

    public function find(string $businessId, string $period): ?array
    {
        $record = BusinessMonthlySubmission::query()->where('business_id', $businessId)->where('period', $period)->first();

        return $record === null ? null : $this->stored($record);
    }

    public function forBusiness(string $businessId): array
    {
        $records = BusinessMonthlySubmission::query()->where('business_id', $businessId)->orderByDesc('period')->get()->all();

        return array_values(array_map(fn (BusinessMonthlySubmission $record): array => $this->stored($record), $records));
    }

    public function submit(int $userId, int $contextRevision, string $businessId, string $requestId, array $input, Closure $decide): array
    {
        return $this->authority->handle($userId, $contextRevision, $businessId, 'business.view', null,
            function (array $business, array $identity) use ($userId, $contextRevision, $businessId, $requestId, $input, $decide): array {
                $partyId = (string) ($identity['party']['id'] ?? throw new CommandRejection('BUSINESS_NOT_FOUND', 404));
                $id = (string) $business['id'];
                $permitted = ['identity_context_revision' => $contextRevision, 'submission_sha256' => hash('sha256', $this->json->encode($input))];

                return $this->journal->execute('party:'.$partyId, $userId, 'business.monthly_submission.send', $requestId, 'business_profile', $id, $permitted,
                    function () use ($userId, $contextRevision, $businessId): void {
                        $this->authority->handle($userId, $contextRevision, $businessId, 'business.view', null, fn (): bool => true);
                    },
                    function (string $operationId) use ($id, $partyId, $decide): OperationResult {
                        $decided = $decide($id);
                        DB::select('SELECT pg_advisory_xact_lock(hashtextextended(?, 0))', ['business-monthly-submission|'.$id.'|'.$decided['period']]);
                        if (BusinessMonthlySubmission::query()->where('business_id', $id)->where('period', $decided['period'])->exists()) {
                            throw new CommandRejection('MONTHLY_SUBMISSION_ALREADY_SENT', 409);
                        }
                        $figures = $decided['figures'];
                        $record = new BusinessMonthlySubmission;
                        $record->forceFill(['business_id' => $id, 'period' => $decided['period'], 'money_in' => $figures['money_in'], 'costs' => $figures['costs'],
                            'costs_total' => $figures['costs_total'], 'paid_to_owners' => $figures['paid_to_owners'], 'forecast_period' => $decided['forecast_period'],
                            'forecast' => $figures['forecast'], 'forecast_basis' => $figures['forecast_basis'], 'order' => $figures['order'],
                            'changes' => $figures['changes'], 'policy_version' => MonthlySubmission::POLICY_VERSION, 'submitted_by_party_id' => $partyId,
                            'operation_id' => $operationId, 'submitted_at' => now('UTC')])->save();

                        return new OperationResult('MONTHLY_SUBMISSION_SENT', ['business_id' => $id, 'period' => $decided['period']], 1);
                    });
            });
    }

    /**
     * A sent submission as the port returns it, its categories and changes in screen order.
     *
     * @return Stored
     */
    private function stored(BusinessMonthlySubmission $record): array
    {
        return ['period' => $record->period, 'forecast_period' => $record->forecast_period, 'figures' => [
            'money_in' => $record->money_in, 'costs' => array_merge(array_fill_keys(MonthlySubmission::COST_CATEGORIES, '0'), $record->costs),
            'costs_total' => $record->costs_total, 'paid_to_owners' => $record->paid_to_owners, 'forecast' => $record->forecast,
            'forecast_basis' => $record->forecast_basis, 'order' => $record->order,
            'changes' => array_merge(array_fill_keys(MonthlySubmission::CHANGES, false), $record->changes),
        ], 'submitted_at' => $record->submitted_at->setTimezone('UTC')->format('Y-m-d\TH:i:s\Z')];
    }
}
