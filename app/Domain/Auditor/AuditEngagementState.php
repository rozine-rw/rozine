<?php

declare(strict_types=1);

namespace App\Domain\Auditor;

use App\Domain\Operations\CommandRejection;
use DateTimeImmutable;

/**
 * @phpstan-type State array{kind: string, status: string, party_id: string|null, original_dispatch_at: string, offered_at: string|null, accepted_at: string|null, accept_by: string|null, complete_by: string|null, visit_by: string|null, attempt: int, tried: list<string>, operations_reason: string|null}
 */
final class AuditEngagementState
{
    public function __construct(private AuditAssignmentClock $clock) {}

    /** @return State */
    public function start(string $kind, DateTimeImmutable $now): array
    {
        $clock = $this->clock->offer($kind, $now, $now, 1);

        return ['kind' => $kind, 'status' => 'operations', 'party_id' => null, 'original_dispatch_at' => $this->time($now),
            'offered_at' => null, 'accepted_at' => null, 'accept_by' => null, 'complete_by' => $clock['complete_by'] === null ? null : $this->time($clock['complete_by']),
            'visit_by' => null, 'attempt' => 0, 'tried' => [], 'operations_reason' => 'AUDIT_NO_ELIGIBLE_PARTNER'];
    }

    /**
     * @param  State  $state
     * @return State
     */
    public function offer(array $state, ?string $partyId, DateTimeImmutable $now): array
    {
        $clock = $this->clock->offer($state['kind'], new DateTimeImmutable($state['original_dispatch_at']), $now, $state['attempt'] + 1);
        $next = [...$state, 'party_id' => null, 'status' => 'operations', 'accept_by' => null, 'visit_by' => null, 'accepted_at' => null,
            'operations_reason' => $clock['operations_required'] ? 'AUDIT_DISPATCH_EXHAUSTED' : 'AUDIT_NO_ELIGIBLE_PARTNER'];
        if ($partyId === null || $clock['operations_required']) {
            return $next;
        }
        if (in_array($partyId, $state['tried'], true)) {
            throw new CommandRejection('AUDIT_PARTNER_ALREADY_OFFERED');
        }

        return [...$next, 'party_id' => $partyId, 'status' => 'offered', 'offered_at' => $this->time($now),
            'accept_by' => $clock['accept_by'] === null ? null : $this->time($clock['accept_by']), 'attempt' => $state['attempt'] + 1,
            'tried' => [...$state['tried'], $partyId], 'operations_reason' => null];
    }

    /**
     * @param  State  $state
     * @return State
     */
    public function accept(array $state, DateTimeImmutable $now): array
    {
        if ($state['status'] !== 'offered' || $state['offered_at'] === null) {
            throw new CommandRejection('ASSIGNMENT_NOT_OFFERED');
        }
        $clock = $this->clock->accept($state['kind'], new DateTimeImmutable($state['original_dispatch_at']), new DateTimeImmutable($state['offered_at']), $state['attempt'], $now);

        return [...$state, 'status' => 'accepted', 'accepted_at' => $this->time($now),
            'visit_by' => $clock['visit_by'] === null ? null : $this->time($clock['visit_by'])];
    }

    public function reason(string $reason): void
    {
        if (trim($reason) === '' || strlen($reason) > 2000 || preg_match('//u', $reason) !== 1 || preg_match('/[\p{Cc}\p{Cf}]/u', $reason) === 1) {
            throw new CommandRejection('ASSIGNMENT_REASON_REQUIRED', 422, fieldErrors: ['reason' => ['A concise reason is required.']]);
        }
    }

    public function conflict(string $kind, string $reason): void
    {
        $this->reason($reason);
        if (! in_array($kind, ['financial_interest', 'role_tie', 'family_or_business', 'other'], true)) {
            throw new CommandRejection('AUDIT_CONFLICT_KIND_INVALID', 422, fieldErrors: ['kind' => ['Select a conflict type.']]);
        }
    }

    private function time(DateTimeImmutable $time): string
    {
        return $time->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z');
    }
}
