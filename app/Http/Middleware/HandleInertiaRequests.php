<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Application\Environment\EnvironmentIsolation;
use App\Application\Identity\GetIdentityContext;
use App\Application\Identity\GetStaffAccess;
use App\Application\Primary\AcknowledgeInvestorRisks;
use App\Application\Primary\Contracts\PrimaryPurchaseIndex;
use App\Application\Primary\Contracts\RiskAcknowledgements;
use App\Application\Primary\GetInvestorCart;
use App\Domain\Identity\IdentityViolation;
use App\Domain\Operations\CommandRejection;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'locale' => app()->getLocale(),
            // The Investor app's Cart tab badge; every other page has no cart.
            'cart_count' => fn (): ?int => $request->routeIs('investor.*') && $request->user() !== null
                ? app(GetInvestorCart::class)->count((int) $request->user()->getAuthIdentifier()) : null,
            // The floating "Held for checkout" countdown on every Investor page but the checkout itself,
            // sent on partial reloads too so a change beacon drops a hold released or confirmed elsewhere.
            'checkout_holds' => Inertia::always(fn (): ?array => $this->checkoutHolds($request)),
            // KYC-R05: the risk statements still to acknowledge, asked once at app open, never inside checkout.
            'risk_acknowledgement' => fn (): ?array => $this->riskAcknowledgement($request),
            // Account settings sit outside the console (#618), so staff get their way back to it there.
            'staff_console' => fn (): ?array => $request->routeIs('profile.edit', 'security.edit', 'appearance.edit') && $this->opensConsole($request)
                ? ['url' => route('staff.dashboard', [], false), 'method' => 'get'] : null,
            'nonLiveEnvironment' => Inertia::always(fn (): ?string => app(EnvironmentIsolation::class)->isIsolated()
                ? app(EnvironmentIsolation::class)->profile()
                : null),
        ];
    }

    /** Whether the signed-in account opens the Admin console, as the launcher decides it (settings routes require sign-in). */
    private function opensConsole(Request $request): bool
    {
        $staff = app(GetStaffAccess::class)->handle((int) $request->user()?->getAuthIdentifier());

        return ($staff['can_open_admin'] ?? false) === true && in_array('admin.open', (array) ($staff['allowed_actions'] ?? []), true);
    }

    /**
     * The signed-in Investor's live checkout holds, soonest to lapse first, each linking back to its
     * deal's checkout, with the server time the countdown runs against. Null off the Investor app
     * and on the checkout page, which shows its own hold clock.
     *
     * @return array{server_time: string, holds: list<array{reservation_id: string, deal_name: string, amount: array{currency: string, amount: string}, units: string, expires_at: string, link: array{url: string, method: string}}>}|null
     */
    private function checkoutHolds(Request $request): ?array
    {
        if (! $request->routeIs('investor.*') || $request->routeIs('investor.checkout') || $request->user() === null) {
            return null;
        }
        $holds = app(PrimaryPurchaseIndex::class)->activeHoldsOfUser((int) $request->user()->getAuthIdentifier());
        usort($holds, fn (array $first, array $second): int => strcmp($first['expires_at'], $second['expires_at']));

        return ['server_time' => now()->toIso8601String(), 'holds' => array_map(fn (array $hold): array => [
            'reservation_id' => $hold['reservation_id'], 'deal_name' => $hold['deal_name'], 'amount' => $hold['amount'], 'units' => $hold['units'],
            'expires_at' => $hold['expires_at'],
            'link' => ['url' => route('investor.checkout', ['campaign' => $hold['campaign_id'], 'reservation' => $hold['reservation_id']], false), 'method' => 'get'],
        ], $holds)];
    }

    /**
     * The five risk statements a verified Investor has not yet acknowledged in their current wording
     * (BRS v3.0 KYC-R05), with the command that acknowledges them all with one tick. Null off the
     * Investor app, once acknowledged, and for anyone who is not an active, verified Investor.
     *
     * @return array<string, mixed>|null
     */
    private function riskAcknowledgement(Request $request): ?array
    {
        if (! $request->routeIs('investor.*') || $request->user() === null) {
            return null;
        }
        $partyId = $request->user()->getAttribute('party_id');
        if (is_string($partyId) && app(RiskAcknowledgements::class)->current($partyId)) {
            return null;
        }
        $userId = (int) $request->user()->getAuthIdentifier();
        try {
            $risks = app(AcknowledgeInvestorRisks::class)->read($userId, null);
        } catch (IdentityViolation|CommandRejection) {
            return null;
        }

        return [...$risks, 'identity_context_revision' => (int) app(GetIdentityContext::class)->handle($userId)['context_revision'],
            'action' => ['url' => route('investor.risks.acknowledge', absolute: false), 'method' => 'post']];
    }
}
