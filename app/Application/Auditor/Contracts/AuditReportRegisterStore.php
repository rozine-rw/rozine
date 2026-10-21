<?php

declare(strict_types=1);

namespace App\Application\Auditor\Contracts;

use DateTimeImmutable;

/**
 * The Admin console's register of audit work (the design's Reports oversight): every accepted or
 * completed audit with its latest report, the state that report is in and who owes the next step.
 * Reads only; the caller checks the staff permission.
 *
 * A monthly visit is due by the 7th of the month after its period, a Flash Audit by its clock. An
 * unsealed audit past that is overdue. A sealed monthly report waits for the Business to co-sign or
 * dispute by its review deadline (`cosign_by`); the CPA owes every step before the seal. Staff may
 * remind whoever owes the next step; `reminded_at` is the latest such reminder.
 *
 * @phpstan-type RegisterStatus 'not_started'|'in_progress'|'overdue'|'returned'|'sealed'|'awaiting_cosign'|'published'|'disputed'|'escalated'|'amended'|'withdrawn'
 * @phpstan-type RegisterRow array{
 *     assignment_id: string, report_id: string|null, business_id: string, business: string, kind: 'flash'|'monthly', period: string|null,
 *     auditor: string|null, auditor_party_id: string|null, licence: string|null, status: RegisterStatus, due_at: string|null,
 *     sealed_at: string|null, cosign_by: string|null, digest: string|null, variance_pct: string|null, owes: 'auditor'|'business'|null,
 *     reminded_at: string|null
 * }
 * @phpstan-type SealedReport array{
 *     report_id: string, kind: string, business: string, district: string, period: string|null, auditor: string, licence: string,
 *     sealed_at: string, digest: string, findings: list<array{measure: string, reported: string|null, observed: string, difference: string|null}>,
 *     note: string, variance_pct: string|null
 * }
 */
interface AuditReportRegisterStore
{
    /** @return list<RegisterRow> newest dispatch first */
    public function register(DateTimeImmutable $now): array;

    /** @return RegisterRow|null */
    public function row(string $assignmentId, DateTimeImmutable $now): ?array;

    /** @return SealedReport|null the report as its seal recorded it, or null when it is not sealed */
    public function sealed(string $reportId): ?array;
}
