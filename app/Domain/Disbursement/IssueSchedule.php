<?php

declare(strict_types=1);

namespace App\Domain\Disbursement;

use DateTimeImmutable;
use DateTimeZone;
use Exception;

/**
 * The contractual dates a Holding is issued with (§11.4). The anchor is the Africa/Kigali calendar
 * date of the authenticated disbursement effective instant, never a callback's arrival or the
 * local issue commit. Each due date is counted from the original anchor day and clamped to the
 * target month's last day: January 31 gives February 28 or 29, then March 31.
 */
final readonly class IssueSchedule
{
    public const string ZONE = 'Africa/Kigali';

    /** @param list<string> $dueDates */
    private function __construct(public string $effectiveDate, public array $dueDates) {}

    public static function dates(string $effectiveAt, int $termMonths): self
    {
        if ($termMonths < 1 || $termMonths > 120
            || preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(\.\d{1,6})?(Z|[+-]\d{2}:\d{2})$/D', $effectiveAt) !== 1) {
            throw new DisbursementViolation('ISSUE_SCHEDULE_INPUT_INVALID');
        }
        try {
            $instant = new DateTimeImmutable($effectiveAt);
        } catch (Exception) {
            throw new DisbursementViolation('ISSUE_SCHEDULE_INPUT_INVALID');
        }
        if ($instant->format('Y-m-d\TH:i:s') !== substr($effectiveAt, 0, 19)) {
            throw new DisbursementViolation('ISSUE_SCHEDULE_INPUT_INVALID');
        }
        $local = $instant->setTimezone(new DateTimeZone(self::ZONE));
        $year = (int) $local->format('Y');
        $month = (int) $local->format('n');
        $day = (int) $local->format('j');
        $dueDates = [];
        for ($index = 1; $index <= $termMonths; $index++) {
            $offset = $month - 1 + $index;
            $targetYear = $year + intdiv($offset, 12);
            $targetMonth = $offset % 12 + 1;
            $first = new DateTimeImmutable(sprintf('%04d-%02d-01T00:00:00', $targetYear, $targetMonth), new DateTimeZone(self::ZONE));
            $dueDates[] = sprintf('%04d-%02d-%02d', $targetYear, $targetMonth, min($day, (int) $first->format('t')));
        }

        return new self($local->format('Y-m-d'), $dueDates);
    }
}
