<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `AdminLedgerProps` (staff-ledger-v1) for both transports: the read-only drill-down in the staff
 * frame. A section without a live route is null, and no correction or contra action is offered.
 */
class StaffLedgerResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $page */
        $page = $this->resource;
        $prefix = $request->routeIs('api.*') ? 'api.v1.' : '';
        $ledger = fn (array $query = []): array => ['url' => route($prefix.'staff.ledger.index', array_filter($query, fn (mixed $value): bool => $value !== null && $value !== ''), false), 'method' => 'get'];
        $search = $page['search'] === '' ? null : $page['search'];
        $permissions = $page['permissions'];
        $name = (string) $request->user()?->name;
        $entry = $page['entry'];

        return ['contract_version' => 'staff-ledger-v1', 'staff_access_version' => 'staff-access-v1', 'server_time' => now()->toIso8601String(),
            'viewer' => ['id' => (string) $request->user()?->getAuthIdentifier(), 'name' => $name, 'email' => (string) $request->user()?->email,
                'initials' => mb_strtoupper(mb_substr($name, 0, 1)), 'role' => in_array('applications.review', $permissions, true) ? 'approver' : 'analyst'],
            'nav' => ['applications' => in_array('applications.review', $permissions, true) ? ['url' => route($prefix.'staff.applications.index', [], false), 'method' => 'get'] : null,
                'launcher' => ['url' => route('dashboard', [], false), 'method' => 'get'], 'today' => null,
                'disbursements' => in_array('disbursements.view', $permissions, true) ? ['url' => route($prefix.'staff.disbursements.index', [], false), 'method' => 'get'] : null,
                'repayments' => null, 'businesses' => null, 'investors' => null, 'auditors' => null, 'staff' => null, 'ledger' => $ledger(), 'events' => null],
            'badges' => ['applications' => null, 'disbursements' => null], 'search' => $page['search'],
            'entries' => array_map(fn (array $row): array => [...$row, 'link' => $ledger(['search' => $search, 'before' => $page['before'] ?? null, 'entry' => $row['id']])],
                $page['entries']),
            'total' => $page['total'], 'more' => $page['next_before'] === null ? null : $ledger(['search' => $search, 'before' => $page['next_before']]),
            'entry' => $entry === null ? null : [...$entry['row'], 'link' => $ledger(['search' => $search, 'entry' => $entry['row']['id']]),
                'operation_id' => $entry['operation_id'], 'posted_by' => $entry['posted_by'], 'postings' => $entry['postings'], 'totals' => $entry['totals'],
                'balanced' => $entry['balanced'], 'contra_of' => null, 'contra_by' => null,
                'links' => ['close' => $ledger(['search' => $search, 'before' => $page['before'] ?? null]), 'events' => null]]];
    }
}
