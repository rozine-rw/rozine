<?php

declare(strict_types=1);

namespace App\Domain\Primary;

use DateInterval;
use DateTimeImmutable;
use DateTimeZone;

final readonly class ReservationWindow
{
    private function __construct(public DateTimeImmutable $startsAt, public DateTimeImmutable $expiresAt) {}

    public static function open(DateTimeImmutable $createdAt, DateTimeImmutable $campaignExpiresAt): self
    {
        if ($createdAt >= $campaignExpiresAt) {
            throw new PrimaryViolation('CAMPAIGN_CLOSED');
        }
        $start = $createdAt->setTimezone(new DateTimeZone('UTC'));
        $deadline = $campaignExpiresAt->setTimezone(new DateTimeZone('UTC'));
        $fiveMinutes = $start->add(new DateInterval('PT300S'));

        return new self($start, min($fiveMinutes, $deadline));
    }

    public function isExpired(DateTimeImmutable $at): bool
    {
        return $at >= $this->expiresAt;
    }

    public function requireOpen(DateTimeImmutable $at): void
    {
        if ($this->isExpired($at)) {
            throw new PrimaryViolation('RESERVATION_EXPIRED');
        }
        if ($at < $this->startsAt) {
            throw new PrimaryViolation('RESERVATION_NOT_STARTED');
        }
    }
}
