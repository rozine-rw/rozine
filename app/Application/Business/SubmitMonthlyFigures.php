<?php

declare(strict_types=1);

namespace App\Application\Business;

use App\Application\Auditor\Contracts\PublishedMonthlyReports;
use App\Application\Business\Contracts\BusinessAuditWindows;
use App\Application\Business\Contracts\MonthlySubmissionStore;
use App\Domain\Business\MonthlySubmission;
use App\Domain\Business\ReadinessStage;
use App\Domain\Operations\CommandRejection;
use DateTimeImmutable;
use DateTimeZone;

/**
 * `business.monthly_submission.send` (BRS v3.0 VIS-R04): the Business declares its figures for the
 * month its open monthly audit covers, before the Audit Partner verifies them. It can do so only
 * while that audit's window is open, once per month. The forecast is for the month after, checked
 * against the verified 3-month average it was pre-filled with. The form names the month it was
 * opened for, so a form left open past a window's rollover is refused instead of being recorded
 * against the new month.
 */
final readonly class SubmitMonthlyFigures
{
    public function __construct(private MonthlySubmissionStore $submissions, private BusinessAuditWindows $windows, private PublishedMonthlyReports $reports) {}

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function handle(int $userId, int $contextRevision, string $businessId, string $requestId, array $input): array
    {
        return $this->submissions->submit($userId, $contextRevision, $businessId, $requestId, $input, function (string $id) use ($input): array {
            $window = $this->windows->upcoming($id, now()->toDateTimeImmutable()) ?? throw new CommandRejection('MONTHLY_SUBMISSION_CLOSED', 409);
            if (($input['period'] ?? null) !== $window['period']) {
                throw new CommandRejection('MONTHLY_SUBMISSION_PERIOD_STALE', 409);
            }
            $basis = MonthlySubmission::forecastBasis(ReadinessStage::months($this->reports->forBusiness($id)));

            $period = substr($window['period'], 0, 7);

            return ['period' => $period, 'forecast_period' => self::nextMonth($period), 'figures' => MonthlySubmission::figures($input, $basis)];
        });
    }

    /** The `YYYY-MM` month after `$period` (`YYYY-MM`). */
    public static function nextMonth(string $period): string
    {
        return (new DateTimeImmutable($period.'-01', new DateTimeZone('Africa/Kigali')))->modify('+1 month')->format('Y-m');
    }
}
