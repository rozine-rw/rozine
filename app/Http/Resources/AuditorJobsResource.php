<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @phpstan-import-type AuditApplication from \App\Application\Business\Contracts\BusinessApplicationStore
 * @phpstan-import-type Summary from \App\Application\Auditor\GetAuditEngagementSummary
 *
 * @phpstan-type Page array{data: list<AuditApplication>, next_cursor: string|null, identity_context_revision: int, limit: int, engagement: Summary}
 */
class AuditorJobsResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var Page $page */
        $page = $this->resource;
        $eligible = [];
        $assigned = [];
        foreach ($page['data'] as $record) {
            $job = self::job($record);
            if ($job['state'] === 'offered') {
                $eligible[] = [...$job, 'sector' => self::sector($record['work']['business']['industry']),
                    'offered_at' => $record['work']['assignment']['offered_at'], 'requested' => self::money($record['application']['target'] ?? null),
                    'term_months' => $record['application']['term_months'] ?? null, 'dscr' => null, 'map' => null,
                    'link' => self::link('auditor.jobs.show', ['assignment' => $job['id']]), 'actions' => self::actions($request, $job['id'])];
            } else {
                $assigned[] = [...$job, 'step' => null, 'steps' => null,
                    'status' => $job['deadline'] !== null && $job['deadline']['due_at'] <= now('UTC')->format('Y-m-d\TH:i:s\Z') ? 'overdue' : 'in_progress',
                    'link' => self::link('auditor.jobs.show', ['assignment' => $job['id']])];
            }
        }

        return [...self::envelope($page['identity_context_revision']), 'radius_km' => 30, 'flash_hours' => 24,
            'engagement' => (new AuditorEngagementSummaryResource($page['engagement']))->resolve($request),
            'eligible' => $eligible, 'assigned' => $assigned, 'monthly' => null,
            'decline_options' => self::declineOptions(), 'outcome' => null, 'links' => self::links($request),
            'pagination' => self::pagination($request, 'jobs.index', $page['next_cursor'], $page['limit'])];
    }

    /** @return array<string, mixed> */
    public static function envelope(int $contextRevision): array
    {
        return ['contract_version' => 'auditor-filing-v1', 'identity_context_revision' => $contextRevision,
            'server_time' => now()->toIso8601String(), 'allowed_actions' => []];
    }

    /**
     * @param  AuditApplication  $record
     * @return array{id: string, revision: int, kind: string, business: string, district: string, distance_km: string|null, state: string, deadline: array{due_at: string}|null, accept_by: string|null, complete_by: string|null, reassigned_from: null, allowed_actions: list<string>}
     */
    public static function job(array $record): array
    {
        $assignment = $record['work']['assignment'];
        $accepted = $assignment['status'] === 'accepted';
        $due = $assignment['kind'] === 'flash' ? $assignment['complete_by'] : $assignment['visit_by'];
        $distance = $record['work']['distance_upper_bound_m'];

        return ['id' => $assignment['id'], 'revision' => $assignment['revision'], 'kind' => $assignment['kind'] === 'routine' ? 'monthly' : 'flash',
            'business' => $record['work']['business']['name'], 'district' => $record['work']['business']['district'],
            'distance_km' => $distance === null ? null : number_format($distance / 1000, 1, '.', ''),
            'state' => $accepted ? 'assigned' : 'offered', 'deadline' => $accepted && $due !== null ? ['due_at' => $due] : null,
            'accept_by' => $accepted ? null : $assignment['accept_by'], 'complete_by' => $assignment['complete_by'],
            'reassigned_from' => null, 'allowed_actions' => $assignment['allowed_actions']];
    }

    /**
     * A finding's whole-number figures grouped for reading: "38000000" reads "38,000,000" and
     * "-1600000" reads "-1,600,000". Only the displayed sentence changes; the sealed values and
     * their digest do not. Anything else, such as an observed status, is left as it is.
     *
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    public static function figures(array $values): array
    {
        return array_map(function (mixed $value): mixed {
            if (! is_string($value) || preg_match('/^-?\d+$/', $value) !== 1) {
                return $value;
            }
            $digits = ltrim($value, '-');

            return ($digits === $value ? '' : '-').preg_replace('/\B(?=(\d{3})+(?!\d))/', ',', $digits);
        }, $values);
    }

    /** @return array{currency: 'RWF', amount: string}|null */
    public static function money(?string $amount): ?array
    {
        return $amount === null ? null : ['currency' => 'RWF', 'amount' => $amount];
    }

    public static function sector(string $industry): ?string
    {
        $sector = strtolower(trim($industry));

        return in_array($sector, ['agriculture', 'logistics', 'manufacturing', 'retail', 'energy', 'technology', 'services'], true) ? $sector : null;
    }

    /** @return list<array{code: string, label: string, requires_explanation: bool}> */
    public static function declineOptions(): array
    {
        return [
            ['code' => 'unavailable', 'label' => __('Unavailable'), 'requires_explanation' => false],
            ['code' => 'capacity', 'label' => __('At capacity'), 'requires_explanation' => false],
            ['code' => 'location', 'label' => __('Location'), 'requires_explanation' => false],
            ['code' => 'other', 'label' => __('Other'), 'requires_explanation' => true],
        ];
    }

    /** @return array<string, array{url: string, method: 'post'}> */
    public static function actions(Request $request, string $assignmentId): array
    {
        $actions = [];
        foreach (['accept', 'decline', 'conflict'] as $decision) {
            $actions[$decision] = ['url' => route(self::prefix($request).'jobs.'.$decision, ['assignment' => $assignmentId], false), 'method' => 'post'];
        }

        return $actions;
    }

    /** @return array<string, array{url: string, method: 'get'}|null> */
    public static function links(Request $request): array
    {
        $placeholder = '00000000-0000-0000-0000-000000000000';
        $operation = self::link(self::prefix($request).'jobs.operations.show', ['request_id' => $placeholder]);
        $operation['url'] = str_replace($placeholder, '{request_id}', $operation['url']);

        return ['home' => $request->routeIs('api.*') ? null : self::link('auditor.home'), 'jobs' => self::link(self::prefix($request).'jobs.index'), 'portfolio' => null,
            'profile' => self::link(self::prefix($request).'profile'), 'launcher' => self::link($request->routeIs('api.*') ? 'api.v1.identity.show' : 'dashboard'),
            'conflicts' => self::link(self::prefix($request).'conflicts.index'), 'operation' => $operation];
    }

    /** @return array{next: array{url: string, method: 'get'}|null} */
    public static function pagination(Request $request, string $route, ?string $cursor, int $limit): array
    {
        return ['next' => $cursor === null ? null : self::link(self::prefix($request).$route, ['before' => $cursor, 'limit' => $limit])];
    }

    /**
     * @param  array<string, string|int>  $parameters
     * @return array{url: string, method: 'get'}
     */
    public static function link(string $route, array $parameters = []): array
    {
        return ['url' => route($route, $parameters, false), 'method' => 'get'];
    }

    private static function prefix(Request $request): string
    {
        return $request->routeIs('api.*') ? 'api.v1.auditor.' : 'auditor.';
    }
}
