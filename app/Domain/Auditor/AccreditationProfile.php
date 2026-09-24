<?php

declare(strict_types=1);

namespace App\Domain\Auditor;

use App\Domain\Operations\CommandRejection;
use DateTimeImmutable;
use DateTimeZone;

/**
 * @phpstan-import-type Standing from AuditorStanding
 *
 * @phpstan-type Submission array{status: 'none'}|array{status: 'pending', id: string, licence: string, expires_on: string, submitted_at: string}|array{status: 'rejected', id: string, reason: string}
 * @phpstan-type State array{accepting: bool, standing: Standing, certificate_id: string|null, submission: Submission}
 */
final class AccreditationProfile
{
    public function __construct(private AuditorStanding $standing) {}

    /** @return State */
    public function empty(): array
    {
        return ['accepting' => false, 'standing' => ['status' => 'none', 'licence' => null, 'expires_on' => null,
            'checked_at' => null, 'check_reference' => null], 'certificate_id' => null, 'submission' => ['status' => 'none']];
    }

    /**
     * @param  State  $state
     * @return State
     */
    public function submit(array $state, string $id, string $licence, string $expiresOn, DateTimeImmutable $now): array
    {
        if ($state['submission']['status'] === 'pending') {
            throw new CommandRejection('ACCREDITATION_SUBMISSION_PENDING');
        }
        $licence = trim($licence);
        if ($licence === '' || mb_strlen($licence) > 32 || ! mb_check_encoding($licence, 'UTF-8') || preg_match('/[\p{Cc}\p{Cf}]/u', $licence) === 1) {
            throw new CommandRejection('ACCREDITATION_LICENCE_INVALID', 422, fieldErrors: ['licence' => ['Enter a valid licence number of at most 32 characters.']]);
        }
        $expiry = DateTimeImmutable::createFromFormat('!Y-m-d', $expiresOn);
        if ($expiry === false || $expiry->format('Y-m-d') !== $expiresOn
            || $expiresOn < $now->setTimezone(new DateTimeZone('Africa/Kigali'))->format('Y-m-d')) {
            throw new CommandRejection('ACCREDITATION_EXPIRY_INVALID', 422, fieldErrors: ['expires_on' => ['Enter a current certificate expiry date.']]);
        }
        $state['submission'] = ['status' => 'pending', 'id' => $id, 'licence' => $licence, 'expires_on' => $expiresOn,
            'submitted_at' => $now->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z')];

        return $state;
    }

    /**
     * @param  State  $state
     * @return State
     */
    public function withdraw(array $state, string $submissionId): array
    {
        $this->pending($state, $submissionId);
        $state['submission'] = ['status' => 'none'];

        return $state;
    }

    /**
     * @param  State  $state
     * @return State
     */
    public function availability(array $state, bool $accepting, DateTimeImmutable $now): array
    {
        if ($accepting) {
            $this->standing->requireCurrent($state['standing'], $now);
        }
        $state['accepting'] = $accepting;

        return $state;
    }

    /**
     * Records independently reviewed facts. Uploaded claims remain separate until approval.
     *
     * @param  State  $state
     * @return State
     */
    public function review(array $state, string $decision, ?string $submissionId, string $checkedAt, string $reference, string $reason, DateTimeImmutable $now): array
    {
        if (! in_array($decision, ['approve', 'reject', 'recheck', 'suspend', 'revoke'], true)
            || trim($reference) === '' || mb_strlen($reference) > 255 || trim($reason) === '' || mb_strlen($reason) > 2000) {
            throw new CommandRejection('ACCREDITATION_REVIEW_INVALID', 422);
        }
        $checked = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i:s\Z', $checkedAt, new DateTimeZone('UTC'));
        if ($checked === false || $checked->format('Y-m-d\TH:i:s\Z') !== $checkedAt || $checked > $now || $checked < $now->modify('-30 days')) {
            throw new CommandRejection('STANDING_CHECK_REQUIRED', 422);
        }
        if ($decision === 'approve' || $decision === 'reject') {
            $this->pending($state, $submissionId);
            if ($decision === 'reject') {
                $state['submission'] = ['status' => 'rejected', 'id' => $state['submission']['id'], 'reason' => trim($reason)];

                return $state;
            }
            if ($state['submission']['expires_on'] < $now->setTimezone(new DateTimeZone('Africa/Kigali'))->format('Y-m-d')) {
                throw new CommandRejection('ACCREDITATION_SUBMISSION_EXPIRED', 422, fieldErrors: ['submission_id' => ['Request a current certificate before approving this submission.']]);
            }
            $state['standing'] = ['status' => 'active', 'licence' => $state['submission']['licence'], 'expires_on' => $state['submission']['expires_on'],
                'checked_at' => $checkedAt, 'check_reference' => trim($reference)];
            $state['certificate_id'] = $state['submission']['id'];
            $state['submission'] = ['status' => 'none'];
        } else {
            if ($submissionId !== null || $state['certificate_id'] === null || ($decision === 'recheck' && $state['standing']['status'] !== 'active')) {
                throw new CommandRejection('ACCREDITATION_REVIEW_INVALID', 422);
            }
            $state['standing']['status'] = match ($decision) {
                'suspend' => 'suspended',
                'revoke' => 'revoked',
                default => 'active',
            };
            $state['standing']['checked_at'] = $checkedAt;
            $state['standing']['check_reference'] = trim($reference);
        }
        if ($state['standing']['status'] === 'active') {
            $this->standing->requireCurrent($state['standing'], $now);
        } else {
            $state['accepting'] = false;
        }

        return $state;
    }

    /**
     * @param  State  $state
     *
     * @phpstan-assert array{accepting: bool, standing: Standing, certificate_id: string|null, submission: array{status: 'pending', id: string, licence: string, expires_on: string, submitted_at: string}} $state
     */
    private function pending(array $state, ?string $submissionId): void
    {
        if ($state['submission']['status'] !== 'pending' || $submissionId !== $state['submission']['id']) {
            throw new CommandRejection('ACCREDITATION_SUBMISSION_STALE');
        }
    }
}
