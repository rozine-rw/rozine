<?php

declare(strict_types=1);

use App\Application\Auditor\BrowseAuditorDirectory;
use App\Application\Identity\SaveInvestorVerification;
use App\Application\Identity\SubmitInvestorVerification;
use App\Application\Identity\UploadInvestorVerificationDocument;
use App\Models\AuditorCertificate;
use App\Models\AuditorProfile;
use App\Models\IdentityAuditEvent;
use App\Models\InvestorVerification;
use App\Models\Party;
use App\Models\RoleMembership;
use App\Models\User;
use App\Notifications\Investor\VerificationDecided;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
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
    $this->person = User::factory()->withTwoFactor()->create(['name' => 'Aminu Hussain', 'party_id' => Party::factory()]);
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
    $licence = [];
    if ($purpose === 'auditor') {
        $step(app(UploadInvestorVerificationDocument::class)->handle($user->id, $context, $revision, 'icpar_certificate', 'icpar.pdf', "%PDF-1.4\nsynthetic licence", (string) Str::uuid()));
        $licence = ['licence_number' => ' ICPAR-2024-0042 ', 'licence_expires_on' => '2027-12-31'];
    }
    $step(app(SaveInvestorVerification::class)->handle($user->id, $context, $revision, 'document', ['id_type' => 'passport', 'id_number' => 'PC 445566', ...$licence], (string) Str::uuid()));
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

test('an Auditor check starts with two-factor authentication: the page sends the person to set it up, and no step is taken without it', function (): void {
    $person = User::factory()->create(['party_id' => Party::factory()]);
    $this->actingAs($person)->get(route('investor.verification', ['for' => 'auditor']))->assertRedirect(route('security.edit'));
    $this->actingAs($person)->get(route('investor.verification', ['for' => 'business']))->assertOk();

    $this->actingAs($person)->post(route('investor.verification.save'), ['request_id' => (string) Str::uuid(),
        'identity_context_revision' => $person->context_revision, 'expected_revision' => 0, 'step' => 'personal',
        'date_of_birth' => '1/5/1990', 'purpose' => 'auditor'])->assertSessionHasErrors('form');
    expect(InvestorVerification::query()->count())->toBe(0);

    // A check already recorded for Auditor access stays closed until two-factor authentication is on.
    expect(app(SaveInvestorVerification::class)->handle($person->id, $person->context_revision, 0, 'personal',
        ['date_of_birth' => '1/5/1990', 'purpose' => 'auditor'], (string) Str::uuid())['status'])->toBe('completed');
    $this->actingAs($person)->get(route('investor.verification'))->assertRedirect(route('security.edit'));
    $this->actingAs($person)->post(route('investor.verification.upload'), ['request_id' => (string) Str::uuid(),
        'identity_context_revision' => $person->context_revision, 'expected_revision' => 1, 'slot' => 'id_front',
        'file' => UploadedFile::fake()->createWithContent('front.png', "\x89PNG\r\n\x1a\nimage")])->assertSessionHasErrors('form');
    $this->actingAs($person)->post(route('investor.verification.submit'), ['request_id' => (string) Str::uuid(),
        'identity_context_revision' => $person->context_revision, 'expected_revision' => 1])->assertSessionHasErrors('form');

    $person->forceFill(['two_factor_secret' => encrypt('secret'), 'two_factor_confirmed_at' => now()])->save();
    $this->actingAs($person)->get(route('investor.verification'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('investor/verification')->where('purpose', 'auditor'));
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

test('a check for Auditor access takes the ICPAR licence with the ID, and once submitted returns the person to the launcher', function (): void {
    $png = "\x89PNG\r\n\x1a\nsynthetic identity image";
    $context = $this->person->context_revision;
    $save = app(SaveInvestorVerification::class);
    $upload = app(UploadInvestorVerificationDocument::class);
    $save->handle($this->person->id, $context, 0, 'personal', ['date_of_birth' => '1/5/1990', 'purpose' => 'auditor'], (string) Str::uuid());
    $upload->handle($this->person->id, $context, 1, 'id_front', 'front.png', $png, (string) Str::uuid());
    $document = ['id_type' => 'passport', 'id_number' => 'PC 445566'];

    // The licence certificate, then a current licence, come with the ID document.
    expect($save->handle($this->person->id, $context, 2, 'document', [...$document, 'licence_number' => 'ICPAR-1', 'licence_expires_on' => '2027-12-31'], (string) Str::uuid()))
        ->toMatchArray(['status' => 'rejected', 'code' => 'VERIFICATION_CERTIFICATE_REQUIRED']);
    $upload->handle($this->person->id, $context, 2, 'icpar_certificate', 'icpar.pdf', "%PDF-1.4\nlicence", (string) Str::uuid());
    expect($save->handle($this->person->id, $context, 3, 'document', [...$document, 'licence_number' => '', 'licence_expires_on' => '2027-12-31'], (string) Str::uuid()))
        ->toMatchArray(['status' => 'rejected', 'code' => 'VERIFICATION_LICENCE_INVALID'])
        ->and($save->handle($this->person->id, $context, 3, 'document', [...$document, 'licence_number' => 'ICPAR-1', 'licence_expires_on' => '2026-11-21'], (string) Str::uuid()))
        ->toMatchArray(['status' => 'rejected', 'code' => 'VERIFICATION_LICENCE_EXPIRY_INVALID']);

    $this->actingAs($this->person)->post(route('investor.verification.save'), ['request_id' => (string) Str::uuid(), 'identity_context_revision' => $context,
        'expected_revision' => 3, 'step' => 'document', ...$document, 'licence_number' => 'ICPAR-1', 'licence_expires_on' => '2027-12-31'])->assertSessionHasNoErrors();
    $upload->handle($this->person->id, $context, 4, 'selfie', 'selfie.png', $png, (string) Str::uuid());
    $this->actingAs($this->person)->get(route('investor.verification'))->assertInertia(fn (Assert $page) => $page
        ->where('licence_number', 'ICPAR-1')->where('licence_expires_on', '2027-12-31')->where('uploads.certificate.status', 'uploaded')
        ->where('uploads.certificate.link.url', fn (string $url): bool => str_starts_with($url, '/investor/verification/documents/certificate')));
    $this->actingAs($this->person)->get(route('investor.verification.document', ['side' => 'certificate']))->assertOk();

    $this->actingAs($this->person)->post(route('investor.verification.submit'), ['request_id' => (string) Str::uuid(),
        'identity_context_revision' => $context, 'expected_revision' => 5])->assertRedirect(route('dashboard'));
});

test('approving a check made for Auditor access verifies the person and their licence, and makes them an Auditor in one step', function (): void {
    Notification::fake();
    $case = purposeSubmitted($this->person, 'auditor');

    $this->actingAs($this->officer)->get(route('staff.investor-verifications.index', ['verification' => $case->id]))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('entries.0.purpose', 'auditor')->where('review.purpose', 'auditor')
            ->where('review.licence', ['number' => 'ICPAR-2024-0042', 'expires_on' => '2027-12-31'])
            ->where('review.documents', fn (Collection $documents): bool => $documents->contains('slot', 'certificate')));

    $this->actingAs($this->officer)->post(route('staff.investor-verifications.approve', $case), ['request_id' => (string) Str::uuid(),
        'expected_revision' => $case->revision, 'reason' => 'Passport, selfie and ICPAR register match.'])->assertSessionHasNoErrors();

    $party = Party::query()->findOrFail($this->person->party_id);
    $profile = AuditorProfile::query()->where('party_id', $party->id)->sole();
    expect($party->verified_at)->not->toBeNull()
        ->and(RoleMembership::query()->where('party_id', $party->id)->sole()->only(['role', 'status']))->toBe(['role' => 'auditor', 'status' => 'active'])
        ->and($case->refresh()->status)->toBe('approved')
        ->and(IdentityAuditEvent::query()->where('action', 'investor.verify')->sole()->result['membership']['role'])->toBe('auditor')
        ->and($profile->state['standing'])->toMatchArray(['status' => 'active', 'licence' => 'ICPAR-2024-0042', 'expires_on' => '2027-12-31'])
        ->and(AuditorCertificate::query()->whereKey($profile->state['certificate_id'])->sole()->content)->toBe("%PDF-1.4\nsynthetic licence");
    Notification::assertSentTo($this->person, VerificationDecided::class,
        fn (VerificationDecided $mail): bool => $mail->purpose === 'auditor' && $mail->url === route('dashboard')
            && str_contains((string) $mail->toMail($this->person)->render(), 'Open the Auditor app'));

    $this->actingAs($this->officer)->get(route('staff.investor-verifications.index', ['tab' => 'decided', 'verification' => $case->id]))
        ->assertInertia(fn (Assert $page) => $page->hasFlash('toast', ['type' => 'success', 'message' => 'Identity and ICPAR licence approved. Aminu Hussain is now an Auditor.']));

    // The launcher now offers the Auditor app, and the Auditor Network lists an active partner.
    $this->actingAs($this->person->refresh())->get(route('dashboard'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('identity.code', 'IDENTITY_READY'));
    expect(app(BrowseAuditorDirectory::class)->directory($this->officer->id, 'active', '', 50)['rows'])->toHaveCount(1);

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
