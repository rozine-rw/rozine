<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('new users can verify their email using the registration notification', function (): void {
    $this->skipUnlessFortifyHas(Features::emailVerification());
    Notification::fake();

    $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasNoErrors()->assertRedirect(route('dashboard', absolute: false));

    $user = User::query()->where('email', 'test@example.com')->sole();

    Notification::assertSentToTimes($user, VerifyEmail::class, 1);

    $this->get(route('dashboard'))->assertRedirect(route('verification.notice'));
    $this->getJson(route('api.v1.identity.show'))
        ->assertOk()
        ->assertJsonPath('data.code', 'EMAIL_VERIFICATION_REQUIRED')
        ->assertJsonPath('data.available_roles', []);

    $notification = Notification::sent($user, VerifyEmail::class)->sole();

    $this->get($notification->toMail($user)->actionUrl)
        ->assertRedirect(route('dashboard', absolute: false));

    expect($user->refresh()->hasVerifiedEmail())->toBeTrue();

    $this->get(route('dashboard'))->assertOk();
    $this->getJson(route('api.v1.identity.show'))
        ->assertOk()
        ->assertJsonPath('data.code', 'IDENTITY_VERIFICATION_REQUIRED')
        ->assertJsonPath('data.available_roles', []);
});
