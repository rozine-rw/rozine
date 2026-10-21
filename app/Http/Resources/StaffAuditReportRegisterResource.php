<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The design's Reports oversight from live records: four figures, the views with their counts and
 * one row per audit with the state of its report, its due date and, once sealed, the seal's time,
 * digest and weighted variance. A sealed report downloads as its PDF. Staff who manage assignments
 * may remind whoever owes the next step, once a Kigali day.
 *
 * @phpstan-import-type RegisterRow from \App\Application\Auditor\Contracts\AuditReportRegisterStore
 *
 * @phpstan-type Page array{rows: list<RegisterRow>, chip: string, roles: list<string>, permissions: list<string>}
 */
class StaffAuditReportRegisterResource extends JsonResource
{
    /** Each view and the states it lists. */
    private const array CHIPS = [
        'all' => null,
        'owed' => ['not_started', 'in_progress', 'overdue', 'returned'],
        'awaiting_cosign' => ['awaiting_cosign'],
        'published' => ['published'],
        'disputed' => ['disputed', 'escalated'],
    ];

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var Page $page */
        $page = $this->resource;
        $rows = $page['rows'];
        $manage = in_array('audit.assignments.manage', $page['permissions'], true);
        $today = now('Africa/Kigali')->format('Y-m-d');
        $in = fn (?array $states): array => $states === null ? $rows : array_values(array_filter($rows, fn (array $row): bool => in_array($row['status'], $states, true)));
        $timed = array_filter($rows, fn (array $row): bool => $row['sealed_at'] !== null && $row['due_at'] !== null);
        $onTime = count(array_filter($timed, fn (array $row): bool => $row['sealed_at'] <= $row['due_at']));

        return ['contract_version' => 'staff-audit-report-register-v1', 'server_time' => now()->toIso8601String(),
            'viewer' => (new StaffViewerResource($page['roles']))->resolve($request),
            'nav' => (new StaffNavigationResource($page['permissions']))->resolve($request),
            'badges' => ['applications' => null, 'disbursements' => null], 'search' => '',
            'stats' => [['key' => 'published', 'value' => ['kind' => 'count', 'value' => count($in(['published']))]],
                ['key' => 'awaiting_cosign', 'value' => ['kind' => 'count', 'value' => count($in(['awaiting_cosign']))]],
                ['key' => 'overdue', 'value' => ['kind' => 'count', 'value' => count($in(['overdue']))]],
                ['key' => 'on_time', 'value' => $timed === [] ? ['kind' => 'text', 'value' => '—'] : ['kind' => 'percent', 'value' => (string) intdiv($onTime * 100, count($timed))]]],
            'chips' => array_map(fn (string $chip): array => ['key' => $chip, 'count' => count($in(self::CHIPS[$chip])),
                'link' => ['url' => route('staff.reports.index', $chip === 'all' ? [] : ['chip' => $chip], false), 'method' => 'get'],
                'active' => $chip === $page['chip']], array_keys(self::CHIPS)),
            'rows' => array_map(fn (array $row): array => [
                'id' => $row['assignment_id'], 'business' => $row['business'], 'kind' => $row['kind'],
                'period' => $row['period'] === null ? null : $row['period'].'-01', 'auditor' => $row['auditor'], 'licence' => $row['licence'],
                'status' => $row['status'], 'due_at' => $row['due_at'], 'cosign_by' => $row['cosign_by'], 'sealed_at' => $row['sealed_at'],
                'digest' => $row['digest'] === null ? null : substr($row['digest'], 0, 12), 'variance_pct' => $row['variance_pct'],
                'owes' => $row['owes'], 'reminded_at' => $row['reminded_at'],
                'links' => [
                    'pdf' => $row['report_id'] !== null && $row['sealed_at'] !== null
                        ? ['url' => route('staff.reports.pdf', ['report' => $row['report_id']], false), 'method' => 'get'] : null,
                    'remind' => $manage && $row['owes'] !== null
                        && ($row['reminded_at'] === null || CarbonImmutable::parse($row['reminded_at'])->setTimezone('Africa/Kigali')->format('Y-m-d') !== $today)
                        ? ['url' => route('staff.reports.remind', ['assignment' => $row['assignment_id']], false), 'method' => 'post'] : null,
                ]], $in(self::CHIPS[$page['chip']]))];
    }
}
