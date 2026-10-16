<?php

declare(strict_types=1);

use App\Application\Identity\ConfigureStaffAccess;
use App\Models\Complaint;
use App\Models\ComplaintMessage;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\BusinessAuthorityFixture;

/*
 * A Business's complaint chat when a message is refused, a staff answer resent with different
 * text, and the Business's bell linking a complaint notice back to its support center.
 */

beforeEach(function (): void {
    $this->travelTo(now('UTC')->startOfSecond());
    $this->withoutVite();
    $authority = BusinessAuthorityFixture::make('organization', 2, requiredSignatories: 1);
    $this->business = BusinessAuthorityFixture::configure($authority)['data']['business']['id'];
    $this->owner = $authority['users'][0];
    $this->staff = User::factory()->withTwoFactor()->create();
    app(ConfigureStaffAccess::class)->handle($this->staff->id, true, 'Complaints assignment.', (string) Str::uuid(), ['compliance']);
    $this->message = fn (string $body, ?string $requestId = null): array => ['request_id' => $requestId ?? (string) Str::uuid(), 'body' => $body];
});

it('returns a refused Business message to the chat as a form error on the message', function (): void {
    $this->actingAs($this->owner)->post(route('business.complaints.store', ['business' => $this->business]), ($this->message)('   '))
        ->assertSessionHasErrors(['body' => 'Tell us what happened.']);
    $this->post(route('business.complaints.reply', ['business' => $this->business, 'complaint' => strtolower((string) Str::ulid())]),
        ($this->message)('Adding the bank reference.'))
        ->assertSessionHasErrors(['body' => 'We could not find that conversation.']);

    expect(Complaint::query()->count())->toBe(0)->and(ComplaintMessage::query()->count())->toBe(0);
});

it('refuses a staff answer resent under the same request with different text, and links the answer notice to the support center', function (): void {
    $this->actingAs($this->owner)->post(route('business.complaints.store', ['business' => $this->business]), ($this->message)('The disbursement is late.'))
        ->assertSessionHasNoErrors();
    $complaint = Complaint::query()->sole();
    $request = (string) Str::uuid();

    $this->actingAs($this->staff)->post(route('staff.complaints.answer', ['complaint' => $complaint->id]),
        [...($this->message)('The payout is on its way.', $request), 'resolves' => false])->assertSessionHasNoErrors();
    $this->post(route('staff.complaints.answer', ['complaint' => $complaint->id]), [...($this->message)('Something else entirely.', $request), 'resolves' => false])
        ->assertSessionHasErrors(['body' => 'That answer was already sent with different text. Try again.']);
    expect(ComplaintMessage::query()->where('author', 'staff')->count())->toBe(1);

    $entityParty = (string) DB::table('business_profiles')->where('id', $this->business)->value('entity_party_id');
    expect($entityParty)->toBe($complaint->party_id);
    $this->actingAs($this->owner)->getJson(route('business.notices.index', $this->business))->assertOk()
        ->assertJsonPath('data.notices.0.kind', 'support.complaint_answered')
        ->assertJsonPath('data.notices.0.facts', ['reference' => $complaint->reference])
        ->assertJsonPath('data.notices.0.link', ['url' => '/business/'.$this->business.'/profile/support', 'method' => 'get']);
});
