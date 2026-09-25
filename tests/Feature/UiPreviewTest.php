<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The pages that read the checkpoint 3 contracts (C3 proposal v2), whose fixtures are all synthetic
 * and non-activatable.
 *
 * @return list<string>
 */
function c3PreviewComponents(): array
{
    return [
        'investor/deals', 'investor/deal', 'investor/checkout', 'investor/commitment',
        'investor/portfolio', 'investor/holding', 'investor/wallet',
        'admin/disbursements', 'admin/applications',
        'business/publish', 'business/campaign',
    ];
}

/**
 * Every C3 fixture, keyed by name.
 *
 * @return array<string, array{component: string, synthetic?: bool, props: array<string, mixed>}>
 */
function c3PreviewFixtures(): array
{
    $fixtures = [];

    foreach (glob(dirname(__DIR__, 2).'/resources/fixtures/ui/*.json') ?: [] as $path) {
        /** @var array{component: string, synthetic?: bool, props: array<string, mixed>} $page */
        $page = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

        if (in_array($page['component'], c3PreviewComponents(), true)) {
            $fixtures[basename($path, '.json')] = $page;
        }
    }

    return $fixtures;
}

/**
 * Every command target (`{url, method: 'post'}`) anywhere in a fixture's props.
 *
 * @param  array<mixed>  $props
 * @return list<string>
 */
function c3PreviewCommandUrls(array $props): array
{
    $urls = [];

    $walk = static function (array $node) use (&$walk, &$urls): void {
        if (isset($node['url'], $node['method']) && $node['method'] === 'post' && is_string($node['url'])) {
            $urls[] = $node['url'];
        }

        foreach ($node as $child) {
            if (is_array($child)) {
                $walk($child);
            }
        }
    };

    $walk($props);

    return $urls;
}

test('a UI fixture renders its page with exactly the fixture props', function () {
    $fixture = json_decode(
        (string) file_get_contents(resource_path('fixtures/ui/launcher-ready.json')),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    $this->get('/preview/launcher-ready')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->where('identity', $fixture['props']['identity']),
        );
});

test('every UI fixture names a page and carries its props', function (string $path) {
    $page = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

    expect($page)->toHaveKeys(['component', 'props'])
        ->and($page['component'])->toBeString()->not->toBeEmpty()
        ->and($page['props'])->toBeArray();

    $this->get('/preview/'.basename($path, '.json'))->assertOk();
})->with(fn () => glob(dirname(__DIR__, 2).'/resources/fixtures/ui/*.json'));

test('every C3 fixture is marked synthetic, and the preview route ignores the marker', function (string $name) {
    $fixture = c3PreviewFixtures()[$name];

    expect($fixture)->toHaveKey('synthetic')
        ->and($fixture['synthetic'])->toBeTrue()
        ->and($fixture['props'])->not->toHaveKey('synthetic');

    $this->get("/preview/{$name}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component($fixture['component'])
            ->missing('synthetic'),
        );
})->with(fn () => array_keys(c3PreviewFixtures()));

test('C3 fixtures cover every surface in the scaffold, each with a live-minimal shape', function () {
    $names = array_keys(c3PreviewFixtures());

    foreach (['investor-deals', 'investor-checkout', 'investor-commitment', 'investor-portfolio', 'investor-holding', 'investor-wallet', 'admin-disbursements', 'business-publish', 'business-campaign'] as $surface) {
        expect($names)->toContain("{$surface}-live-minimal");
    }

    expect($names)->toContain(
        'investor-deals-gated', 'investor-deals-restricted', 'investor-deals-disbursing',
        'investor-checkout-reserved', 'investor-checkout-reservation-expired', 'investor-checkout-cap-hit',
        'investor-checkout-units-unavailable', 'investor-checkout-unconfirmed', 'investor-checkout-not-recorded',
        'investor-checkout-identity-required', 'investor-commitment-confirmed', 'investor-commitment-funded-awaiting',
        'investor-commitment-funded-pending', 'investor-commitment-funded-unknown', 'investor-commitment-cancelled',
        'investor-commitment-expired', 'investor-commitment-failed-closing', 'investor-commitment-issued',
        'investor-commitment-visibility-lost', 'investor-portfolio-awaiting-issue', 'investor-holding-issued',
        'investor-wallet-deposit-pending', 'investor-wallet-deposit-unknown', 'investor-wallet-policy-missing',
        'investor-wallet-internal', 'investor-wallet-paged',
        'admin-disbursements-ready', 'admin-disbursements-awaiting-second', 'admin-disbursements-self',
        'admin-disbursements-on-hold', 'admin-disbursements-queued', 'admin-disbursements-dispatched-pending',
        'admin-disbursements-dispatched-unknown', 'admin-disbursements-verified-failure-unreconciled',
        'admin-disbursements-exception', 'admin-disbursements-failed-closing', 'admin-disbursements-succeeded',
        'admin-disbursements-visibility-lost',
        'business-publish-released', 'business-publish-published', 'business-campaign-raising-live',
        'business-campaign-raising-restricted', 'business-campaign-funded-in-flight', 'business-campaign-disbursed',
        'business-campaign-cancelled', 'business-campaign-failed-closing',
    );
});

test('no C3 fixture posts anywhere real', function (string $name) {
    $fixture = c3PreviewFixtures()[$name];

    /*
     * Every command target in the fixture that would reach a registered POST route. It must be
     * empty for every fixture, including a read-only one that carries no command target at all.
     */
    $registered = array_values(array_filter(
        c3PreviewCommandUrls($fixture['props']),
        static function (string $url): bool {
            try {
                Route::getRoutes()->match(Request::create($url, 'POST'));
            } catch (NotFoundHttpException|MethodNotAllowedHttpException) {
                return false;
            }

            return true;
        },
    ));

    expect($registered)->toBe([], "{$name} posts to a registered route: ".implode(', ', $registered));

    if (str_ends_with($name, '-live-minimal')) {
        expect($fixture['props']['allowed_actions'] ?? [])->toBe([]);
    }
})->with(fn () => array_keys(c3PreviewFixtures()));

test('an unknown fixture is not found', function () {
    $this->get('/preview/no-such-fixture')->assertNotFound();
});

test('fixture names cannot walk out of the fixture directory', function () {
    $this->get('/preview/..%2F..%2F.env')->assertNotFound();
    $this->get('/preview/Launcher-Ready')->assertNotFound();
});

test('preview routes are registered for local and testing', function (string $environment) {
    app()->detectEnvironment(fn () => $environment);
    Route::setRoutes(new RouteCollection);

    require base_path('routes/preview.php');

    $routes = Route::getRoutes()->getRoutes();

    expect($routes)->toHaveCount(1)
        ->and($routes[0]->uri())->toBe('preview/{fixture}')
        ->and($routes[0]->getName())->toBeNull();
})->with(['local', 'testing']);

test('preview routes do not exist in production, staging or demo', function (string $environment) {
    app()->detectEnvironment(fn () => $environment);
    Route::setRoutes(new RouteCollection);

    require base_path('routes/preview.php');

    expect(Route::getRoutes()->getRoutes())->toBeEmpty();
})->with(['production', 'staging', 'uat', 'demo']);
