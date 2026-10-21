<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Identity\GetStaffAccess;
use App\Http\Resources\StaffNavigationResource;
use App\Http\Resources\StaffPolicyRulesResource;
use App\Http\Resources\StaffViewerResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A console section the design shows but whose screen is not wired yet: any staff member may open
 * it, and it renders the console frame with the section's title and an empty state. Nothing is
 * read beyond the viewer's own access, so nothing can be invented. Policies also lists the rules the
 * domain enforces.
 */
class StaffSectionController extends Controller
{
    /** URL slug to the frame's section key. */
    public const SECTIONS = [
        'secondary-market' => 'secondary_market', 'reports' => 'reports', 'risk' => 'risk',
        'compliance' => 'compliance', 'ratings' => 'ratings', 'deferrals' => 'deferrals', 'plus' => 'plus',
        'finance' => 'finance', 'messaging' => 'messaging', 'academies' => 'academies', 'app-control' => 'app_control', 'engines' => 'engines',
        'policies' => 'policies',
    ];

    /** The role the frame names, most privileged first. */
    private const ROLES = ['superadmin', 'compliance', 'approver', 'analyst'];

    /** Compliance has one dashboard: whoever may review investor identities is sent to that live queue. */
    public function show(Request $request, GetStaffAccess $access, string $section): Response|RedirectResponse
    {
        $staff = $access->handle((int) $request->user()?->getAuthIdentifier(), true);
        /** @var list<string> $roles */
        $roles = $staff['roles'];
        /** @var list<string> $permissions */
        $permissions = $staff['allowed_actions'];
        if ($section === 'compliance' && in_array('investors.verify', $permissions, true)) {
            return to_route('staff.investor-verifications.index');
        }
        $name = (string) $request->user()?->name;

        return Inertia::render('admin/section', ['server_time' => now()->toIso8601String(),
            'viewer' => ['id' => (string) $request->user()?->getAuthIdentifier(), 'name' => $name, 'email' => (string) $request->user()?->email,
                'initials' => StaffViewerResource::initials($name), 'role' => current(array_intersect(self::ROLES, $roles)) ?: 'analyst'],
            'nav' => (new StaffNavigationResource($permissions))->resolve($request),
            'badges' => ['applications' => null, 'disbursements' => null], 'search' => '', 'section' => self::SECTIONS[$section],
            'rules' => $section === 'policies' ? StaffPolicyRulesResource::rows() : []]);
    }
}
