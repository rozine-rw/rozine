<?php

declare(strict_types=1);

use App\Models\User;
use App\Providers\AppServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Console\Seeds\SeedCommand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password;

/*
 * Command prohibitions are static, so they outlive the per-test application
 * refresh. Without this reset, one production-mode test would leave seeding
 * refused for every test that runs after it.
 */
afterEach(function () {
    DB::prohibitDestructiveCommands(false);
    SeedCommand::prohibit(false);
});

/**
 * Boot the application's defaults as production would see them.
 */
function bootAsProduction(): void
{
    app()->detectEnvironment(fn (): string => 'production');

    (new AppServiceProvider(app()))->boot();
}

test('production is held to a strict password policy', function () {
    app()->detectEnvironment(fn () => 'production');

    expect(Password::default()->toPasswordRulesString())
        ->toBe('minlength: 12; required: lower; required: upper; required: digit; required: special;');
});

test('production refuses to seed, so no known-credential account can reach live data', function () {
    bootAsProduction();

    expect(Artisan::call('db:seed', ['--force' => true]))->toBe(1)
        ->and(User::query()->count())->toBe(0);
});

test('production refuses destructive schema commands', function () {
    bootAsProduction();

    expect(Artisan::call('db:wipe', ['--force' => true]))->toBe(1);
});

test('local and testing environments can still be seeded', function (string $environment) {
    app()->detectEnvironment(fn (): string => $environment);
    (new AppServiceProvider(app()))->boot();

    expect(Artisan::call('db:seed', ['--force' => true]))->toBe(0)
        ->and(User::query()->where('email', 'test@example.com')->exists())->toBeTrue();
})->with(['local', 'testing']);

test('anything other than production leaves password rules to the defaults', function () {
    expect(Password::default()->toPasswordRulesString())->toBe('minlength: 8;');
});

test('the two factor challenge is limited per login attempt', function () {
    $request = Request::create('/two-factor-challenge', 'POST');
    $request->setLaravelSession(app('session.store'));
    $request->session()->put('login.id', 7);

    $limit = RateLimiter::limiter('two-factor')($request);

    expect($limit)->toBeInstanceOf(Limit::class)
        ->and($limit->maxAttempts)->toBe(5);
});

test('login is limited per username and address', function () {
    $request = Request::create('/login', 'POST', ['email' => 'Diane@Example.com']);

    $limit = RateLimiter::limiter('login')($request);

    expect($limit->maxAttempts)->toBe(5)
        ->and($limit->key)->toContain('diane@example.com');
});

test('passkey attempts are limited per credential', function () {
    $request = Request::create('/passkeys', 'POST', ['credential' => ['id' => 'a-credential']]);

    $limit = RateLimiter::limiter('passkeys')($request);

    expect($limit->maxAttempts)->toBe(10)
        ->and($limit->key)->toContain('a-credential');
});

test('passkey attempts without a credential fall back to the session', function () {
    $request = Request::create('/passkeys', 'POST');
    $request->setLaravelSession(app('session.store'));

    $limit = RateLimiter::limiter('passkeys')($request);

    expect($limit->maxAttempts)->toBe(10)
        ->and($limit->key)->toContain($request->session()->getId());
});
