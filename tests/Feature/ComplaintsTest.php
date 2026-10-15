<?php

declare(strict_types=1);

use App\Application\Identity\ConfigureStaffAccess;
use App\Models\Complaint;
use App\Models\ComplaintMessage;
use App\Models\PartyNotice;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\BusinessAuthorityFixture;
use Tests\Support\InvestorWalletFixture;

/*
 * Complaints (BRS v2.0 CMP-R03): every complaint gets a reference number, is acknowledged within 4
 * hours and resolved within 48, and one unanswered at 4 hours is flagged on the Admin console.
 */

/** @param list<string> $roles */
function complaintStaff(array $roles): User
{
    $user = User::factory()->withTwoFactor()->create();
    app(ConfigureStaffAccess::class)->handle($user->id, true, 'Complaints assignment.', (string) Str::uuid(), $roles);

    return $user;
}

/** @return array<string, string> */
function complaintMessage(string $body, ?string $requestId = null): array
{
    return ['request_id' => $requestId ?? (string) Str::uuid(), 'body' => $body];
}

beforeEach(function (): void {
    $this->travelTo(now('UTC')->startOfSecond());
    $this->support = function (User $investor): array {
        $props = [];
        $this->actingAs($investor)->get(route('investor.profile', ['section' => 'help']))->assertOk()
            ->assertInertia(function (Assert $page) use (&$props): void {
                $page->component('investor/profile');
                $props = $page->toArray()['props']['support'];
            });

        return $props;
    };
    $this->exceptions = function (User $staff): array {
        $items = [];
        $this->actingAs($staff)->get(route('staff.exceptions.index', ['kind' => 'complaint']))->assertOk()
            ->assertInertia(function (Assert $page) use (&$items): void {
                $items = $page->toArray()['props']['exceptions'];
            });

        return $items;
    };
});

it('opens a complaint from the Help center chat with its reference and clocks, idempotently, and lets the Investor add to it', function (): void {
    $investor = InvestorWalletFixture::investor()['user'];
    expect(($this->support)($investor))->toBe(['open' => ['url' => '/investor/complaints', 'method' => 'post'], 'complaints' => []]);

    $first = complaintMessage('My withdrawal has not arrived after two days.');
    $this->actingAs($investor)->post(route('investor.complaints.store'), $first)
        ->assertRedirect(route('investor.profile', ['section' => 'help']))->assertSessionHasNoErrors();
    $this->post(route('investor.complaints.store'), $first)->assertSessionHasNoErrors();

    $complaint = Complaint::query()->sole();
    expect($complaint->reference)->toBe('CMP-'.Str::upper(Str::substr($complaint->id, -8)))
        ->and($complaint->role)->toBe('investor')->and($complaint->channel)->toBe('in_app')
        ->and($complaint->acknowledge_by->equalTo(now()->addHours(4)))->toBeTrue()
        ->and($complaint->resolve_by->equalTo(now()->addHours(48)))->toBeTrue()
        ->and(DB::table('complaint_messages')->value('body'))->not->toContain('withdrawal');

    $this->post(route('investor.complaints.reply', ['complaint' => $complaint->id]), complaintMessage('  The reference is WDR-123.  '))
        ->assertSessionHasNoErrors();

    $support = ($this->support)($investor);
    expect($support['complaints'])->toHaveCount(1)
        ->and($support['complaints'][0])->toMatchArray(['id' => $complaint->id, 'reference' => $complaint->reference, 'status' => 'awaiting',
            'opened_at' => now()->toIso8601String(), 'acknowledge_by' => now()->addHours(4)->toIso8601String(),
            'resolve_by' => now()->addHours(48)->toIso8601String(),
            'reply' => ['url' => '/investor/complaints/'.$complaint->id.'/messages', 'method' => 'post']])
        ->and(array_map(fn (array $message): array => [$message['author'], $message['body'], $message['resolves']], $support['complaints'][0]['messages']))
        ->toBe([['customer', 'My withdrawal has not arrived after two days.', false], ['customer', 'The reference is WDR-123.', false]]);
});

it('refuses an empty or overlong message and a reply to someone else\'s complaint', function (): void {
    $investor = InvestorWalletFixture::investor()['user'];
    $this->actingAs($investor)->post(route('investor.complaints.store'), complaintMessage('   '))
        ->assertSessionHasErrors(['body' => 'Tell us what happened.']);
    $this->post(route('investor.complaints.store'), complaintMessage(str_repeat('a', 2001)))
        ->assertSessionHasErrors(['body' => 'Keep the message to 2,000 characters.']);
    expect(Complaint::query()->count())->toBe(0);

    $this->post(route('investor.complaints.store'), complaintMessage('A fee looks wrong.'))->assertSessionHasNoErrors();
    $other = InvestorWalletFixture::investor()['user'];
    $this->actingAs($other)->post(route('investor.complaints.reply', ['complaint' => Complaint::query()->sole()->id]), complaintMessage('Me too.'))
        ->assertSessionHasErrors(['body' => 'We could not find that conversation.']);
    expect(ComplaintMessage::query()->count())->toBe(1);
});

it('lets Compliance answer and then resolve a complaint, with a notice each time, after which nothing more can be added', function (): void {
    $investor = InvestorWalletFixture::investor();
    $this->actingAs($investor['user'])->post(route('investor.complaints.store'), complaintMessage('I was charged twice.'));
    $complaint = Complaint::query()->sole();
    $staff = complaintStaff(['compliance']);

    $props = [];
    $this->actingAs($staff)->get(route('staff.complaints.index', ['complaint' => $complaint->id]))->assertOk()
        ->assertInertia(function (Assert $page) use (&$props): void {
            $page->component('admin/complaints');
            $props = $page->toArray()['props'];
        });
    expect($props)->toMatchArray(['contract_version' => 'staff-complaints-v1', 'allowed_actions' => [], 'search' => ''])
        ->and($props['nav']['complaints'])->toBe(['url' => '/admin/complaints', 'method' => 'get'])
        ->and($props['stats'])->toBe([['key' => 'unresolved', 'value' => ['kind' => 'count', 'value' => 1]],
            ['key' => 'awaiting', 'value' => ['kind' => 'count', 'value' => 1]], ['key' => 'late', 'value' => ['kind' => 'count', 'value' => 0]]])
        ->and($props['complaints'][0])->toMatchArray(['reference' => $complaint->reference, 'customer' => $investor['user']->name, 'role' => 'investor',
            'status' => 'awaiting', 'late' => null, 'messages' => 1])
        ->and($props['complaint'])->toMatchArray(['id' => $complaint->id, 'answer' => ['url' => '/admin/complaints/'.$complaint->id.'/answers', 'method' => 'post'],
            'close' => ['url' => '/admin/complaints', 'method' => 'get']])
        ->and($props['complaint']['messages'][0]['body'])->toBe('I was charged twice.');

    $this->post(route('staff.complaints.answer', ['complaint' => $complaint->id]), [...complaintMessage('We are checking the duplicate charge.'), 'resolves' => false])
        ->assertRedirect(route('staff.complaints.index', ['complaint' => $complaint->id]))->assertSessionHasNoErrors();
    expect(($this->support)($investor['user'])['complaints'][0]['status'])->toBe('answered');

    $this->actingAs($staff)->post(route('staff.complaints.answer', ['complaint' => $complaint->id]),
        [...complaintMessage('The duplicate charge was refunded to your wallet.'), 'resolves' => true])->assertSessionHasNoErrors();
    $support = ($this->support)($investor['user']);
    expect($support['complaints'][0])->toMatchArray(['status' => 'resolved', 'reply' => null])
        ->and(array_column($support['complaints'][0]['messages'], 'resolves'))->toBe([false, false, true])
        ->and(PartyNotice::query()->where('party_id', $investor['party']->id)->orderBy('created_at')->orderBy('id')->get(['kind', 'facts'])->toArray())->toBe([
            ['kind' => 'support.complaint_answered', 'facts' => ['reference' => $complaint->reference]],
            ['kind' => 'support.complaint_resolved', 'facts' => ['reference' => $complaint->reference]],
        ]);

    $this->post(route('investor.complaints.reply', ['complaint' => $complaint->id]), complaintMessage('Thanks.'))
        ->assertSessionHasErrors(['body' => 'This complaint is resolved. Start a new message to raise something else.']);
    $this->actingAs($staff)->post(route('staff.complaints.answer', ['complaint' => $complaint->id]), [...complaintMessage('Again.'), 'resolves' => true])
        ->assertSessionHasErrors(['body' => 'This complaint is already resolved.']);
    expect(ComplaintMessage::query()->count())->toBe(3);
});

it('flags a complaint on Exceptions once 4 hours pass unacknowledged, and again once 48 pass unresolved', function (): void {
    $this->actingAs(InvestorWalletFixture::investor()['user'])->post(route('investor.complaints.store'), complaintMessage('My deposit is missing.'));
    $complaint = Complaint::query()->sole();
    $compliance = complaintStaff(['compliance']);
    $treasury = complaintStaff(['treasury']);
    expect(($this->exceptions)($compliance))->toBe([]);

    $this->travel(4)->hours();
    $item = ($this->exceptions)($compliance)[0];
    expect($item)->toMatchArray(['id' => $complaint->id, 'kind' => 'complaint', 'reference' => $complaint->reference,
        'description' => 'Complaint not acknowledged within 4 hours', 'link' => ['url' => '/admin/complaints?complaint='.$complaint->id, 'method' => 'get']])
        ->and($item['subject']['kind'])->toBe('investor')
        ->and(($this->exceptions)($treasury)[0]['link'])->toBeNull();

    $this->actingAs($compliance)->post(route('staff.complaints.answer', ['complaint' => $complaint->id]), [...complaintMessage('We are on it.'), 'resolves' => false]);
    expect(($this->exceptions)($compliance))->toBe([]);

    $this->travel(44)->hours();
    expect(($this->exceptions)($compliance)[0]['description'])->toBe('Complaint acknowledged but not resolved within 48 hours');

    $this->actingAs($compliance)->post(route('staff.complaints.answer', ['complaint' => $complaint->id]), [...complaintMessage('Found and credited.'), 'resolves' => true]);
    expect(($this->exceptions)($compliance))->toBe([]);
});

it('keeps Complaints to staff who may answer them, and complaints immutable', function (): void {
    $this->actingAs(InvestorWalletFixture::investor()['user'])->post(route('investor.complaints.store'), complaintMessage('Help.'));
    $complaint = Complaint::query()->sole();
    $treasury = complaintStaff(['treasury']);

    $this->actingAs($treasury)->get(route('staff.complaints.index'))->assertForbidden();
    $this->post(route('staff.complaints.answer', ['complaint' => $complaint->id]), [...complaintMessage('Hi.'), 'resolves' => false])->assertForbidden();
    expect(ComplaintMessage::query()->count())->toBe(1);

    expect(fn () => DB::table('complaint_messages')->update(['author' => 'staff']))->toThrow(QueryException::class)
        ->and(fn () => DB::table('complaints')->where('id', $complaint->id)->delete())->toThrow(QueryException::class);
});

it('lets everyone who may view a Business open and follow its complaints from the support center', function (): void {
    $this->withoutVite();
    $authority = BusinessAuthorityFixture::make('organization', 2, requiredSignatories: 1);
    $viewer = $authority['people'][1]->id;
    $authority['terms']['people'] = array_map(fn (array $person): array => $person['party_id'] === $viewer
        ? [...$person, 'permissions' => ['business.view']] : $person, $authority['terms']['people']);
    $business = BusinessAuthorityFixture::configure($authority)['data']['business']['id'];
    $entityParty = (string) DB::table('business_profiles')->where('id', $business)->value('entity_party_id');
    $name = (string) DB::table('business_profiles')->where('id', $business)->selectRaw("profile->>'name' AS name")->value('name');

    $this->actingAs($authority['users'][1])->post(route('business.complaints.store', ['business' => $business]), complaintMessage('The disbursement is late.'))
        ->assertRedirect(route('business.profile', ['business' => $business, 'section' => 'support']))->assertSessionHasNoErrors();
    $complaint = Complaint::query()->sole();
    expect($complaint->party_id)->toBe($entityParty)->and($complaint->role)->toBe('business');

    $this->actingAs($authority['users'][0])->post(route('business.complaints.reply', ['business' => $business, 'complaint' => $complaint->id]),
        complaintMessage('Adding the bank reference.'))->assertSessionHasNoErrors();
    $this->get(route('business.profile', ['business' => $business, 'section' => 'support']))->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page->component('business/profile')
            ->where('support.open', ['url' => '/business/'.$business.'/complaints', 'method' => 'post'])
            ->where('support.complaints.0.reference', $complaint->reference)
            ->where('support.complaints.0.reply', ['url' => '/business/'.$business.'/complaints/'.$complaint->id.'/messages', 'method' => 'post'])
            ->has('support.complaints.0.messages', 2));

    $staff = complaintStaff(['compliance']);
    $props = [];
    $this->actingAs($staff)->get(route('staff.complaints.index'))->assertOk()
        ->assertInertia(function (Assert $page) use (&$props): void {
            $props = $page->toArray()['props'];
        });
    expect($props['complaints'][0])->toMatchArray(['role' => 'business', 'customer' => $name.' ('.$authority['users'][1]->name.')']);

    $this->post(route('staff.complaints.answer', ['complaint' => $complaint->id]), [...complaintMessage('The payout is on its way.'), 'resolves' => false]);
    expect(PartyNotice::query()->where('party_id', $entityParty)->pluck('kind')->all())->toBe(['support.complaint_answered']);

    $outsider = InvestorWalletFixture::investor()['user'];
    $this->actingAs($outsider)->post(route('business.complaints.store', ['business' => $business]), complaintMessage('Hello.'))->assertStatus(403);
    expect(Complaint::query()->count())->toBe(1);
});
