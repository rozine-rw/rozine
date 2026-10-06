<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Compliance's Investor identity queue in the Admin console frame. Only the Investors section is
 * served here; every other sidebar entry is null. A case's documents are linked, never inlined.
 *
 * @phpstan-type Entry array{id: string, revision: int, status: string, submitted_at: string, decided_at: string|null, name: string, email: string, id_type: string}
 * @phpstan-type Review array{
 *     id: string, revision: int, status: string, submitted_at: string|null, account: array{name: string, email: string},
 *     state: array{date_of_birth: string, id_type: string, id_number: string, decision: array{outcome: string, reason: string, decided_at: string}|null},
 *     documents: list<array{id: string, slot: string, filename: string, media_type: string, size_bytes: int, sha256: string, uploaded_at: string, current: bool}>,
 *     history: list<array{revision: int, status: string, command: string, reason: string|null, at: string}>,
 *     allowed_actions: list<string>
 * }
 * @phpstan-type Page array{
 *     tab: string, search: string, limit: int, before: string|null, entries: list<Entry>, next_cursor: string|null,
 *     counts: array{submitted: int, decided: int}, review: Review|null, roles: list<string>
 * }
 */
class StaffInvestorVerificationsResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var Page $page */
        $page = $this->resource;
        $prefix = $request->routeIs('api.*') ? 'api.v1.' : '';
        $link = fn (array $query): array => ['url' => route($prefix.'staff.investor-verifications.index', $query, false), 'method' => 'get'];
        $filters = array_filter(['tab' => $page['tab'], 'search' => $page['search']], fn (string $value): bool => $value !== '');
        $position = array_filter([...$filters, 'before' => $page['before']], fn (?string $value): bool => $value !== null);
        $name = (string) $request->user()?->name;
        // Only compliance and superadmin hold investors.verify, so the queue is never shown to another role.
        $role = in_array('superadmin', $page['roles'], true) ? 'superadmin' : 'compliance';

        return ['contract_version' => 'staff-investor-verifications-v1', 'server_time' => now()->toIso8601String(),
            'viewer' => ['id' => (string) $request->user()?->getAuthIdentifier(), 'name' => $name, 'email' => (string) $request->user()?->email,
                'initials' => mb_strtoupper(mb_substr($name, 0, 1)), 'role' => $role],
            'nav' => ['investors' => $link([]), 'launcher' => ['url' => route('dashboard', [], false), 'method' => 'get'],
                'today' => null, 'applications' => null, 'disbursements' => null, 'repayments' => null, 'businesses' => null,
                'auditors' => null, 'staff' => null, 'ledger' => null, 'events' => null],
            'badges' => ['applications' => null, 'disbursements' => null], 'search' => $page['search'],
            'active_tab' => $page['tab'],
            'tabs' => array_map(fn (string $tab): array => ['key' => $tab, 'count' => $page['counts'][$tab], 'link' => $link([...$filters, 'tab' => $tab])], ['submitted', 'decided']),
            'entries' => array_map(fn (array $entry): array => [...$entry, 'selected' => $entry['id'] === ($page['review']['id'] ?? null),
                'link' => $link([...$position, 'verification' => $entry['id']])], $page['entries']),
            'pagination' => ['next' => $page['next_cursor'] === null ? null : $link([...$filters, 'before' => $page['next_cursor']])],
            'review' => $page['review'] === null ? null : self::review($page['review'], $link($position), $prefix)];
    }

    /**
     * @param  Review  $review
     * @param  array{url: string, method: string}  $close
     * @return array<string, mixed>
     */
    private static function review(array $review, array $close, string $prefix): array
    {
        $action = fn (string $name): array => ['url' => route($prefix.'staff.investor-verifications.'.$name, ['verification' => $review['id']], false), 'method' => 'post'];

        return ['id' => $review['id'], 'revision' => $review['revision'], 'status' => $review['status'], 'submitted_at' => $review['submitted_at'],
            'account' => $review['account'], 'date_of_birth' => $review['state']['date_of_birth'], 'id_type' => $review['state']['id_type'],
            'id_number' => $review['state']['id_number'], 'decision' => $review['state']['decision'],
            'documents' => array_map(fn (array $document): array => [...$document, 'link' => ['url' => route($prefix.'staff.investor-verifications.document',
                ['verification' => $review['id'], 'document' => $document['id']], false), 'method' => 'get']], $review['documents']),
            'history' => $review['history'], 'links' => ['close' => $close],
            'actions' => (object) array_combine($review['allowed_actions'], array_map($action, $review['allowed_actions']))];
    }
}
