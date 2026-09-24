<?php

declare(strict_types=1);

use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;

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
