<?php

declare(strict_types=1);

namespace App\Infrastructure\Auditor;

use App\Application\Operations\Contracts\OverdueAudits;
use App\Models\AuditAssignment;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;

/**
 * Unfinished Flash Audits read from `audit_assignments`: their clock sits in the encrypted state,
 * so the open assignments are read and filtered here. A sealed report finishes the audit.
 */
final class EloquentOverdueAudits implements OverdueAudits
{
    private const array OPEN = ['offered', 'accepted', 'operations'];

    /** @return list<array{id: string, business_id: string, business: string, partner: string|null, status: 'offered'|'accepted'|'operations', due_at: string}> */
    public function flashOverdue(DateTimeInterface $at): array
    {
        $now = CarbonImmutable::instance($at)->utc();
        $overdue = [];
        AuditAssignment::query()->whereIn('status', self::OPEN)
            ->whereNotExists(fn ($query) => $query->from('audit_reports')->whereColumn('audit_reports.assignment_id', 'audit_assignments.id')->where('audit_reports.status', 'sealed'))
            ->lazyById(100)->each(function (AuditAssignment $assignment) use ($now, &$overdue): void {
                $completeBy = $assignment->state['complete_by'];
                if ($assignment->state['kind'] !== 'flash' || $completeBy === null || CarbonImmutable::parse($completeBy)->gt($now)) {
                    return;
                }
                $overdue[] = ['id' => $assignment->id, 'business_id' => $assignment->business_id,
                    'status' => in_array($assignment->status, ['offered', 'accepted'], true) ? $assignment->status : 'operations',
                    'party_id' => $assignment->status === 'accepted' ? $assignment->party_id : null, 'due_at' => CarbonImmutable::parse($completeBy)->utc()->toIso8601String()];
            });
        $businesses = DB::table('business_profiles')->whereIn('id', array_column($overdue, 'business_id'))
            ->selectRaw("id, profile->>'name' AS name")->pluck('name', 'id')->all();
        $partners = DB::table('users')->whereIn('party_id', array_filter(array_column($overdue, 'party_id')))
            ->orderBy('id')->get(['party_id', 'name'])->unique('party_id')->pluck('name', 'party_id')->all();
        usort($overdue, fn (array $a, array $b): int => [$a['due_at'], $a['id']] <=> [$b['due_at'], $b['id']]);

        return array_map(fn (array $row): array => ['id' => $row['id'], 'business_id' => $row['business_id'],
            'business' => (string) ($businesses[$row['business_id']] ?? 'Business'), 'partner' => $row['party_id'] === null ? null : (string) ($partners[$row['party_id']] ?? 'Audit Partner'),
            'status' => $row['status'], 'due_at' => $row['due_at']], $overdue);
    }
}
