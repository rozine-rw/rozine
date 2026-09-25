<?php

declare(strict_types=1);

namespace App\Domain\Auditor;

use App\Domain\Operations\CommandRejection;
use DateTimeImmutable;
use DateTimeZone;

final class AuditReportWindow
{
    public function dueAt(?string $period): ?string
    {
        if ($period === null) {
            return null;
        }
        $month = DateTimeImmutable::createFromFormat('!Y-m', $period, new DateTimeZone('Africa/Kigali'));
        if ($month === false || $month->format('Y-m') !== $period) {
            throw new CommandRejection('AUDIT_PERIOD_INVALID', 422);
        }

        return $month->modify('+1 month +6 days')->setTime(23, 59, 59)->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z');
    }

    public function mayPublish(?string $period, DateTimeImmutable $now): bool
    {
        $due = $this->dueAt($period);
        if ($due === null) {
            return true;
        }
        $deadline = new DateTimeImmutable($due);
        $opens = $deadline->setTimezone(new DateTimeZone('Africa/Kigali'))->modify('first day of this month')->setTime(0, 0);

        return $now >= $opens && $now < $deadline->modify('+1 second');
    }
}
