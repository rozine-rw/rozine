<?php

declare(strict_types=1);

namespace App\Infrastructure\Auditor;

use App\Application\Business\Contracts\BusinessAuditWindows;
use App\Domain\Auditor\AuditReportWindow;
use App\Models\AuditAssignment;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

/**
 * A Business has at most one open assignment, whose encrypted state is read here. A report already
 * sealed for it closes the window even before the assignment completes. The partner named is the one the file was offered to; a file still waiting
 * for a partner names no one.
 *
 * @phpstan-import-type AuditWindow from BusinessAuditWindows
 */
final class EloquentBusinessAuditWindows implements BusinessAuditWindows
{
    public function __construct(private AuditReportWindow $window) {}

    /** @return AuditWindow|null */
    public function upcoming(string $businessId, DateTimeImmutable $now): ?array
    {
        $now = CarbonImmutable::instance($now);
        $sealed = DB::table('audit_reports')->where('business_id', $businessId)->where('status', 'sealed');
        $assignment = AuditAssignment::query()->where('business_id', $businessId)->whereIn('status', ['offered', 'accepted', 'operations'])
            ->whereNotIn('id', (clone $sealed)->select('assignment_id'))->first();
        if ($assignment === null || $assignment->state['kind'] !== 'routine') {
            return null;
        }
        $period = CarbonImmutable::parse($assignment->state['original_dispatch_at'])->setTimezone('Africa/Kigali')->startOfMonth()->subMonth()->format('Y-m');
        $sealBy = CarbonImmutable::parse((string) $this->window->dueAt($period));
        if ($now->greaterThan($sealBy)) {
            return null;
        }
        $days = (int) $now->setTimezone('Africa/Kigali')->startOfDay()->diffInDays($sealBy->setTimezone('Africa/Kigali')->startOfDay());

        return ['period' => $period.'-01', 'seal_by' => $sealBy->format('Y-m-d\TH:i:s\Z'),
            'cosign_by' => $sealBy->addHours(24)->format('Y-m-d\TH:i:s\Z'), 'days_left' => max(0, $days),
            'first' => ! (clone $sealed)->where('kind', 'monthly')->exists(), 'reassigned' => $this->reassigned($assignment)];
    }

    /** @return array{from: string, to: string}|null */
    private function reassigned(AuditAssignment $assignment): ?array
    {
        if ($assignment->party_id === null) {
            return null;
        }
        $previous = DB::table('audit_assignment_versions')->where('assignment_id', $assignment->id)->whereNotNull('party_id')
            ->where('party_id', '<>', $assignment->party_id)->orderByDesc('revision')->value('party_id');
        if (! is_string($previous)) {
            return null;
        }
        $name = fn (string $partyId): string => (string) DB::table('users')->where('party_id', $partyId)->orderBy('id')->value('name');
        $from = $name($previous);
        $to = $name($assignment->party_id);

        return $from === '' || $to === '' ? null : ['from' => $from, 'to' => $to];
    }
}
