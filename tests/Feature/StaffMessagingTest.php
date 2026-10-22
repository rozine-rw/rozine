<?php

declare(strict_types=1);

use App\Application\Identity\Contracts\PartyNotices;
use App\Domain\Identity\PartyNoticeKind;
use App\Models\BusinessProfile;
use App\Models\Party;
use App\Models\PartyNotice;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;
use Tests\Support\StaffFixture;

/*
 * Admin Messaging History (BRS v2.0 NTF-R01): every notice receipt, newest first, naming who it
 * reached, the notice's kind and category, the channel and whether it arrived, searched on the
 * server and paged by an older-receipts cursor.
 */

/** @param list<string> $roles */
function messagingStaff(array $roles): User
{
    $user = User::factory()->withTwoFactor()->create();
    StaffFixture::provision($user, $roles, 'Messaging assignment.');

    return $user;
}

beforeEach(function (): void {
    $this->freezeSecond();
    Notification::fake();
    $this->person = User::factory()->create(['name' => 'Aline Uwase', 'party_id' => Party::factory()]);
    $this->business = BusinessProfile::factory()->create(['profile' => ['name' => 'Kigali Bakery', 'company_code' => null, 'industry' => 'retail',
        'district' => 'Gasabo', 'established_year' => 2020]]);
    $notices = app(PartyNotices::class);
    DB::transaction(fn () => $notices->record((string) $this->person->party_id, PartyNoticeKind::VerificationApproved, [], 'messaging-verified'));
    $this->travel(1)->seconds();
    DB::transaction(fn () => $notices->record($this->business->entity_party_id, PartyNoticeKind::BusinessDepositReceived,
        ['currency' => 'RWF', 'amount' => '50000'], 'messaging-business-deposit'));
    $this->travel(1)->seconds();
    $this->failed = Str::lower((string) Str::ulid());
    DB::table('party_notice_deliveries')->insert(['id' => $this->failed, 'channel' => 'mail', 'user_id' => $this->person->id, 'status' => 'failed',
        'detail' => 'RuntimeException', 'created_at' => now('UTC'),
        'party_notice_id' => PartyNotice::query()->where('event_key', 'messaging-verified')->value('id')]);
    $this->page = function (User $staff, array $query = []): array {
        $props = [];
        $this->actingAs($staff)->get(route('staff.messaging.index', $query))->assertOk()
            ->assertInertia(function (Assert $page) use (&$props): void {
                $page->component('admin/messaging');
                $props = $page->toArray()['props'];
            });

        return $props;
    };
});

it('lists every receipt newest first with its recipient, kind, category, channel and outcome', function (): void {
    $props = ($this->page)(messagingStaff(['compliance']));

    expect($props['contract_version'])->toBe('staff-messaging-v1')
        ->and($props['nav']['messaging'])->toBe(['url' => '/admin/messaging/history', 'method' => 'get'])
        ->and($props['older'])->toBeNull()->and($props['newest'])->toBeNull()
        ->and(array_map(fn (array $row): array => [$row['recipient'], $row['recipient_kind'], $row['kind'], $row['category'], $row['source'],
            $row['channel'], $row['status']], $props['history']))->toBe([
                ['Aline Uwase', 'person', 'verification.approved', 'onboarding', 'automation', 'mail', 'failed'],
                ['Kigali Bakery', 'business', 'business.deposit_received', 'wallet', 'automation', 'in_app', 'delivered'],
                ['Aline Uwase', 'person', 'verification.approved', 'onboarding', 'automation', 'mail', 'sent'],
                ['Aline Uwase', 'person', 'verification.approved', 'onboarding', 'automation', 'in_app', 'delivered'],
            ])
        ->and($props['history'][0])->toMatchArray(['id' => $this->failed, 'at' => now('UTC')->toIso8601String()]);
});

it('lists every automation with its audience and channels, and every email template', function (): void {
    $props = ($this->page)(messagingStaff(['compliance']));
    $automations = array_column($props['automations'], null, 'kind');

    expect($props['automations'])->toHaveCount(count(PartyNoticeKind::cases()))
        ->and($automations['verification.approved'])->toBe(['kind' => 'verification.approved', 'category' => 'onboarding',
            'audience' => 'investor', 'channels' => ['in_app', 'mail']])
        ->and($automations['business.deposit_received'])->toMatchArray(['audience' => 'business', 'channels' => ['in_app']])
        ->and($automations['auditor.job_offered'])->toMatchArray(['audience' => 'auditor', 'channels' => ['in_app', 'mail']])
        ->and($automations['security.password_changed']['audience'])->toBe('everyone')
        ->and($automations['support.complaint_resolved']['audience'])->toBe('everyone')
        ->and(array_column($props['templates'], 'audience', 'key'))->toBe([
            'account.new_device_sign_in' => 'everyone', 'account.one_time_code' => 'everyone', 'account.reset_password' => 'everyone',
            'account.verify_email' => 'everyone', 'auditor.accreditation_decided' => 'auditor', 'auditor.job_offered' => 'auditor',
            'business.application_decided' => 'business', 'business.disbursement_sent' => 'business', 'business.raise_expired' => 'business',
            'business.repayment_reminder' => 'business', 'business.reporting_window_open' => 'business',
            'investor.investment_confirmed' => 'investor', 'investor.payment_delayed' => 'investor', 'investor.payout_received' => 'investor',
            'investor.raise_expired' => 'investor', 'investor.report_published' => 'investor', 'investor.verification_decided' => 'investor',
            'investor.wallet_transfer_completed' => 'investor',
        ]);
});

it('searches recipients, kinds and categories, and pages to older receipts and back', function (): void {
    $staff = messagingStaff(['superadmin']);

    expect(array_column(($this->page)($staff, ['q' => 'bakery'])['history'], 'recipient'))->toBe(['Kigali Bakery'])
        ->and(array_column(($this->page)($staff, ['q' => 'ALINE'])['history'], 'status'))->toBe(['failed', 'sent', 'delivered'])
        ->and(array_column(($this->page)($staff, ['q' => 'wallet'])['history'], 'kind'))->toBe(['business.deposit_received'])
        ->and(array_column(($this->page)($staff, ['q' => 'verification'])['history'], 'kind'))->toHaveCount(3)
        ->and(($this->page)($staff, ['q' => 'nobody'])['history'])->toBe([]);

    $history = app(PartyNotices::class)->deliveries(null, null, 10);
    $person = User::factory()->create(['name' => 'Eric Mugisha', 'party_id' => Party::factory()]);
    DB::transaction(fn () => app(PartyNotices::class)->record((string) $person->party_id, PartyNoticeKind::PasswordChanged, [], 'messaging-password'));
    expect(app(PartyNotices::class)->deliveries(null, null, 10)[0])->toMatchArray(['recipient' => 'Eric Mugisha', 'kind' => 'security.password_changed'])
        ->and(app(PartyNotices::class)->deliveries(null, $history[1]['id'], 10))->toBe(array_slice($history, 2));
});

it('opens older receipts after fifty, keeping the search', function (): void {
    $notices = app(PartyNotices::class);
    foreach (range(1, 48) as $index) {
        DB::transaction(fn () => $notices->record($this->business->entity_party_id, PartyNoticeKind::BusinessDepositReceived,
            ['currency' => 'RWF', 'amount' => (string) $index], 'messaging-deposit-'.$index));
    }
    $staff = messagingStaff(['compliance']);
    $first = ($this->page)($staff, ['q' => 'Kigali']);
    $cursor = $first['history'][48]['id'];

    expect($first['history'])->toHaveCount(49)->and($first['older'])->toBeNull();

    $page = ($this->page)($staff);
    $last = $page['history'][49]['id'];
    expect($page['history'])->toHaveCount(50)
        ->and($page['older'])->toBe(['url' => '/admin/messaging/history?before='.$last, 'method' => 'get']);
    $older = ($this->page)($staff, ['before' => $last]);
    expect(array_column($older['history'], 'kind'))->toBe(['verification.approved', 'verification.approved'])
        ->and($older['older'])->toBeNull()->and($older['newest'])->toBe(['url' => '/admin/messaging/history', 'method' => 'get'])
        ->and(($this->page)($staff, ['q' => 'Kigali', 'before' => $cursor])['newest'])->toBe(['url' => '/admin/messaging/history?q=Kigali', 'method' => 'get']);
});

it('is for Compliance and superadmin only, and over API v1 with its own ability', function (): void {
    foreach (['analyst', 'approver', 'treasury'] as $role) {
        $this->actingAs(messagingStaff([$role]))->get(route('staff.messaging.index'))->assertForbidden();
    }
    $this->actingAs(User::factory()->create(['party_id' => Party::factory()]))->get(route('staff.messaging.index'))->assertForbidden();
    $this->actingAs(messagingStaff(['compliance']))->get(route('staff.messaging.index', ['before' => 'not-a-ulid']))->assertSessionHasErrors('before');

    $compliance = messagingStaff(['compliance']);
    Sanctum::actingAs($compliance, ['staff:messaging:read']);
    $this->getJson(route('api.v1.staff.messaging.index', ['q' => 'bakery']))->assertOk()->assertJsonPath('data.contract_version', 'staff-messaging-v1')
        ->assertJsonPath('data.nav.messaging.url', '/api/v1/staff/messaging/history')->assertJsonCount(1, 'data.history');
    Sanctum::actingAs($compliance, ['staff:dashboard:read']);
    $this->getJson(route('api.v1.staff.messaging.index'))->assertForbidden();
});

it('leaves the Messaging entry on its pending page for staff who may not read the history', function (): void {
    $props = [];
    $this->actingAs(messagingStaff(['treasury']))->get(route('staff.reconciliation.index'))->assertOk()
        ->assertInertia(function (Assert $page) use (&$props): void {
            $props = $page->toArray()['props'];
        });

    expect($props['nav']['messaging'])->toBe(['url' => '/admin/messaging', 'method' => 'get']);
});
