<?php

declare(strict_types=1);

namespace App\Domain\Auditor;

use App\Domain\Operations\CommandRejection;
use DateTimeImmutable;
use DateTimeZone;

/**
 * @phpstan-import-type Point from Wgs84Distance
 *
 * @phpstan-type State array{point: Point|null, verified_at: string|null, uncertainty_m: int|null, moved_at: string|null, evidence_reference: string|null}
 */
final class VerifiedAuditLocation
{
    public function __construct(private Wgs84Distance $geodesic) {}

    /** @return State */
    public function empty(): array
    {
        return ['point' => null, 'verified_at' => null, 'uncertainty_m' => null, 'moved_at' => null, 'evidence_reference' => null];
    }

    /**
     * @param  State  $state
     * @return State
     */
    public function verify(array $state, string $latitude, string $longitude, int $uncertainty, string $verifiedAt, string $reference, string $reason, DateTimeImmutable $now): array
    {
        $point = $this->geodesic->point($latitude, $longitude);
        $checked = $this->timestamp($verifiedAt, $now);
        if ($uncertainty < 0 || $uncertainty > 30000 || $checked < $now->modify('-365 days')
            || ($state['moved_at'] !== null && $verifiedAt <= $state['moved_at'])) {
            throw new CommandRejection('AUDIT_LOCATION_REVIEW_REQUIRED', 422);
        }
        $this->text($reference, 255);
        $this->text($reason, 2000);

        return ['point' => $point, 'verified_at' => $verifiedAt, 'uncertainty_m' => $uncertainty,
            'moved_at' => $state['moved_at'], 'evidence_reference' => $reference];
    }

    /**
     * Clearing the current location also invalidates a verification recorded in the same second.
     * Prior coordinates and evidence remain exclusively in the immutable history.
     *
     * @param  State  $state
     * @return State
     */
    public function moved(array $state, string $movedAt, string $reason, DateTimeImmutable $now): array
    {
        $this->timestamp($movedAt, $now);
        $this->text($reason, 2000);
        if ($state['moved_at'] !== null && $movedAt < $state['moved_at']) {
            throw new CommandRejection('AUDIT_LOCATION_MOVE_STALE', 422);
        }

        return [...$this->empty(), 'moved_at' => $movedAt];
    }

    private function timestamp(string $value, DateTimeImmutable $now): DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i:s\Z', $value, new DateTimeZone('UTC'));
        if ($date === false || $date->format('Y-m-d\TH:i:s\Z') !== $value || $date > $now) {
            throw new CommandRejection('AUDIT_LOCATION_TIME_INVALID', 422);
        }

        return $date;
    }

    private function text(string $value, int $limit): void
    {
        if (! mb_check_encoding($value, 'UTF-8') || trim($value) === '' || mb_strlen($value) > $limit || preg_match('/[\p{Cc}\p{Cf}]/u', $value)) {
            throw new CommandRejection('AUDIT_LOCATION_EVIDENCE_REQUIRED', 422);
        }
    }
}
