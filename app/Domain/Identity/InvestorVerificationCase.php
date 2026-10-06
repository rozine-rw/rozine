<?php

declare(strict_types=1);

namespace App\Domain\Identity;

use App\Domain\Operations\CommandRejection;
use DateTimeImmutable;

/**
 * An individual Investor's own identity submission (MVP-INVESTOR-SCR-03). It holds the answers and
 * the hash-pinned document ids only; it never verifies anyone. Approval belongs to Compliance and
 * goes through the existing verified-person writer, which alone sets Party verification.
 *
 * @phpstan-type State array{
 *     step: 'personal'|'document'|'liveness',
 *     date_of_birth: string,
 *     id_type: 'national_id'|'passport'|'drivers_license',
 *     id_number: string,
 *     uploads: array{front: string|null, back: string|null, selfie: string|null},
 *     decision: array{outcome: 'approved'|'rejected', reason: string, decided_at: string}|null
 * }
 */
final class InvestorVerificationCase
{
    public const STEPS = ['personal', 'document', 'liveness'];

    public const SLOTS = ['id_front' => 'front', 'id_back' => 'back', 'selfie' => 'selfie'];

    public const MINIMUM_AGE = 18;

    /** @return State */
    public function empty(): array
    {
        return ['step' => 'personal', 'date_of_birth' => '', 'id_type' => 'national_id', 'id_number' => '',
            'uploads' => ['front' => null, 'back' => null, 'selfie' => null], 'decision' => null];
    }

    /**
     * Records one step's answers and moves to the next step. A rejected submission reopens as a draft.
     *
     * @param  State  $state
     * @param  array<string, mixed>  $input
     * @return State
     */
    public function save(array $state, string $status, string $step, array $input, DateTimeImmutable $now): array
    {
        $this->editable($status);
        $this->reachable($state, $step);

        if ($step === 'personal') {
            $state['date_of_birth'] = $this->dateOfBirth((string) ($input['date_of_birth'] ?? ''), $now);
        } elseif ($step === 'document') {
            [$state['id_type'], $state['id_number']] = $this->document((string) ($input['id_type'] ?? ''), (string) ($input['id_number'] ?? ''));
            $this->documentsUploaded($state);
        } else {
            throw new CommandRejection('VERIFICATION_STEP_INVALID', 422, fieldErrors: ['step' => ['Submit the final step instead of saving it.']]);
        }

        $state['step'] = self::STEPS[array_search($step, self::STEPS, true) + 1];
        $state['decision'] = null;

        return $state;
    }

    /**
     * @param  State  $state
     * @return State
     */
    public function upload(array $state, string $status, string $slot, string $documentId): array
    {
        $this->editable($status);
        if (! array_key_exists($slot, self::SLOTS)) {
            throw new CommandRejection('VERIFICATION_SLOT_INVALID', 422, fieldErrors: ['slot' => ['Choose the ID front, ID back or selfie.']]);
        }
        $state['uploads'][self::SLOTS[$slot]] = $documentId;
        $state['decision'] = null;

        return $state;
    }

    /**
     * Every answer and document is rechecked here, so a stale step can never submit an incomplete case.
     *
     * @param  State  $state
     * @return State
     */
    public function submit(array $state, string $status, DateTimeImmutable $now): array
    {
        $this->editable($status);
        $this->reachable($state, 'liveness');
        $this->dateOfBirth($state['date_of_birth'], $now);
        $this->document($state['id_type'], $state['id_number']);
        $this->documentsUploaded($state);
        if ($state['uploads']['selfie'] === null) {
            throw new CommandRejection('VERIFICATION_SELFIE_REQUIRED', 422, fieldErrors: ['selfie' => ['Take a selfie to finish.']]);
        }
        $state['decision'] = null;

        return $state;
    }

    /**
     * The identity reference the verified-person writer pins: document kind and normalized number.
     *
     * @param  State  $state
     */
    public function identityReference(array $state): string
    {
        return match ($state['id_type']) {
            'national_id' => 'rw-nid',
            'passport' => 'passport',
            'drivers_license' => 'rw-dl',
        }.':'.$state['id_number'];
    }

    private function editable(string $status): void
    {
        if (! in_array($status, ['draft', 'rejected'], true)) {
            throw new CommandRejection('VERIFICATION_NOT_EDITABLE', 409);
        }
    }

    /** @param State $state */
    private function reachable(array $state, string $step): void
    {
        $position = array_search($step, self::STEPS, true);
        if ($position === false) {
            throw new CommandRejection('VERIFICATION_STEP_INVALID', 422, fieldErrors: ['step' => ['Choose a verification step.']]);
        }
        if ($position > array_search($state['step'], self::STEPS, true)) {
            throw new CommandRejection('VERIFICATION_STEP_OUT_OF_ORDER', 409);
        }
    }

    /** Read as the page writes it, day first (DD / MM / YYYY), and kept in that one form. */
    private function dateOfBirth(string $value, DateTimeImmutable $now): string
    {
        $parts = [];
        if (preg_match('/^\s*([0-9]{1,2})\s*[\/.-]\s*([0-9]{1,2})\s*[\/.-]\s*([0-9]{4})\s*$/D', $value, $parts) !== 1
            || ! checkdate((int) $parts[2], (int) $parts[1], (int) $parts[3]) || (int) $parts[3] < 1900) {
            throw new CommandRejection('VERIFICATION_DATE_OF_BIRTH_INVALID', 422, fieldErrors: ['date_of_birth' => ['Enter your date of birth as DD / MM / YYYY.']]);
        }
        $date = new DateTimeImmutable(sprintf('%04d-%02d-%02d', $parts[3], $parts[2], $parts[1]));
        if ($date->modify('+'.self::MINIMUM_AGE.' years') > $now) {
            throw new CommandRejection('VERIFICATION_AGE_BELOW_MINIMUM', 422, fieldErrors: ['date_of_birth' => ['You must be at least 18 to invest.']]);
        }

        return $date->format('d / m / Y');
    }

    /** @return array{'national_id'|'passport'|'drivers_license', string} */
    private function document(string $type, string $number): array
    {
        $number = strtoupper((string) preg_replace('/\s+/', '', $number));
        $valid = match ($type) {
            'national_id', 'drivers_license' => preg_match('/^[0-9]{16}$/D', $number) === 1,
            'passport' => preg_match('/^[A-Z0-9]{6,12}$/D', $number) === 1,
            default => throw new CommandRejection('VERIFICATION_ID_TYPE_INVALID', 422, fieldErrors: ['id_type' => ['Choose a national ID, passport or driving licence.']]),
        };
        if (! $valid) {
            throw new CommandRejection('VERIFICATION_ID_NUMBER_INVALID', 422, fieldErrors: ['id_number' => [$type === 'passport'
                ? 'Enter the passport number, 6 to 12 letters or digits.'
                : 'Enter the 16-digit number on the card.']]);
        }

        return [$type, $number];
    }

    /** @param State $state */
    private function documentsUploaded(array $state): void
    {
        if ($state['uploads']['front'] === null || ($state['id_type'] !== 'passport' && $state['uploads']['back'] === null)) {
            throw new CommandRejection('VERIFICATION_DOCUMENT_REQUIRED', 422, fieldErrors: ['id_front' => ['Upload both sides of your ID, or the passport photo page.']]);
        }
    }
}
