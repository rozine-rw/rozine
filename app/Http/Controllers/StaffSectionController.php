<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Identity\GetStaffAccess;
use App\Application\Operations\BrowseLedger;
use App\Application\Operations\EngineSettings;
use App\Application\Operations\PlatformPolicies;
use App\Http\Resources\StaffNavigationResource;
use App\Http\Resources\StaffViewerResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A console section the design shows but whose screen is not wired yet: any staff member may open
 * it, and it renders the console frame with the section's title and an empty state. Nothing is
 * read beyond the viewer's own access, so nothing can be invented, except where a section has a live
 * read: Policies lists the rules the domain enforces, Finance shows the ledger's fee revenue to
 * whoever may read the ledger, and Engines and Ratings show underwriters the engine's settings and
 * the rating scorecard.
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
    public function show(Request $request, GetStaffAccess $access, BrowseLedger $ledger, string $section): Response|RedirectResponse
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
        $finance = $section === 'finance' && in_array('ledger.view', $permissions, true) ? self::finance($ledger->revenue((int) $request->user()?->getAuthIdentifier())) : [];
        $underwriter = in_array('applications.review', $permissions, true);

        return Inertia::render('admin/section', ['server_time' => now()->toIso8601String(),
            'viewer' => ['id' => (string) $request->user()?->getAuthIdentifier(), 'name' => $name, 'email' => (string) $request->user()?->email,
                'initials' => StaffViewerResource::initials($name), 'role' => current(array_intersect(self::ROLES, $roles)) ?: 'analyst'],
            'nav' => (new StaffNavigationResource($permissions))->resolve($request),
            'badges' => ['applications' => null, 'disbursements' => null], 'search' => '', 'section' => self::SECTIONS[$section],
            'rules' => $section === 'policies' ? PlatformPolicies::rules() : [], ...$finance,
            'engine' => match (true) {
                ! $underwriter => [],
                $section === 'engines' => EngineSettings::engines(),
                $section === 'ratings' => EngineSettings::scorecard(),
                default => [],
            }]);
    }

    /**
     * Finance's figures for whoever may read the ledger: all fee revenue and the primary market's
     * part of it, and one row per stream with who pays it. Investor and secondary-market fees are not
     * charged yet, so their tiles stay blank.
     *
     * @param  array{platform_fee: string, audit_partner_share: string, deposit_fees: string}  $revenue
     * @return array{figures: array<string, array{currency: string, amount: string}>, streams: list<array{key: string, paid_by: string, collected: array{currency: string, amount: string}}>}
     */
    private static function finance(array $revenue): array
    {
        $money = fn (string $amount): array => ['currency' => 'RWF', 'amount' => $amount];

        return ['figures' => ['revenue' => $money((string) ((int) $revenue['platform_fee'] + (int) $revenue['deposit_fees'])),
            'primary' => $money($revenue['platform_fee'])],
            'streams' => [['key' => 'platform_fee', 'paid_by' => 'business', 'collected' => $money($revenue['platform_fee'])],
                ['key' => 'audit_partner_share', 'paid_by' => 'business', 'collected' => $money($revenue['audit_partner_share'])],
                ['key' => 'deposit_fees', 'paid_by' => 'investor', 'collected' => $money($revenue['deposit_fees'])]]];
    }
}
