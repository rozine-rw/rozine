<?php

declare(strict_types=1);

namespace App\Domain\Auditor;

use App\Domain\Operations\CommandRejection;
use DateTimeImmutable;
use DateTimeZone;

final class AuditAssignmentClock
{
    /** @return array{accept_by: DateTimeImmutable|null, complete_by: DateTimeImmutable|null, operations_required: bool} */
    public function offer(string $kind, DateTimeImmutable $originalDispatch, DateTimeImmutable $offeredAt, int $attempt): array
    {
        $originalDispatch = $originalDispatch->setTimezone(new DateTimeZone('UTC'));
        $offeredAt = $offeredAt->setTimezone(new DateTimeZone('UTC'));
        if (! in_array($kind, ['flash', 'routine'], true) || $attempt < 1 || $offeredAt < $originalDispatch) {
            throw new CommandRejection('AUDIT_CLOCK_INVALID', 422);
        }
        $completeBy = $kind === 'flash' ? $originalDispatch->modify('+24 hours') : null;
        if ($attempt > 3 || ($completeBy !== null && $offeredAt >= $completeBy)) {
            return ['accept_by' => null, 'complete_by' => $completeBy, 'operations_required' => true];
        }
        $acceptBy = $offeredAt->modify($kind === 'flash' ? '+1 hour' : '+4 hours');
        if ($completeBy !== null && $acceptBy > $completeBy) {
            $acceptBy = $completeBy;
        }

        return ['accept_by' => $acceptBy, 'complete_by' => $completeBy, 'operations_required' => false];
    }

    /** @return array{visit_by: DateTimeImmutable|null, complete_by: DateTimeImmutable|null} */
    public function accept(string $kind, DateTimeImmutable $originalDispatch, DateTimeImmutable $offeredAt, int $attempt, DateTimeImmutable $acceptedAt): array
    {
        $acceptedAt = $acceptedAt->setTimezone(new DateTimeZone('UTC'));
        $offer = $this->offer($kind, $originalDispatch, $offeredAt, $attempt);
        if ($acceptedAt < $offeredAt || $offer['accept_by'] === null || $acceptedAt >= $offer['accept_by']) {
            throw new CommandRejection('ASSIGNMENT_ACCEPTANCE_EXPIRED', 409);
        }

        return ['visit_by' => $kind === 'routine' ? $acceptedAt->modify('+48 hours') : null, 'complete_by' => $offer['complete_by']];
    }
}
