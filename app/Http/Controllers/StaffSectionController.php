<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Business\BrowseBusinessDirectory;
use App\Application\Identity\GetStaffAccess;
use App\Application\Operations\BrowseLedger;
use App\Application\Operations\EngineSettings;
use App\Application\Operations\PlatformPolicies;
use App\Application\Operations\ReadSandboxUsage;
use App\Http\Resources\StaffNavigationResource;
use App\Http\Resources\StaffViewerResource;
use Brick\Math\BigInteger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A console section the design shows but whose screen is not wired yet: any staff member may open
 * it, and it renders the console frame with the section's title and an empty state. Nothing is
 * read beyond the viewer's own access, so nothing can be invented, except where a section has a live
 * read: Policies lists the rules the domain enforces, Finance shows the ledger's fee revenue to
 * whoever may read the ledger, Engines and Ratings show underwriters the engine's settings and the
 * rating scorecard, Ratings lists every rated Business to whoever may view Businesses, and App
 * Control shows how much of the sandbox's limits is used.
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

    /** The most Businesses the rating book reads at once. */
    private const BOOK_LIMIT = 500;

    /** The role the frame names, most privileged first. */
    private const ROLES = ['superadmin', 'compliance', 'approver', 'analyst'];

    /** Compliance has one dashboard: whoever may review investor identities is sent to that live queue. */
    public function show(Request $request, GetStaffAccess $access, BrowseLedger $ledger, BrowseBusinessDirectory $businesses, ReadSandboxUsage $sandbox,
        string $section): Response|RedirectResponse
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
        $book = $section === 'ratings' && in_array('businesses.view', $permissions, true)
            ? self::book($businesses->directory((int) $request->user()?->getAuthIdentifier(), 'all', 'name', '', '', self::BOOK_LIMIT)['rows']) : [];

        return Inertia::render('admin/section', ['server_time' => now()->toIso8601String(),
            'viewer' => ['id' => (string) $request->user()?->getAuthIdentifier(), 'name' => $name, 'email' => (string) $request->user()?->email,
                'initials' => StaffViewerResource::initials($name), 'role' => current(array_intersect(self::ROLES, $roles)) ?: 'analyst'],
            'nav' => (new StaffNavigationResource($permissions))->resolve($request),
            'badges' => ['applications' => null, 'disbursements' => null], 'search' => '', 'section' => self::SECTIONS[$section],
            'rules' => $section === 'policies' ? PlatformPolicies::rules() : [], ...$finance, ...$book,
            ...($section === 'app-control' ? ['sandbox' => $sandbox->handle((int) $request->user()?->getAuthIdentifier())] : []),
            'engine' => match (true) {
                ! $underwriter => [],
                $section === 'engines' => EngineSettings::engines(),
                $section === 'ratings' => EngineSettings::scorecard(),
                default => [],
            }]);
    }

    /**
     * The rating book: every Business with a published rating, by name, and how many there are. No
     * rating history is kept yet, so a band change, its trend and its reason are not read.
     *
     * @param  list<array{business_id: string, name: string, sector: string, rating: array{band: string, score: string}|null}>  $rows
     * @return array{counts: array{rated: int}, book: list<array{key: string, business: string, sector: string, rating: array{band: string, score: string}}>}
     */
    private static function book(array $rows): array
    {
        $book = [];
        foreach ($rows as $row) {
            if ($row['rating'] !== null) {
                $book[] = ['key' => $row['business_id'], 'business' => $row['name'], 'sector' => $row['sector'], 'rating' => $row['rating']];
            }
        }

        return ['counts' => ['rated' => count($book)], 'book' => $book];
    }

    /**
     * Finance's figures for whoever may read the ledger: all fee revenue and the primary market's
     * part of it, and one row per stream with its rate, who pays it and what the ledger holds for
     * it. Investor and secondary-market fees are not charged yet, so their tiles stay blank and the
     * earnings and resale fees show their BRS rate with nothing collected (no ledger account takes
     * them yet). A withdrawal fee and a loss reserve do not exist (LED-R04, GEN-R01), so they show
     * as none.
     *
     * @param  array{platform_fee: string, audit_partner_share: string, deposit_fees: string}  $revenue
     * @return array{figures: array<string, array{currency: string, amount: string}>, streams: list<array{key: string, rate: array{key: string, value: string, term?: string}|null, paid_by: string|null, collected: array{currency: string, amount: string}|null}>}
     */
    private static function finance(array $revenue): array
    {
        $money = fn (string $amount): array => ['currency' => 'RWF', 'amount' => $amount];
        $rules = array_column(PlatformPolicies::rules(), null, 'key');
        $rate = fn (string $key): array => array_intersect_key($rules[$key], array_flip(['key', 'value', 'term']));

        return ['figures' => ['revenue' => $money((string) BigInteger::of($revenue['platform_fee'])->plus($revenue['deposit_fees'])),
            'primary' => $money($revenue['platform_fee'])],
            'streams' => [['key' => 'platform_fee', 'rate' => $rate('platform_fee'), 'paid_by' => 'business', 'collected' => $money($revenue['platform_fee'])],
                ['key' => 'audit_partner_share', 'rate' => $rate('cpa_share'), 'paid_by' => 'business', 'collected' => $money($revenue['audit_partner_share'])],
                ['key' => 'deposit_fees', 'rate' => null, 'paid_by' => 'investor', 'collected' => $money($revenue['deposit_fees'])],
                ['key' => 'earnings_fee', 'rate' => $rate('earnings_fee'), 'paid_by' => 'investor', 'collected' => null],
                ['key' => 'resale_fee', 'rate' => $rate('resale_fee'), 'paid_by' => 'seller', 'collected' => null],
                ['key' => 'withdrawal_fee', 'rate' => $rate('withdrawal_fee'), 'paid_by' => null, 'collected' => null],
                ['key' => 'loss_reserve', 'rate' => $rate('loss_reserve'), 'paid_by' => null, 'collected' => null]]];
    }
}
