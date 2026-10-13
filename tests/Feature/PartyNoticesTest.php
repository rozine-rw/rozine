<?php

declare(strict_types=1);

use App\Models\PartyNotice;
use App\Models\PartyNoticeDelivery;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Support\InvestorWalletFixture;

/*
 * In-app notices behind the Investor's bell (BRS v2.0 NTF-R01): one notice per recorded event, a
 * receipt for each delivery, read only by the account's own Party, and immutable but for the
 * first read time.
 */

beforeEach(function (): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $this->investor = InvestorWalletFixture::ready();
    $this->credit = function (string $amount = '50000', ?string $event = null): string {
        $intent = InvestorWalletFixture::deposit($this->investor, $amount)['data']['intent_id'];
        InvestorWalletFixture::settle($intent, eventId: $event);

        return $intent;
    };
});

it('notices a credited deposit once, with its in-app receipt, and never a failed one', function (): void {
    $intent = ($this->credit)('50000', 'synthetic-event-once');
    InvestorWalletFixture::settle($intent, eventId: 'synthetic-event-once');
    InvestorWalletFixture::settle(InvestorWalletFixture::deposit($this->investor, '7000')['data']['intent_id'], 'failed');

    $notice = PartyNotice::query()->sole();
    expect([$notice->party_id, $notice->kind, $notice->facts, $notice->event_key])
        ->toBe([$this->investor['party']->id, 'wallet.deposit_received', ['amount' => '50000', 'currency' => 'RWF'], 'wallet-deposit:'.$intent])
        ->and(PartyNoticeDelivery::query()->sole()->only(['party_notice_id', 'channel', 'status', 'user_id']))
        ->toBe(['party_notice_id' => $notice->id, 'channel' => 'in_app', 'status' => 'delivered', 'user_id' => null]);
});

it('lists the account\'s notices newest first and marks one, then all, read', function (): void {
    ($this->credit)('50000');
    $this->travel(1)->seconds();
    ($this->credit)('20000');
    [$newest, $oldest] = PartyNotice::query()->orderByDesc('created_at')->pluck('id')->all();

    $this->actingAs($this->investor['user'])->getJson(route('investor.notices'))->assertOk()
        ->assertJsonPath('data.unread', 2)->assertJsonCount(2, 'data.notices')
        ->assertJsonPath('data.notices.0', ['id' => $newest, 'kind' => 'wallet.deposit_received', 'facts' => ['amount' => '20000', 'currency' => 'RWF'],
            'created_at' => now()->toIso8601String(), 'read' => false, 'link' => ['url' => '/investor/wallet', 'method' => 'get'],
            'read_action' => ['url' => "/investor/notices/{$newest}/read", 'method' => 'post']])
        ->assertJsonPath('data.actions.read_all', ['url' => '/investor/notices/read-all', 'method' => 'post']);

    $this->postJson(route('investor.notices.read', ['notice' => $newest]))->assertOk()->assertJsonPath('data.unread', 1);
    $this->postJson(route('investor.notices.read', ['notice' => $newest]))->assertOk()->assertJsonPath('data.unread', 1);
    $this->getJson(route('investor.notices'))->assertJsonPath('data.notices.0.read', true)->assertJsonPath('data.notices.0.read_action', null)
        ->assertJsonPath('data.notices.1.id', $oldest);

    $this->postJson(route('investor.notices.read_all'))->assertOk()->assertJsonPath('data.unread', 0);
    $this->getJson(route('investor.notices'))->assertJsonPath('data.unread', 0)->assertJsonPath('data.actions.read_all', null);
    $this->get(route('investor.deals'))->assertInertia(fn ($page) => $page->where('unread_notifications', 0)
        ->where('links.notifications', ['url' => '/investor/notices', 'method' => 'get']));
});

it('keeps another Party\'s notices out of reach, and gives an account with no Party none', function (): void {
    ($this->credit)();
    $notice = PartyNotice::query()->sole();
    $other = InvestorWalletFixture::ready();

    $this->actingAs($other['user'])->getJson(route('investor.notices'))->assertOk()->assertJsonPath('data.unread', 0)->assertJsonCount(0, 'data.notices');
    $this->postJson(route('investor.notices.read', ['notice' => $notice->id]))->assertOk()->assertJsonPath('data.unread', 0);
    $this->postJson(route('investor.notices.read_all'))->assertOk();
    expect($notice->refresh()->read_at)->toBeNull();

    $this->actingAs(User::factory()->create())->getJson(route('investor.notices'))->assertOk()->assertJsonPath('data.unread', 0);
    $this->postJson(route('investor.notices.read', ['notice' => 'not-a-ulid']))->assertNotFound();
});

it('serves the notices on API v1 with API links, reading with the read ability and marking with the command ability', function (): void {
    ($this->credit)();
    $notice = PartyNotice::query()->sole();

    Sanctum::actingAs($this->investor['user'], ['investor:read']);
    $this->getJson(route('api.v1.investor.notices'))->assertOk()
        ->assertJsonPath('data.notices.0.link', ['url' => '/api/v1/investor/wallet', 'method' => 'get'])
        ->assertJsonPath('data.notices.0.read_action', ['url' => "/api/v1/investor/notices/{$notice->id}/read", 'method' => 'post']);
    $this->postJson(route('api.v1.investor.notices.read_all'))->assertForbidden();

    Sanctum::actingAs($this->investor['user'], ['investor:read', 'investor:command']);
    $this->postJson(route('api.v1.investor.notices.read', ['notice' => $notice->id]))->assertOk()->assertJsonPath('data.unread', 0);
    Sanctum::actingAs($this->investor['user'], []);
    $this->getJson(route('api.v1.investor.notices'))->assertForbidden();
});

it('keeps notices and receipts immutable but for the first read time', function (): void {
    ($this->credit)();
    $notice = PartyNotice::query()->sole();
    $receipt = PartyNoticeDelivery::query()->sole();
    $refused = fn (Closure $write) => expect(fn () => DB::transaction(fn (): mixed => $write()))->toThrow(QueryException::class);

    $refused(fn () => PartyNotice::query()->whereKey($notice->id)->update(['kind' => 'other']));
    $refused(fn () => PartyNotice::query()->whereKey($notice->id)->delete());
    $refused(fn () => PartyNoticeDelivery::query()->whereKey($receipt->id)->update(['status' => 'failed']));
    $refused(fn () => PartyNoticeDelivery::query()->whereKey($receipt->id)->delete());
    $refused(fn () => PartyNoticeDelivery::query()->insert(['id' => strtolower((string) Str::ulid()), 'party_notice_id' => $notice->id,
        'channel' => 'mail', 'status' => 'sent', 'user_id' => null, 'created_at' => now()]));

    PartyNotice::query()->whereKey($notice->id)->update(['read_at' => now()]);
    $refused(fn () => PartyNotice::query()->whereKey($notice->id)->update(['read_at' => now()->addMinute()]));
    expect($notice->refresh()->read_at?->equalTo(now()))->toBeTrue();
});

it('notices each password change, signed in or by reset, linking to security on the web only', function (): void {
    $user = $this->investor['user'];
    $this->actingAs($user)->from(route('security.edit'))->put(route('user-password.update'),
        ['current_password' => 'password', 'password' => 'new-password', 'password_confirmation' => 'new-password'])->assertSessionHasNoErrors();
    auth()->logout();
    $this->post(route('password.update'), ['token' => Password::createToken($user), 'email' => $user->email,
        'password' => 'newer-password', 'password_confirmation' => 'newer-password'])->assertSessionHasNoErrors();

    expect(PartyNotice::query()->where('kind', 'security.password_changed')->where('party_id', $this->investor['party']->id)->count())->toBe(2)
        ->and(PartyNoticeDelivery::query()->where('channel', 'mail')->exists())->toBeFalse();
    $this->actingAs($user)->getJson(route('investor.notices'))->assertJsonPath('data.notices.0.kind', 'security.password_changed')
        ->assertJsonPath('data.notices.0.facts', [])
        ->assertJsonPath('data.notices.0.link', ['url' => '/investor/profile?section=security', 'method' => 'get']);
    Sanctum::actingAs($user, ['investor:read']);
    $this->getJson(route('api.v1.investor.notices'))->assertJsonPath('data.notices.0.link', null);

    $unattached = User::factory()->create();
    $this->actingAs($unattached)->from(route('security.edit'))->put(route('user-password.update'),
        ['current_password' => 'password', 'password' => 'new-password', 'password_confirmation' => 'new-password'])->assertSessionHasNoErrors();
    expect(PartyNotice::query()->where('kind', 'security.password_changed')->count())->toBe(2);
});
