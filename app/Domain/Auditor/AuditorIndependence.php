<?php

declare(strict_types=1);

namespace App\Domain\Auditor;

use App\Domain\Operations\CommandRejection;
use DateTimeImmutable;
use DateTimeZone;

/**
 * @phpstan-type Facts array{financial_interest: bool, current_role_tie: bool, role_tie_ended_at: string|null, family_or_business_conflict: bool, unresolved_conflict: bool}
 * @phpstan-type State array{facts: Facts, mandate_version: int, checked_at: string, evidence_reference: string}
 */
final class AuditorIndependence
{
    /**
     * @param  State|null  $previous
     * @param  array<string, mixed>  $input
     * @return State
     */
    public function review(?array $previous, array $input, int $mandateVersion, bool $currentBusinessTie, string $checkedAt, string $reference, string $reason, DateTimeImmutable $now): array
    {
        $keys = ['financial_interest', 'current_role_tie', 'role_tie_ended_at', 'family_or_business_conflict', 'unresolved_conflict'];
        if (count($input) !== count($keys) || array_diff(array_keys($input), $keys) !== []) {
            throw new CommandRejection('AUDITOR_INDEPENDENCE_FACTS_INVALID', 422);
        }
        $checked = $this->timestamp($checkedAt);
        if ($mandateVersion < 1 || $checked === null || $checked > $now || ($previous !== null && $checkedAt < $previous['checked_at'])) {
            throw new CommandRejection('AUDITOR_INDEPENDENCE_TIME_INVALID', 422);
        }
        $ended = $input['role_tie_ended_at'];
        if ($ended !== null && (! is_string($ended) || ($date = $this->timestamp($ended)) === null || $date > $now)) {
            throw new CommandRejection('AUDITOR_INDEPENDENCE_FACTS_INVALID', 422);
        }
        foreach ([[$reference, 255], [$reason, 2000]] as [$text, $limit]) {
            if (! mb_check_encoding($text, 'UTF-8') || trim($text) === '' || mb_strlen($text) > $limit || preg_match('/[\p{Cc}\p{Cf}]/u', $text)) {
                throw new CommandRejection('AUDITOR_INDEPENDENCE_EVIDENCE_REQUIRED', 422);
            }
        }
        $currentRole = $this->flag($input, 'current_role_tie');

        return ['facts' => ['financial_interest' => $this->flag($input, 'financial_interest'),
            'current_role_tie' => $currentRole || $currentBusinessTie, 'role_tie_ended_at' => $ended,
            'family_or_business_conflict' => $this->flag($input, 'family_or_business_conflict'),
            'unresolved_conflict' => $this->flag($input, 'unresolved_conflict')],
            'mandate_version' => $mandateVersion, 'checked_at' => $checkedAt, 'evidence_reference' => $reference];
    }

    /**
     * A review is evidence, never permission to override current holdings, mandate history or
     * unresolved declarations. Dispatch must combine those independently resolved facts as well.
     *
     * @param  State|null  $state
     * @return Facts
     */
    public function facts(?array $state, int $mandateVersion, DateTimeImmutable $now): array
    {
        $checked = $state === null ? null : $this->timestamp($state['checked_at']);
        if ($state === null || $state['mandate_version'] !== $mandateVersion || $checked === null || $checked > $now) {
            throw new CommandRejection('AUDITOR_INDEPENDENCE_REVIEW_REQUIRED', 403);
        }

        return $state['facts'];
    }

    /** @param array<string, mixed> $input */
    private function flag(array $input, string $key): bool
    {
        $value = $input[$key];
        if (! is_bool($value)) {
            throw new CommandRejection('AUDITOR_INDEPENDENCE_FACTS_INVALID', 422);
        }

        return $value;
    }

    private function timestamp(string $value): ?DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i:s\Z', $value, new DateTimeZone('UTC'));

        return $date !== false && $date->format('Y-m-d\TH:i:s\Z') === $value ? $date : null;
    }
}
