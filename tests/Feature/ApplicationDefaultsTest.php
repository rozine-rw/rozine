<?php

declare(strict_types=1);

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password;

test('production is held to a strict password policy', function () {
    app()->detectEnvironment(fn () => 'production');

    expect(Password::default()->toPasswordRulesString())
        ->toBe('minlength: 12; required: lower; required: upper; required: digit; required: special;');
});

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
