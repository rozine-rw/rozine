<?php

declare(strict_types=1);

namespace App\Application\Business;

use App\Application\Auditor\Contracts\PublishedMonthlyReports;
use App\Application\Business\Contracts\BusinessAuditWindows;
use App\Application\Business\Contracts\MonthlySubmissionStore;
use App\Domain\Business\MonthlySubmission;
use App\Domain\Business\ReadinessStage;

/**
 * The monthly submission page (BRS v3.0 VIS-R04) for a current mandate holder: the month the open
 * monthly audit covers, the forecast's pre-fill, and what was sent for that month if anything. With
 * no open monthly audit there is nothing to submit, and `window` is null.
 *
 * @phpstan-import-type AuditWindow from BusinessAuditWindows
 * @phpstan-import-type Stored from MonthlySubmissionStore
 *
 * @phpstan-type Page array{identity_context_revision: int, business_id: string, window: AuditWindow|null, forecast_period: string|null, forecast_basis: string|null, cost_categories: list<string>, submission: Stored|null}
 */
final readonly class GetMonthlyFigures
{
    public function __construct(private WithBusinessAuthority $authority, private BusinessAuditWindows $windows, private PublishedMonthlyReports $reports,
        private MonthlySubmissionStore $submissions) {}

    /** @return Page */
    public function handle(int $userId, int $contextRevision, string $businessId): array
    {
        $id = $this->authority->handle($userId, $contextRevision, $businessId, 'business.view', null, fn (array $business): string => (string) $business['id']);
        $window = $this->windows->upcoming($id, now()->toDateTimeImmutable());
        $period = $window === null ? null : substr($window['period'], 0, 7);

        return ['identity_context_revision' => $contextRevision, 'business_id' => $id, 'window' => $window,
            'forecast_period' => $period === null ? null : SubmitMonthlyFigures::nextMonth($period),
            'forecast_basis' => MonthlySubmission::forecastBasis(ReadinessStage::months($this->reports->forBusiness($id))),
            'cost_categories' => MonthlySubmission::COST_CATEGORIES,
            'submission' => $period === null ? null : $this->submissions->find($id, $period)];
    }
}
