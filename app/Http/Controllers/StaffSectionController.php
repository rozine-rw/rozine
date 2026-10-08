<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Identity\GetStaffAccess;
use App\Http\Resources\StaffNavigationResource;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A console section the design shows but whose screen is not wired yet: any staff member may open
 * it, and it renders the console frame with the section's title and an empty state. Nothing is
 * read beyond the viewer's own access, so nothing can be invented.
 */
class StaffSectionController extends Controller
{
    /** URL slug to the frame's section key. */
    public const SECTIONS = [
        'notes' => 'notes', 'primary-market' => 'primary_market', 'secondary-market' => 'secondary_market', 'risk' => 'risk',
        'compliance' => 'compliance', 'payments' => 'payments', 'ratings' => 'ratings', 'deferrals' => 'deferrals', 'plus' => 'plus',
        'finance' => 'finance', 'messaging' => 'messaging', 'academies' => 'academies', 'app-control' => 'app_control', 'engines' => 'engines',
        'policies' => 'policies', 'system-health' => 'system_health', 'dashboard' => 'today', 'staff' => 'staff', 'activity' => 'events',
    ];

    /** The role the frame names, most privileged first. */
    private const ROLES = ['superadmin', 'compliance', 'approver', 'analyst'];

    public function show(Request $request, GetStaffAccess $access, string $section): Response
    {
        $staff = $access->handle((int) $request->user()?->getAuthIdentifier(), true);
        /** @var list<string> $roles */
        $roles = $staff['roles'];
        /** @var list<string> $permissions */
        $permissions = $staff['allowed_actions'];
        $name = (string) $request->user()?->name;

        return Inertia::render('admin/section', ['server_time' => now()->toIso8601String(),
            'viewer' => ['id' => (string) $request->user()?->getAuthIdentifier(), 'name' => $name, 'email' => (string) $request->user()?->email,
                'initials' => mb_strtoupper(mb_substr($name, 0, 1)), 'role' => current(array_intersect(self::ROLES, $roles)) ?: 'analyst'],
            'nav' => (new StaffNavigationResource($permissions))->resolve($request),
            'badges' => ['applications' => null, 'disbursements' => null], 'search' => '', 'section' => self::SECTIONS[$section]]);
    }
}
