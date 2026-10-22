<?php

declare(strict_types=1);

namespace App\Infrastructure\Auditor;

use App\Application\Auditor\Contracts\AuditorFiledReportStore;
use App\Application\Identity\AuthorizeActiveRole;
use App\Application\Identity\Contracts\IdentityRepository;
use App\Domain\Auditor\AuditReportWindow;
use App\Domain\Auditor\AuditVariance;
use App\Domain\Identity\IdentityViolation;
use App\Models\AuditAssignment;
use App\Models\AuditReport;
use App\Models\AuditReportSeal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * The partner's sealed reports, read under the current Auditor role and its Party lock. The
 * Business is named as it was sealed (the seal's own record of it), and the due date is the one
 * the procedure already holds: the flash assignment's `complete_by`, or the monthly report window
 * for the sealed period. An offer runs out on a partner when the next version of its assignment is
 * the expiry sweep or a staff advance past their unaccepted offer.
 *
 * @phpstan-import-type FiledReport from AuditorFiledReportStore
 * @phpstan-import-type FiledReports from AuditorFiledReportStore
 * @phpstan-import-type TrackRecord from AuditorFiledReportStore
 */
final class EloquentAuditorFiledReportStore implements AuditorFiledReportStore
{
    /**
     * A filed report's publication while it still stands: awaiting its co-signature or review,
     * disputed, escalated or published. An `amended` publication was superseded by its linked
     * amendment, which is listed in its own right once sealed.
     */
    private const STANDING = ['pending', 'disputed', 'escalated', 'published'];

    public function __construct(private IdentityRepository $identities, private AuthorizeActiveRole $roles, private AuditReportWindow $window) {}

    /** @return FiledReports */
    public function filed(int $userId, int $contextRevision, ?bool $published, int $limit): array
    {
        $partyId = $this->identities->forUser($userId)['party']['id'] ?? throw new IdentityViolation('IDENTITY_NOT_LINKED');

        return $this->roles->handle($userId, 'auditor', $partyId, $contextRevision, function () use ($partyId, $published, $limit): array {
            /** @return Builder<AuditReport> */
            $filed = fn (): Builder => AuditReport::query()->where('audit_reports.author_party_id', $partyId)->where('audit_reports.status', 'sealed')
                ->join('audit_report_publications as publications', 'publications.audit_report_id', '=', 'audit_reports.id')
                ->whereIn('publications.status', self::STANDING);
            $page = $filed()->join('audit_report_seals as seals', 'seals.audit_report_id', '=', 'audit_reports.id')
                ->when($published !== null, fn (Builder $query): Builder => $query->where('publications.status', $published ? '=' : '<>', 'published'))
                ->orderByDesc('seals.created_at')->orderByDesc('audit_reports.id')->limit($limit)
                ->get(['audit_reports.id', 'audit_reports.kind', 'audit_reports.assignment_id', 'publications.status as publication_status']);
            $seals = AuditReportSeal::query()->whereIn('audit_report_id', $page->modelKeys())->get()->keyBy('audit_report_id');
            $assignments = AuditAssignment::query()->whereIn('id', $page->pluck('assignment_id')->all())->get()->keyBy('id');

            return ['data' => array_values($page->map(fn (AuditReport $report): array => $this->report($report, $seals[$report->id], $assignments[$report->assignment_id]))->all()),
                'counts' => ['all' => $filed()->count(), 'published' => $filed()->where('publications.status', 'published')->count()]];
        });
    }

    /** @return TrackRecord */
    public function trackRecord(int $userId, int $contextRevision): array
    {
        $partyId = $this->identities->forUser($userId)['party']['id'] ?? throw new IdentityViolation('IDENTITY_NOT_LINKED');

        return $this->roles->handle($userId, 'auditor', $partyId, $contextRevision, function () use ($partyId): array {
            $reports = AuditReport::query()->where('audit_reports.author_party_id', $partyId)->where('audit_reports.status', 'sealed')
                ->join('audit_report_publications as publications', 'publications.audit_report_id', '=', 'audit_reports.id')
                ->whereIn('publications.status', self::STANDING)->get(['audit_reports.id', 'audit_reports.kind', 'audit_reports.assignment_id', 'publications.status as publication_status']);
            $seals = AuditReportSeal::query()->whereIn('audit_report_id', $reports->modelKeys())->get()->keyBy('audit_report_id');
            $assignments = AuditAssignment::query()->whereIn('id', $reports->pluck('assignment_id')->all())->get()->keyBy('id');
            $timed = $onTime = 0;
            $findings = [];
            foreach ($reports as $report) {
                $filed = $this->report($report, $seals[$report->id], $assignments[$report->assignment_id]);
                $findings = [...$findings, ...$seals[$report->id]->payload['report']['findings'] ?? []];
                if ($filed['due_at'] !== null) {
                    $timed++;
                    $onTime += $filed['sealed_at'] <= $filed['due_at'] ? 1 : 0;
                }
            }
            $expiries = DB::table('audit_assignment_versions as exits')
                ->join('audit_assignment_versions as offers', fn ($join) => $join->on('offers.assignment_id', '=', 'exits.assignment_id')
                    ->whereRaw('offers.revision = exits.revision - 1'))
                ->where('offers.party_id', $partyId)->where('offers.status', 'offered')
                ->whereIn('exits.command', ['audit.assignment.expire', 'audit.assignment.advance'])->count();

            return ['on_time_pct' => $timed === 0 ? null : intdiv($onTime * 100, $timed), 'clock_expiries' => $expiries,
                'avg_variance_pct' => AuditVariance::weighted($findings), 'variance_flagged' => AuditVariance::hasMoneyDifference($findings)];
        });
    }

    /** @return FiledReport */
    private function report(AuditReport $report, AuditReportSeal $seal, AuditAssignment $assignment): array
    {
        /** @var array{business: array{profile: array{name: string, district: string}}, report: array{period: string|null}, sealed_at: string} $sealed */
        $sealed = $seal->payload;
        $period = $sealed['report']['period'];

        return ['id' => $report->id, 'kind' => $report->kind, 'period' => $period,
            'business' => $sealed['business']['profile']['name'], 'district' => $sealed['business']['profile']['district'],
            'sealed_at' => $sealed['sealed_at'], 'published' => $report->getAttribute('publication_status') === 'published',
            'due_at' => $period === null ? ($assignment->state['complete_by'] ?? null) : $this->window->dueAt($period),
            'variance_pct' => AuditVariance::weighted($seal->payload['report']['findings'] ?? [])];
    }
}
