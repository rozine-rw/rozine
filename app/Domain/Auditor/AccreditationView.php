<?php

declare(strict_types=1);

namespace App\Domain\Auditor;

use App\Domain\Operations\CommandRejection;
use DateTimeImmutable;
use DateTimeZone;

/** @phpstan-import-type State from AccreditationProfile */
final class AccreditationView
{
    public function __construct(private AuditorStanding $standing) {}

    /**
     * Whitelists participant facts; staff check references and original filenames stay private.
     *
     * @param  State  $state
     * @return array<string, mixed>
     */
    public function present(array $state, int $revision, ?string $certificateHash, DateTimeImmutable $now): array
    {
        $current = true;
        $reason = null;
        try {
            $this->standing->requireCurrent($state['standing'], $now);
        } catch (CommandRejection $exception) {
            $current = false;
            $reason = $exception->reason;
        }
        $standing = $state['standing'];
        $today = $now->setTimezone(new DateTimeZone('Africa/Kigali'))->setTime(0, 0);
        $status = match (true) {
            $standing['status'] === 'none' => 'none',
            in_array($standing['status'], ['suspended', 'revoked'], true) => 'suspended',
            $standing['expires_on'] !== null && $standing['expires_on'] < $today->format('Y-m-d') => 'expired',
            default => 'active',
        };
        $expiry = $standing['expires_on'] === null ? null : new DateTimeImmutable($standing['expires_on'], new DateTimeZone('Africa/Kigali'));
        $submission = $state['submission'];
        $pending = $submission['status'] === 'pending';
        if ($submission['status'] === 'pending') {
            if ($certificateHash === null || preg_match('/^[a-f0-9]{64}$/D', $certificateHash) !== 1) {
                throw new CommandRejection('ACCREDITATION_CERTIFICATE_INTEGRITY_FAILED');
            }
            $submission = ['status' => 'pending', 'id' => $submission['id'], 'licence' => $submission['licence'], 'expires_on' => $submission['expires_on'],
                'submitted_on' => (new DateTimeImmutable($submission['submitted_at']))->setTimezone(new DateTimeZone('Africa/Kigali'))->format('Y-m-d'),
                'evidence' => ['evidence_id' => $submission['id'], 'kind' => 'licence_certificate', 'sha256' => $certificateHash,
                    'captured_at' => null, 'source' => 'web_upload', 'device_attestation' => 'unavailable', 'position' => null, 'accuracy_m' => null]];
        }
        $actions = $pending ? ['accreditation.withdraw'] : [$state['certificate_id'] === null ? 'accreditation.submit' : 'accreditation.renew'];
        if ($current || $state['accepting']) {
            $actions[] = 'availability.update';
        }

        return ['standing' => ['current' => $current, 'reason' => $reason],
            'accreditation' => ['status' => $status, 'revision' => $revision, 'licence' => $standing['licence'], 'expires_on' => $standing['expires_on'],
                'days_left' => $expiry === null ? null : (int) $today->diff($expiry)->format('%r%a'), 'submission' => $submission],
            'availability' => ['accepting' => $state['accepting'], 'radius_km' => 30, 'max_active' => 3, 'revision' => $revision],
            'allowed_actions' => $actions];
    }
}
