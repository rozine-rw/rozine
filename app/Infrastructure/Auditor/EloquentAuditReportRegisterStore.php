<?php

declare(strict_types=1);

namespace App\Infrastructure\Auditor;

use App\Application\Auditor\Contracts\AuditReportRegisterStore;
use App\Domain\Auditor\AuditReportWindow;
use App\Domain\Auditor\AuditVariance;
use App\Domain\Auditor\MonthlyReportReview;
use App\Models\AuditAssignment;
use App\Models\AuditorProfile;
use App\Models\AuditReport;
use App\Models\AuditReportSeal;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;

/**
 * An assignment's kind and clock sit in its encrypted state, so the accepted and completed
 * assignments are read and sorted here; the network is small. Each takes its newest report, that
 * report's seal and its publication. The Business is named as the seal recorded it once sealed,
 * else as its profile names it now; the licence is the sealed one, else the partner's licence on file. Only a report sealed
 * under a 24-hour review policy has a deadline, so only it can be reminded to the Business; a Flash
 * report sealed before 26 Oct 2026 waits with none. A reminder is remembered per
 * recipient, so nudging one party leaves the other's Remind in place.
 *
 * @phpstan-import-type RegisterRow from AuditReportRegisterStore
 * @phpstan-import-type RegisterStatus from AuditReportRegisterStore
 * @phpstan-import-type SealedReport from AuditReportRegisterStore
 */
final class EloquentAuditReportRegisterStore implements AuditReportRegisterStore
{
    /** How each publication state the table allows (audit_publication_state) reads in the register. */
    private const array PUBLICATION = ['pending' => 'awaiting_cosign', 'published' => 'published',
        'disputed' => 'disputed', 'escalated' => 'escalated', 'amended' => 'amended'];

    public function __construct(private AuditReportWindow $window, private MonthlyReportReview $review) {}

    /** @return list<RegisterRow> */
    public function register(DateTimeImmutable $now): array
    {
        return $this->rows(AuditAssignment::query()->whereIn('status', ['accepted', 'completed'])->whereNotNull('party_id')->get(), $now);
    }

    /** @return RegisterRow|null */
    public function row(string $assignmentId, DateTimeImmutable $now): ?array
    {
        return $this->rows(AuditAssignment::query()->whereKey($assignmentId)->whereIn('status', ['accepted', 'completed'])->whereNotNull('party_id')->get(), $now)[0] ?? null;
    }

    /** @return SealedReport|null */
    public function sealed(string $reportId): ?array
    {
        $seal = AuditReportSeal::query()->where('audit_report_id', $reportId)->first();
        if ($seal === null) {
            return null;
        }
        /** @var array{business: array{profile: array{name: string, district: string}}, auditor_name: string, sealed_at: string, digest: string, report: array{period: string|null, licence: string, findings?: list<array<string, mixed>>, draft: array{note?: string}}} $payload */
        $payload = $seal->payload;
        $findings = $payload['report']['findings'] ?? [];

        return ['report_id' => $reportId, 'kind' => (string) AuditReport::query()->whereKey($reportId)->value('kind'),
            'business' => $payload['business']['profile']['name'], 'district' => $payload['business']['profile']['district'],
            'period' => $payload['report']['period'], 'auditor' => $payload['auditor_name'], 'licence' => $payload['report']['licence'],
            'sealed_at' => $payload['sealed_at'], 'digest' => $payload['digest'],
            'findings' => array_map(fn (array $finding): array => ['measure' => (string) ($finding['measure'] ?? ''),
                'reported' => is_string($finding['reported'] ?? null) ? $finding['reported'] : null, 'observed' => (string) ($finding['observed'] ?? ''),
                'difference' => is_string($finding['difference'] ?? null) ? $finding['difference'] : null], $findings),
            'note' => (string) ($payload['report']['draft']['note'] ?? ''), 'variance_pct' => AuditVariance::weighted($findings)];
    }

    /**
     * @param  Collection<int, AuditAssignment>  $assignments
     * @return list<RegisterRow>
     */
    private function rows(Collection $assignments, DateTimeImmutable $now): array
    {
        $reports = AuditReport::query()->join('audit_assignments as current_assignment', fn (JoinClause $join): JoinClause => $join
            ->on('current_assignment.id', '=', 'audit_reports.assignment_id')
            ->on('current_assignment.party_id', '=', 'audit_reports.author_party_id')
            // Completion increments the assignment revision; reports retain their accepted revision.
            ->whereRaw(<<<'SQL'
                audit_reports.assignment_revision = CASE WHEN current_assignment.status = 'completed' THEN (
                    SELECT max(accepted.revision) FROM audit_assignment_versions AS accepted
                    WHERE accepted.assignment_id = current_assignment.id
                        AND accepted.party_id = current_assignment.party_id AND accepted.status = 'accepted'
                ) ELSE current_assignment.revision END
                SQL))
            ->whereIn('audit_reports.assignment_id', $assignments->modelKeys())->orderBy('audit_reports.created_at')->orderBy('audit_reports.id')
            ->get(['audit_reports.id', 'audit_reports.assignment_id', 'audit_reports.status', 'audit_reports.kind'])->keyBy('assignment_id');
        $seals = AuditReportSeal::query()->whereIn('audit_report_id', $reports->pluck('id')->all())->get()->keyBy('audit_report_id');
        $publications = DB::table('audit_report_publications')->whereIn('audit_report_id', $reports->pluck('id')->all())
            ->get(['audit_report_id', 'status', 'due_at'])->keyBy('audit_report_id');
        $businesses = DB::table('business_profiles')->whereIn('id', $assignments->pluck('business_id')->all())
            ->selectRaw("id, entity_party_id, profile->>'name' AS name")->get()->keyBy('id');
        $partners = DB::table('users')->whereIn('party_id', $assignments->pluck('party_id')->all())->orderBy('id')
            ->get(['party_id', 'name'])->unique('party_id')->pluck('name', 'party_id');
        $licences = AuditorProfile::query()->whereIn('party_id', $assignments->pluck('party_id')->all())->get()
            ->mapWithKeys(fn (AuditorProfile $profile): array => [$profile->party_id => $profile->state['standing']['licence']]);
        $reminders = DB::table('party_notices')->where('event_key', 'like', 'staff-audit-reminder:%')
            ->whereIn(DB::raw("split_part(event_key, ':', 2)"), $assignments->modelKeys())
            ->selectRaw("split_part(event_key, ':', 2) || ':' || split_part(event_key, ':', 3) || ':' || party_id AS recipient, max(created_at) AS at")->groupBy('recipient')->pluck('at', 'recipient');
        $rows = [];
        foreach ($assignments as $assignment) {
            $state = $assignment->state;
            $period = $state['kind'] === 'routine'
                ? CarbonImmutable::parse($state['original_dispatch_at'])->setTimezone('Africa/Kigali')->startOfMonth()->subMonth()->format('Y-m') : null;
            $due = $period === null ? $state['complete_by'] : $this->window->dueAt($period);
            $report = $reports->get($assignment->id);
            $seal = $report === null ? null : $seals->get($report->id);
            /** @var array{business: array{profile: array{name: string}}, sealed_at: string, report: array{licence: string, findings?: list<array<string, mixed>>}}|null $payload */
            $payload = $seal?->payload;
            $business = $businesses->get($assignment->business_id);
            $publication = $report === null ? null : $publications->get($report->id);
            $status = self::status($report?->status, $publication?->status, $due, $now);
            $owes = match ($status) {
                'not_started', 'in_progress', 'overdue', 'returned' => 'auditor',
                'awaiting_cosign' => $publication?->due_at !== null && $this->review->isOpen($publication->status, $publication->due_at, $now) ? 'business' : null,
                default => null,
            };
            $recipient = $owes === 'auditor' ? $assignment->party_id : $business?->entity_party_id;
            $reminded = $owes === null ? null : ($reminders[$assignment->id.':'.$owes.':'.$recipient] ?? null);
            $rows[] = ['assignment_id' => $assignment->id, 'report_id' => $report?->id, 'business_id' => $assignment->business_id,
                'business' => $payload['business']['profile']['name'] ?? (string) ($business->name ?? ''),
                'kind' => $state['kind'] === 'routine' ? 'monthly' : 'flash', 'period' => $period,
                'auditor' => $assignment->party_id === null ? null : (string) ($partners[$assignment->party_id] ?? ''), 'auditor_party_id' => $assignment->party_id,
                'licence' => $payload['report']['licence'] ?? $licences->get((string) $assignment->party_id), 'status' => $status,
                'due_at' => $due === null ? null : CarbonImmutable::parse($due)->utc()->format('Y-m-d\TH:i:s\Z'),
                'sealed_at' => $payload['sealed_at'] ?? null,
                'cosign_by' => $status === 'awaiting_cosign' && $publication?->due_at !== null ? CarbonImmutable::parse($publication->due_at)->utc()->format('Y-m-d\TH:i:s\Z') : null,
                'digest' => $seal?->digest,
                'variance_pct' => $payload === null ? null : AuditVariance::weighted($payload['report']['findings'] ?? []),
                'variance_flagged' => AuditVariance::hasMoneyDifference($payload['report']['findings'] ?? []),
                'owes' => $owes,
                'reminded_at' => $reminded === null ? null : CarbonImmutable::parse($reminded)->utc()->format('Y-m-d\TH:i:s\Z'),
                'dispatched_at' => (string) $state['original_dispatch_at']];
        }
        usort($rows, fn (array $a, array $b): int => [$b['dispatched_at'], $b['assignment_id']] <=> [$a['dispatched_at'], $a['assignment_id']]);

        return array_map(function (array $row): array {
            unset($row['dispatched_at']);

            return $row;
        }, $rows);
    }

    /** @return RegisterStatus */
    private static function status(?string $report, ?string $publication, ?string $due, DateTimeImmutable $now): string
    {
        $late = $due !== null && CarbonImmutable::parse($due)->lessThan($now);

        return match (true) {
            $report === null => $late ? 'overdue' : 'not_started',
            $report === 'draft' => $late ? 'overdue' : 'in_progress',
            in_array($report, ['changes_requested', 'rejected'], true) => 'returned',
            $report === 'withdrawn' => 'withdrawn',
            $publication === null => 'sealed',
            default => self::PUBLICATION[$publication] ?? 'sealed',
        };
    }
}
