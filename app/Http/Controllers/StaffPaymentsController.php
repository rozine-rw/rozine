<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Identity\GetStaffAccess;
use App\Application\Operations\BrowsePayments;
use App\Application\Operations\PlatformPolicies;
use App\Http\Requests\Staff\ListPaymentsRequest;
use App\Http\Resources\StaffPaymentsResource;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The designed Payment Center (`payments.view`): money moved over the last fourteen days and the
 * withdrawals whose payout is in flight, searched. Payouts follow the provider's verified outcome,
 * so nothing here releases, holds or reverses one.
 */
class StaffPaymentsController extends Controller
{
    /** The design shows a first page and opens the rest on request. */
    private const LIMIT = 50;

    public function __invoke(ListPaymentsRequest $request, BrowsePayments $payments, GetStaffAccess $access): Response|StaffPaymentsResource
    {
        $actorId = (int) $request->user()?->getAuthIdentifier();
        $from = $request->validated('from');
        $to = $request->validated('to');
        $page = $payments->handle($actorId, $from === null ? null : (string) $from, $to === null ? null : (string) $to);
        $staff = $access->handle($actorId);

        $resource = new StaffPaymentsResource([...$page, 'policy' => PlatformPolicies::fees(), 'search' => trim((string) $request->validated('q', '')),
            'limit' => (int) $request->validated('limit', self::LIMIT), 'roles' => $staff['roles'], 'permissions' => $staff['allowed_actions']]);

        return $request->routeIs('api.*') ? $resource : Inertia::render('admin/payments', $resource->resolve($request));
    }
}
