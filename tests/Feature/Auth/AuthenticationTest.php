<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Fortify\Features;

test('login screen can be rendered', function () {
    $response = $this->get(route('login'));

    $response->assertOk();
});

test('the login screen is branded for the app named by the entry link or the page left behind', function () {
    $this->get(route('login'))->assertInertia(fn (Assert $page) => $page->component('auth/login')->where('audience', null));
    $this->get(route('login', ['for' => 'business']))->assertInertia(fn (Assert $page) => $page->where('audience', 'business'));
    $this->get(route('login', ['for' => 'admin']))->assertInertia(fn (Assert $page) => $page->where('audience', null));
    $this->get(route('login', ['for' => ['investor']]))->assertInertia(fn (Assert $page) => $page->where('audience', null));

    $this->get('/auditor')->assertRedirect(route('login'));
    $this->get(route('login'))->assertInertia(fn (Assert $page) => $page->where('audience', 'auditor'));

    $this->withSession(['url.intended' => url('/admin')])->get(route('login'))->assertInertia(fn (Assert $page) => $page->where('audience', null));
    $this->withSession(['url.intended' => url('/investor/deals')])->get(route('register', ['for' => 'business']))
        ->assertInertia(fn (Assert $page) => $page->component('auth/register')->where('audience', 'business'));
    $this->get(route('register'))->assertInertia(fn (Assert $page) => $page->where('audience', 'investor'));
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('enter', absolute: false));
});

test('users with two factor enabled are redirected to two factor challenge', function () {
    $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);

    $user = User::factory()->withTwoFactor()->create();

    $response = $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('two-factor.login'));
    $response->assertSessionHas('login.id', $user->id);
    $this->assertGuest();
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('logout'));

    $response->assertRedirect(route('home'));

    $this->assertGuest();
});

test('users are rate limited', function () {
    $user = User::factory()->create();

    RateLimiter::increment(md5('login'.implode('|', [$user->email, '127.0.0.1'])), amount: 5);

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertTooManyRequests();
});
