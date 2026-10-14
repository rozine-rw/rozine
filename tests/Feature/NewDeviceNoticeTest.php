<?php

declare(strict_types=1);

use App\Application\Identity\RecognizeSignInDevice;
use App\Domain\Identity\DeviceLabel;
use App\Models\AccountDevice;
use App\Models\PartyNotice;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\Support\InvestorWalletFixture;

/*
 * The new-device security notice (BRS v2.0 NTF-R01): each web sign-in is matched to the device
 * token its browser keeps, and the account's Party is told when a device it has not seen signs in.
 */

const FIREFOX_ON_ANDROID = 'Mozilla/5.0 (Android 14; Mobile; rv:131.0) Gecko/131.0 Firefox/131.0';

beforeEach(function (): void {
    InvestorWalletFixture::policy(maximum: null);
    $this->investor = InvestorWalletFixture::ready();
    $this->signIn = function (?string $token = null, string $agent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0 Safari/537.36'): TestResponse {
        auth()->logout();
        Cookie::flushQueuedCookies();
        $request = $this->withHeader('User-Agent', $agent);
        if ($token !== null) {
            $request = $request->withCookie(RecognizeSignInDevice::COOKIE, $token);
        }

        return $request->post(route('login.store'), ['email' => $this->investor['user']->email, 'password' => 'password'])->assertRedirect();
    };
});

it('records the first device silently and gives its browser a lasting token', function (): void {
    $response = ($this->signIn)();

    $cookie = collect($response->headers->getCookies())->first(fn ($cookie): bool => $cookie->getName() === RecognizeSignInDevice::COOKIE);
    expect($cookie?->isHttpOnly())->toBeTrue()
        ->and($cookie?->getExpiresTime())->toBeGreaterThanOrEqual(now()->addDays(400)->getTimestamp())
        ->and(AccountDevice::query()->sole()->only(['user_id', 'label']))->toBe(['user_id' => $this->investor['user']->id, 'label' => 'Chrome on Windows'])
        ->and(PartyNotice::query()->where('kind', 'security.new_device')->exists())->toBeFalse();
});

it('tells the Party once when a new device signs in, and never for a device it knows', function (): void {
    $first = ($this->signIn)()->getCookie(RecognizeSignInDevice::COOKIE)?->getValue();
    ($this->signIn)($first)->assertCookieMissing(RecognizeSignInDevice::COOKIE);
    $second = ($this->signIn)('a-token-no-device-holds', FIREFOX_ON_ANDROID)->getCookie(RecognizeSignInDevice::COOKIE)?->getValue();
    ($this->signIn)($second, FIREFOX_ON_ANDROID);

    $notice = PartyNotice::query()->where('kind', 'security.new_device')->sole();
    $device = AccountDevice::query()->where('label', 'Firefox on Android')->sole();
    expect(AccountDevice::query()->count())->toBe(2)
        ->and([$notice->party_id, $notice->facts, $notice->event_key])->toBe([$this->investor['party']->id, ['device' => 'Firefox on Android'], 'new-device:'.$device->id])
        ->and($device->token_hash)->toBe(hash('sha256', (string) $second));

    $this->actingAs($this->investor['user'])->getJson(route('investor.notices'))->assertJsonPath('data.notices.0.kind', 'security.new_device')
        ->assertJsonPath('data.notices.0.link', ['url' => '/investor/profile?section=security', 'method' => 'get']);
});

it('does not recognise another account\'s device token', function (): void {
    $other = User::factory()->create();
    Cookie::flushQueuedCookies();
    $foreign = $this->post(route('login.store'), ['email' => $other->email, 'password' => 'password'])->getCookie(RecognizeSignInDevice::COOKIE)?->getValue();
    ($this->signIn)();
    $borrowed = ($this->signIn)($foreign);

    expect(AccountDevice::query()->where('user_id', $this->investor['user']->id)->count())->toBe(2)
        ->and($borrowed->getCookie(RecognizeSignInDevice::COOKIE)?->getValue())->not->toBe($foreign)
        ->and(PartyNotice::query()->where('kind', 'security.new_device')->count())->toBe(1);
});

it('keeps device records unchanged once recorded, and lets them go with the account', function (): void {
    ($this->signIn)();
    $device = AccountDevice::query()->sole();

    expect(fn () => DB::transaction(fn (): int => AccountDevice::query()->whereKey($device->id)->update(['label' => 'Changed'])))->toThrow(QueryException::class);
    $other = User::factory()->create();
    $this->post(route('login.store'), ['email' => $other->email, 'password' => 'password']);
    $other->delete();
    expect(AccountDevice::query()->pluck('user_id')->all())->toBe([$this->investor['user']->id]);
});

it('names devices plainly from their browser', function (?string $agent, string $label): void {
    expect(DeviceLabel::fromUserAgent($agent))->toBe($label);
})->with([
    [FIREFOX_ON_ANDROID, 'Firefox on Android'],
    ['Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0 Mobile/15E148 Safari/604.1', 'Safari on iPhone'],
    ['Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0 Safari/537.36 Edg/130.0', 'Edge on macOS'],
    ['curl/8.5.0', 'An unrecognised browser'],
    ['Mozilla/5.0 (X11; Linux x86_64)', 'Linux'],
    [null, 'An unrecognised browser'],
]);
