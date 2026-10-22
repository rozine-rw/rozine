<?php

declare(strict_types=1);

namespace App\Application\Auditor;

use App\Application\Auditor\Contracts\AuditReportRegisterStore;
use App\Application\Business\Contracts\BusinessNoticeAudience;
use App\Application\Identity\AuthorizeStaffPermission;
use App\Application\Identity\Contracts\PartyNotices;
use App\Domain\Identity\PartyNoticeKind;
use App\Domain\Operations\CommandRejection;
use Carbon\CarbonImmutable;

/**
 * Staff nudge whoever owes an audit's next step (`audit.assignments.manage`): the Audit Partner
 * before the seal, with the date it is due, and the Business while a sealed report waits for it
 * to co-sign or dispute, with its review deadline. Each is an in-app notice, recorded at most once
 * a Kigali day for the same audit and recipient, so a second press that day records nothing new.
 */
final class RemindAuditReport
{
    public function __construct(private AuditReportRegisterStore $store, private AuthorizeStaffPermission $staff,
        private PartyNotices $notices, private BusinessNoticeAudience $audience) {}

    /** @return 'auditor'|'business' who was reminded */
    public function handle(int $actorId, string $assignmentId): string
    {
        return $this->staff->handle($actorId, 'audit.assignments.manage', function () use ($assignmentId): string {
            $now = now()->toDateTimeImmutable();
            $row = $this->store->row($assignmentId, $now) ?? throw new CommandRejection('AUDIT_ASSIGNMENT_NOT_FOUND', 404);
            $day = now('Africa/Kigali')->format('Y-m-d');
            if ($row['owes'] !== null && $row['reminded_at'] !== null
                && CarbonImmutable::parse($row['reminded_at'])->setTimezone('Africa/Kigali')->format('Y-m-d') === $day) {
                return $row['owes'];
            }
            if ($row['owes'] === 'auditor') {
                $this->notices->record((string) $row['auditor_party_id'], PartyNoticeKind::AuditorReportReminder, ['business' => $row['business'],
                    'kind' => $row['kind'], 'period' => (string) $row['period'], 'due_at' => (string) $row['due_at'], 'assignment_id' => $row['assignment_id']],
                    'staff-audit-reminder:'.$row['assignment_id'].':auditor:'.$row['auditor_party_id'].':'.$day);

                return 'auditor';
            }
            if ($row['owes'] === 'business') {
                $partyId = $this->audience->entityParty($row['business_id']);
                $this->notices->record($partyId, PartyNoticeKind::BusinessReportReviewClosing,
                    ['due_at' => (string) $row['cosign_by'], 'business_id' => $row['business_id'], 'report_id' => (string) $row['report_id']],
                    'staff-audit-reminder:'.$row['assignment_id'].':business:'.$partyId.':'.$day);

                return 'business';
            }

            throw new CommandRejection('AUDIT_REMINDER_NOT_OWED', 409);
        });
    }
}
