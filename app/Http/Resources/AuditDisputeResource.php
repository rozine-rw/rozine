<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditDisputeResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $page */
        $page = $this->resource;
        $prefix = ($request->routeIs('api.*') ? 'api.v1.' : '').'staff.audit.disputes.';
        $parameters = ['assignment' => $page['assignment_id'], 'report' => $page['report_id']];
        $command = match ($page['status']) {
            'disputed' => 'escalate', 'escalated' => 'resolve', default => null,
        };
        $canManage = ! $request->routeIs('api.*') || $request->user()?->tokenCan('staff:audit:manage');
        $operation = ['url' => route($prefix.'operations.show', ['request_id' => '00000000-0000-0000-0000-000000000000'], false), 'method' => 'get'];
        $operation['url'] = str_replace('00000000-0000-0000-0000-000000000000', '{request_id}', $operation['url']);

        return [...$page, 'dispute' => self::project($page['dispute'], $request, 'staff', $parameters),
            'allowed_actions' => $canManage && $command !== null ? ['audit.dispute.'.$command] : [],
            'actions' => ['escalate' => $canManage && $command === 'escalate' ? ['url' => route($prefix.'escalate', $parameters, false), 'method' => 'post'] : null,
                'resolve' => $canManage && $command === 'resolve' ? ['url' => route($prefix.'resolve', $parameters, false), 'method' => 'post'] : null],
            'links' => ['current' => ['url' => route($prefix.'show', $parameters, false), 'method' => 'get'], 'operation' => $operation]];
    }

    /** @param 'business'|'auditor'|'staff' $role
     * @param  array<string, mixed>|null  $dispute
     * @param  array<string, string>  $parameters
     * @return array<string, mixed>|null
     */
    public static function project(?array $dispute, Request $request, string $role, array $parameters): ?array
    {
        if ($dispute === null) {
            return null;
        }
        $prefix = ($request->routeIs('api.*') ? 'api.v1.' : '').match ($role) {
            'business' => 'business.audit-reports.disputes.', 'auditor' => 'auditor.reports.disputes.', 'staff' => 'staff.audit.disputes.',
        };
        $dispute['proof_files'] = array_map(fn (array $proof): array => [...$proof, 'download' => [
            'url' => route($prefix.'proofs.show', [...$parameters, 'proof' => $proof['id']], false), 'method' => 'get']], $dispute['proof_files']);
        if ($dispute['amendment'] !== null) {
            $dispute['amendment']['link'] = ['url' => route(($request->routeIs('api.*') ? 'api.v1.' : '')
                .match ($role) {
                    'business' => 'business.audit-reports.show', 'auditor' => 'auditor.reports.show', 'staff' => 'staff.audit.disputes.show'
                },
                [...match ($role) {
                    'business' => ['business' => $parameters['business']], 'staff' => ['assignment' => $parameters['assignment']], 'auditor' => []
                }, 'report' => $dispute['amendment']['report_id']], false), 'method' => 'get'];
        }

        return $dispute;
    }
}
