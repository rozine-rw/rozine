<?php

declare(strict_types=1);

use App\Application\Identity\EmailVerificationCode;
use App\Models\User;
use App\Notifications\Account\OneTimeCode;
use App\Notifications\Account\OneTimeCodePurpose;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;

/**
 * Sign-up confirms the email address with the six-digit onboarding code: registration and every
 * "send a new code" issue one, and the verification page accepts it once, within ten minutes,
 * with at most five tries in fifteen minutes.
 */
function emailCodeFor(User $user): string
{
    return Notification::sent($user, OneTimeCode::class)->last()->code;
}

test('registering sends the onboarding code email, not a link', function () {
    Notification::fake();

    $this->post(route('register.store'), [
        'name' => 'Aline Uwase',
        'email' => 'aline@example.test',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));

    $user = User::query()->where('email', 'aline@example.test')->sole();

    Notification::assertSentToTimes($user, OneTimeCode::class, 1);
    Notification::assertSentTo($user, OneTimeCode::class, fn (OneTimeCode $notification): bool => preg_match('/^\d{6}$/', $notification->code) === 1
        && $notification->purpose === OneTimeCodePurpose::SignUp
        && $notification->expiresInMinutes === EmailVerificationCode::EXPIRES_IN_MINUTES);
});

test('the emailed code verifies the address and continues like the verification link', function () {
    Notification::fake();
    Event::fake([Verified::class]);
    $user = User::factory()->unverified()->create();
    $user->sendEmailVerificationNotification();

    $this->actingAs($user)
        ->post(route('verification.code'), ['code' => emailCodeFor($user)])
        ->assertRedirect(route('dashboard', absolute: false).'?verified=1');

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    Event::assertDispatched(Verified::class, fn (Verified $event): bool => $event->user->getEmailForVerification() === $user->email);
});

test('a wrong code is refused on the code field and leaves the address unverified', function () {
    Notification::fake();
    $user = User::factory()->unverified()->create();
    $user->sendEmailVerificationNotification();
    $wrong = emailCodeFor($user) === '000000' ? '111111' : '000000';

    $this->actingAs($user)
        ->from(route('verification.notice'))
        ->post(route('verification.code'), ['code' => $wrong])
        ->assertRedirect(route('verification.notice'))
        ->assertSessionHasErrors(['code' => 'That code is not right. Check the latest email from Rozine and try again.']);

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('a code is refused once it is ten minutes old', function () {
    Notification::fake();
    $user = User::factory()->unverified()->create();
    $user->sendEmailVerificationNotification();

    $this->travel(EmailVerificationCode::EXPIRES_IN_MINUTES)->minutes();
    $this->travel(1)->second();

    $this->actingAs($user)
        ->post(route('verification.code'), ['code' => emailCodeFor($user)])
        ->assertSessionHasErrors(['code' => 'That code has expired. Send a new code and enter it within 10 minutes.']);

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('sending a new code replaces the earlier one', function () {
    Notification::fake();
    $user = User::factory()->unverified()->create();
    $user->sendEmailVerificationNotification();
    $first = emailCodeFor($user);

    do {
        $this->actingAs($user)->post(route('verification.send'))->assertRedirect();
        $second = emailCodeFor($user);
    } while ($second === $first);

    $this->actingAs($user)
        ->post(route('verification.code'), ['code' => $first])
        ->assertSessionHasErrors('code');

    $this->actingAs($user)
        ->post(route('verification.code'), ['code' => $second])
        ->assertSessionHasNoErrors();

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

test('five tries in fifteen minutes is the limit, even for the right code', function () {
    Notification::fake();
    $user = User::factory()->unverified()->create();
    $user->sendEmailVerificationNotification();
    $code = emailCodeFor($user);
    $wrong = $code === '000000' ? '111111' : '000000';

    foreach (range(1, 5) as $attempt) {
        $this->actingAs($user)->post(route('verification.code'), ['code' => $wrong])->assertSessionHasErrors('code');
        $this->travel(15)->seconds();
    }

    $this->actingAs($user)
        ->post(route('verification.code'), ['code' => $code])
        ->assertSessionHasErrors(['code' => 'Too many attempts. Wait 15 minutes, then try the latest code again.']);

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();

    $this->travel(15)->minutes();
    $user->sendEmailVerificationNotification();

    $this->actingAs($user)
        ->post(route('verification.code'), ['code' => emailCodeFor($user)])
        ->assertSessionHasNoErrors();

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

test('a code works once', function () {
    $codes = app(EmailVerificationCode::class);
    $code = $codes->issue(41);

    expect($codes->confirm(41, $code))->toBeNull()
        ->and($codes->confirm(41, $code))->toBe('EMAIL_CODE_EXPIRED');
});

test('a code belongs to the account it was issued for', function () {
    $codes = app(EmailVerificationCode::class);
    $code = $codes->issue(41);

    do {
        $other = $codes->issue(42);
    } while ($other === $code);

    expect($codes->confirm(42, $code))->toBe('EMAIL_CODE_INVALID')
        ->and($codes->confirm(41, $code))->toBeNull();
});

test('only a keyed digest of the code is kept', function () {
    $code = app(EmailVerificationCode::class)->issue(41);
    $stored = Cache::get('identity:email-verification-code:41');

    expect($stored)->toBeString()
        ->and($stored)->not->toContain($code)
        ->and(strlen($stored))->toBe(64);
});

test('a code that is not six digits is refused before it is checked', function (string $code) {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->post(route('verification.code'), ['code' => $code])
        ->assertSessionHasErrors(['code' => 'Enter the 6-digit code from your email.']);
})->with(['empty' => '', 'short' => '12345', 'letters' => '12a456', 'long' => '1234567']);

test('an already verified account is sent on without checking a code', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('verification.code'), ['code' => '123456'])
        ->assertRedirect(route('dashboard', absolute: false).'?verified=1')
        ->assertSessionHasNoErrors();
});

test('a guest cannot submit a code', function () {
    $this->post(route('verification.code'), ['code' => '123456'])->assertRedirect(route('login'));
});
