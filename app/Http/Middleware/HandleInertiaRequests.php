<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Application\Environment\EnvironmentIsolation;
use App\Application\Primary\Contracts\PrimaryPurchaseIndex;
use App\Application\Primary\GetInvestorCart;
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
            // The floating "Held for checkout" countdown on every Investor page but the checkout itself.
            'checkout_holds' => fn (): ?array => $this->checkoutHolds($request),
            'nonLiveEnvironment' => Inertia::always(fn (): ?string => app(EnvironmentIsolation::class)->isIsolated()
                ? app(EnvironmentIsolation::class)->profile()
                : null),
        ];
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
}
