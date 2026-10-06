<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `C3AdminDisbursementsProps` (staff-disbursement-v1). Every action comes from the server's
 * per-viewer `allowed_actions`, never a role label, and an API token acts only with its manage
 * ability. The step-up route is listed only while this viewer may approve this disbursement.
 */
class StaffDisbursementsResource extends JsonResource
{
    private const array ROUTES = ['authorize' => 'authorize', 'approve' => 'approve', 'reject' => 'reject', 'hold' => 'hold',
        'release_hold' => 'release-hold', 'requery' => 'requery'];

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $page */
        $page = $this->resource;
        $prefix = $request->routeIs('api.*') ? 'api.v1.' : '';
        $manage = ! $request->routeIs('api.*') || $request->user()?->tokenCan('staff:disbursements:manage') === true;
        $link = fn (string $name, array $parameters = []): array => ['url' => route($prefix.'staff.disbursements.'.$name, $parameters, false), 'method' => 'get'];
        $permissions = $page['permissions'];
        $name = (string) $request->user()?->name;
        $detail = $page['disbursement'] === null ? null : $this->detail($request, $page['disbursement'], $manage, $prefix, $link('index'));

        return ['contract_version' => 'staff-disbursement-v1', 'staff_access_version' => 'staff-access-v1', 'server_time' => now()->toIso8601String(),
            'viewer' => ['id' => (string) $request->user()?->getAuthIdentifier(), 'name' => $name, 'email' => (string) $request->user()?->email,
                'initials' => mb_strtoupper(mb_substr($name, 0, 1)), 'role' => 'approver'],
            'nav' => ['applications' => in_array('applications.review', $permissions, true) ? ['url' => route($prefix.'staff.applications.index', [], false), 'method' => 'get'] : null,
                'launcher' => ['url' => route('dashboard', [], false), 'method' => 'get'], 'today' => null, 'disbursements' => $link('index'),
                'repayments' => null, 'businesses' => null, 'investors' => null, 'auditors' => null, 'staff' => null,
                'ledger' => in_array('ledger.view', $permissions, true) ? ['url' => route($prefix.'staff.ledger.index', [], false), 'method' => 'get'] : null, 'events' => null],
            'badges' => ['applications' => null, 'disbursements' => $page['awaiting_second_approver']], 'search' => '',
            'allowed_actions' => $detail['allowed_actions'] ?? [],
            'disbursements' => array_map(fn (array $row): array => [...$row, 'link' => $link('show', ['disbursement' => $row['id']])], $page['rows']),
            'pagination' => ['next' => $page['next_cursor'] === null ? null : $link('index', ['before' => $page['next_cursor']])],
            'awaiting_second_approver' => $page['awaiting_second_approver'], 'disbursement' => $detail, 'refusal' => $page['refusal']];
    }

    /**
     * The operation lookup; without a request id its url keeps the literal `{request_id}` token.
     *
     * @return array{url: string, method: string}
     */
    public static function lookup(Request $request, ?string $requestId = null, ?string $command = null): array
    {
        $placeholder = '00000000-0000-0000-0000-000000000000';
        $query = $command === null ? [] : ['command' => $command];
        $url = route(($request->routeIs('api.*') ? 'api.v1.' : '').'staff.disbursements.operations.show', ['request_id' => $requestId ?? $placeholder, ...$query], false);

        return ['url' => $requestId === null ? str_replace($placeholder, '{request_id}', $url) : $url, 'method' => 'get'];
    }

    /**
     * @param  array<string, mixed>  $detail
     * @param  array{url: string, method: string}  $close
     * @return array<string, mixed>
     */
    private function detail(Request $request, array $detail, bool $manage, string $prefix, array $close): array
    {
        $allowed = $manage ? $detail['allowed_actions'] : [];
        $actions = [];
        foreach (self::ROUTES as $key => $route) {
            if (in_array('disbursement.'.$key, $allowed, true)) {
                $actions[$key] = ['url' => route($prefix.'staff.disbursements.'.$route, ['disbursement' => $detail['id']], false), 'method' => 'post'];
            }
        }
        $stepUp = $manage && $detail['step_up_allowed'] ? ['url' => route($prefix.'staff.disbursements.step-up', ['disbursement' => $detail['id']], false), 'method' => 'post'] : null;
        $intent = $detail['intent'];
        if ($intent !== null) {
            $intent['receipt']['link'] = self::lookup($request, $intent['receipt']['request_id'], 'disbursement.approve');
        }
        $refund = $detail['refund'];
        if ($refund !== null) {
            $refund['receipt']['link'] = self::lookup($request, $refund['receipt']['request_id'], 'disbursement.approve');
        }
        $independence = $detail['independence'];
        unset($detail['step_up_allowed'], $detail['independence']);

        return [...$detail, 'intent' => $intent, 'refund' => $refund, 'allowed_actions' => $allowed, 'actions' => (object) $actions,
            'step_up' => ['purpose' => 'disbursement.approve', 'route' => $stepUp],
            'independence' => $independence,
            'links' => ['close' => $close, 'ledger' => null, 'operation' => self::lookup($request)]];
    }
}
