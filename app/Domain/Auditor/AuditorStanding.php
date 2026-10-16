<?php

declare(strict_types=1);

namespace App\Domain\Auditor;

use App\Domain\Operations\CommandRejection;
use DateTimeImmutable;
use DateTimeZone;

/**
 * Standing is established by an authorized, evidenced staff check; a submitted certificate alone
 * never supplies these facts. Dispatch and co-signature must recheck the current record.
 *
 * @phpstan-type Standing array{status: string, licence: string|null, expires_on: string|null, checked_at: string|null, check_reference: string|null}
 */
final class AuditorStanding
{
    /** @param Standing $standing */
    public function requireCurrent(array $standing, DateTimeImmutable $now): void
    {
        $now = $now->setTimezone(new DateTimeZone('UTC'));
        if ($standing['status'] === 'suspended' || $standing['status'] === 'revoked') {
            throw new CommandRejection('ACCREDITATION_SUSPENDED', 403);
        }
        if ($standing['status'] !== 'active' || $standing['licence'] === null || trim($standing['licence']) === '') {
            throw new CommandRejection('ACCREDITATION_REQUIRED', 403);
        }
        $expiry = $standing['expires_on'] === null ? false : DateTimeImmutable::createFromFormat('!Y-m-d', $standing['expires_on']);
        if ($expiry === false || $expiry->format('Y-m-d') !== $standing['expires_on']
            || $standing['expires_on'] < $now->setTimezone(new DateTimeZone('Africa/Kigali'))->format('Y-m-d')) {
            throw new CommandRejection('ACCREDITATION_EXPIRED', 403);
        }
        $checked = $standing['checked_at'] === null ? false : DateTimeImmutable::createFromFormat('!Y-m-d\TH:i:s\Z', $standing['checked_at'], new DateTimeZone('UTC'));
        if ($checked === false || $checked->format('Y-m-d\TH:i:s\Z') !== $standing['checked_at']
            || $checked > $now || $checked < $now->modify('-30 days')
            || $standing['check_reference'] === null || trim($standing['check_reference']) === '') {
            throw new CommandRejection('STANDING_CHECK_REQUIRED', 403);
        }
    }
}
