<?php

declare(strict_types=1);

use App\Application\Identity\SaveInvestorVerification;
use App\Application\Identity\SubmitInvestorVerification;
use App\Application\Identity\UploadInvestorVerificationDocument;
use App\Models\IdentityAuditEvent;
use App\Models\InvestorVerification;
use App\Models\Party;
use App\Models\RoleMembership;
use App\Models\User;
use App\Notifications\Investor\VerificationDecided;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\StaffFixture;

/*
 * The launcher's Business and Auditor cards send an unverified person through the same identity check
 * an Investor makes. Before this, every approved check also activated an Investor membership, so a
 * person who came for Auditor access ended up an Investor and could no longer ask for Auditor access:
 * an Auditor keeps no other role (AUD-R01). The check now remembers which app it was made for.
 */

beforeEach(function (): void {
    $this->withoutVite();
    $this->travelTo(now()->setDate(2026, 11, 22)->startOfDay());
    $this->officer = User::factory()->withTwoFactor()->create();
    StaffFixture::provision($this->officer, ['compliance'], 'Compliance review assignment.');
    $this->person = User::factory()->create(['name' => 'Aminu Hussain', 'party_id' => Party::factory()]);
});

/** Drives the person's own check to `submitted`, saying with the first step which app it is for. */
function purposeSubmitted(User $user, ?string $purpose): InvestorVerification
{
    $context = $user->context_revision;
    $png = "\x89PNG\r\n\x1a\nsynthetic identity image";
    $revision = 0;
    $step = function (array $result) use (&$revision): void {
        expect($result['status'])->toBe('completed');
        $revision++;
    };
    $step(app(SaveInvestorVerification::class)->handle($user->id, $context, $revision, 'personal',
        ['date_of_birth' => '1/5/1990', ...($purpose === null ? [] : ['purpose' => $purpose])], (string) Str::uuid()));
    $step(app(UploadInvestorVerificationDocument::class)->handle($user->id, $context, $revision, 'id_front', 'front.png', $png, (string) Str::uuid()));
    $step(app(SaveInvestorVerification::class)->handle($user->id, $context, $revision, 'document', ['id_type' => 'passport', 'id_number' => 'PC 445566'], (string) Str::uuid()));
    $step(app(UploadInvestorVerificationDocument::class)->handle($user->id, $context, $revision, 'selfie', 'selfie.png', $png, (string) Str::uuid()));
    $step(app(SubmitInvestorVerification::class)->handle($user->id, $context, $revision, (string) Str::uuid()));

    return InvestorVerification::query()->where('party_id', $user->party_id)->sole();
}

test('the check page carries the app the launcher card sent the person for, until it is submitted', function (): void {
    $this->actingAs($this->person)->get(route('investor.verification', ['for' => 'auditor']))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('investor/verification')->where('purpose', 'auditor'));
    $this->actingAs($this->person)->get(route('investor.verification', ['for' => 'business']))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('purpose', 'business'));
    $this->actingAs($this->person)->get(route('investor.verification', ['for' => 'investor']))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('purpose', 'investor'));
    $this->actingAs($this->person)->get(route('investor.verification', ['for' => 'staff']))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('purpose', 'investor'));

    $case = purposeSubmitted($this->person, 'auditor');
    expect($case->purpose)->toBe('auditor');
    // Submitted, the recorded purpose stands whatever link the person follows next.
    $this->actingAs($this->person)->get(route('investor.verification', ['for' => 'investor']))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('purpose', 'auditor'));
});

test('a step saved without a purpose keeps the one recorded, and an unknown purpose is refused', function (): void {
    $save = app(SaveInvestorVerification::class);
    expect($save->handle($this->person->id, $this->person->context_revision, 0, 'personal',
        ['date_of_birth' => '1/5/1990', 'purpose' => 'business'], (string) Str::uuid())['status'])->toBe('completed');
    expect($save->handle($this->person->id, $this->person->context_revision, 1, 'personal',
        ['date_of_birth' => '1/5/1990'], (string) Str::uuid())['status'])->toBe('completed');
    expect(InvestorVerification::query()->sole()->purpose)->toBe('business');

    $this->actingAs($this->person)->post(route('investor.verification.save'), ['request_id' => (string) Str::uuid(),
        'identity_context_revision' => $this->person->context_revision, 'expected_revision' => 2, 'step' => 'personal',
        'date_of_birth' => '1/5/1990', 'purpose' => 'admin'])->assertSessionHasErrors('purpose');
    $this->actingAs($this->person)->post(route('investor.verification.save'), ['request_id' => (string) Str::uuid(),
        'identity_context_revision' => $this->person->context_revision, 'expected_revision' => 2, 'step' => 'personal',
        'date_of_birth' => '1/5/1990', 'purpose' => 'auditor'])->assertSessionHasNoErrors();
    expect(InvestorVerification::query()->sole()->purpose)->toBe('auditor');
});

test('a check for Auditor access, once submitted, returns the person to the launcher', function (): void {
    $png = "\x89PNG\r\n\x1a\nsynthetic identity image";
    $context = $this->person->context_revision;
    app(SaveInvestorVerification::class)->handle($this->person->id, $context, 0, 'personal', ['date_of_birth' => '1/5/1990', 'purpose' => 'auditor'], (string) Str::uuid());
    app(UploadInvestorVerificationDocument::class)->handle($this->person->id, $context, 1, 'id_front', 'front.png', $png, (string) Str::uuid());
    app(SaveInvestorVerification::class)->handle($this->person->id, $context, 2, 'document', ['id_type' => 'passport', 'id_number' => 'PC 445566'], (string) Str::uuid());
    app(UploadInvestorVerificationDocument::class)->handle($this->person->id, $context, 3, 'selfie', 'selfie.png', $png, (string) Str::uuid());

    $this->actingAs($this->person)->post(route('investor.verification.submit'), ['request_id' => (string) Str::uuid(),
        'identity_context_revision' => $context, 'expected_revision' => 4])->assertRedirect(route('dashboard'));
});

test('approving a check made for Auditor access verifies the person without making them an Investor', function (): void {
    Notification::fake();
    $case = purposeSubmitted($this->person, 'auditor');

    $this->actingAs($this->officer)->get(route('staff.investor-verifications.index', ['verification' => $case->id]))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('entries.0.purpose', 'auditor')->where('review.purpose', 'auditor'));

    $this->actingAs($this->officer)->post(route('staff.investor-verifications.approve', $case), ['request_id' => (string) Str::uuid(),
        'expected_revision' => $case->revision, 'reason' => 'Passport and selfie match.'])->assertSessionHasNoErrors();

    $party = Party::query()->findOrFail($this->person->party_id);
    expect($party->verified_at)->not->toBeNull()
        ->and(RoleMembership::query()->where('party_id', $party->id)->exists())->toBeFalse()
        ->and($case->refresh()->status)->toBe('approved')
        ->and(IdentityAuditEvent::query()->where('action', 'investor.verify')->sole()->result['membership'])->toBeNull();
    Notification::assertSentTo($this->person, VerificationDecided::class,
        fn (VerificationDecided $mail): bool => $mail->purpose === 'auditor' && $mail->url === route('dashboard')
            && str_contains((string) $mail->toMail($this->person)->render(), 'Request Auditor access'));

    $this->actingAs($this->officer)->get(route('staff.investor-verifications.index', ['tab' => 'decided', 'verification' => $case->id]))
        ->assertInertia(fn (Assert $page) => $page->hasFlash('toast', ['type' => 'success', 'message' => 'Identity approved. Aminu Hussain can now ask for Auditor access.']));

    // The launcher now offers Auditor access to ask for, where before the Investor role blocked it.
    $this->actingAs($this->person->refresh())->get(route('dashboard'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('identity.code', 'ROLE_MEMBERSHIP_REQUIRED')
            ->where('access_requests.requestable', ['business', 'auditor']));

    // Not an Investor, so not in the Investor directory.
    $this->actingAs($this->officer)->get(route('staff.investors.index'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('total', 0)->where('directory.rows', []));
});

test('a check with no purpose is an Investor check and still grants Investor access', function (): void {
    $case = purposeSubmitted($this->person, null);
    expect($case->purpose)->toBe('investor');

    $this->actingAs($this->officer)->post(route('staff.investor-verifications.approve', $case), ['request_id' => (string) Str::uuid(),
        'expected_revision' => $case->revision, 'reason' => 'Passport and selfie match.'])->assertSessionHasNoErrors();

    expect(RoleMembership::query()->where('party_id', $this->person->party_id)->sole()->only(['role', 'status']))
        ->toBe(['role' => 'investor', 'status' => 'active']);
});

test('approving a check made for Business access words the confirmation and e-mail for Business access', function (): void {
    Notification::fake();
    $case = purposeSubmitted($this->person, 'business');

    $this->actingAs($this->officer)->post(route('staff.investor-verifications.approve', $case), ['request_id' => (string) Str::uuid(),
        'expected_revision' => $case->revision, 'reason' => 'Passport and selfie match.'])->assertSessionHasNoErrors();

    expect(RoleMembership::query()->where('party_id', $this->person->party_id)->exists())->toBeFalse();
    Notification::assertSentTo($this->person, VerificationDecided::class,
        fn (VerificationDecided $mail): bool => $mail->purpose === 'business'
            && str_contains((string) $mail->toMail($this->person)->render(), 'Request Business access'));
    $this->actingAs($this->officer)->get(route('staff.investor-verifications.index', ['tab' => 'decided', 'verification' => $case->id]))
        ->assertInertia(fn (Assert $page) => $page->hasFlash('toast', ['type' => 'success', 'message' => 'Identity approved. Aminu Hussain can now ask for Business access.']));
});
