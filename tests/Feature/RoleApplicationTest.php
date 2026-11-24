<?php

declare(strict_types=1);

use App\Application\Identity\SaveInvestorVerification;
use App\Application\Identity\SubmitInvestorVerification;
use App\Application\Identity\UploadInvestorVerificationDocument;
use App\Models\AuditorProfile;
use App\Models\BusinessProfile;
use App\Models\IdentityAuditEvent;
use App\Models\InvestorVerification;
use App\Models\Party;
use App\Models\PartyNotice;
use App\Models\RoleApplication;
use App\Models\RoleApplicationDocument;
use App\Models\RoleMembership;
use App\Models\User;
use App\Notifications\Account\OnboardingUpdate;
use App\Notifications\Investor\VerificationDecided;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\HttpFoundation\Response;
use Tests\Support\StaffFixture;
use Tests\TestCase;

/*
 * Switching role (Erastus, 23 Nov 2026): everyone starts as an Investor through the same identity
 * check (KYC). Once that check is SENT, not yet approved, the person may apply to switch to Business
 * representative (company KYB) or Auditor (ICPAR licence). Compliance decides each application in one
 * place: business KYB on the Businesses page, Auditor licences on the Auditors page. Approval needs the
 * KYC approved and switches the role, so the account keeps exactly one.
 */

beforeEach(function (): void {
    $this->withoutVite();
    $this->travelTo(now()->setDate(2026, 11, 23)->setTime(9, 0));
    $this->officer = User::factory()->withTwoFactor()->create(['name' => 'Grace Officer']);
    StaffFixture::provision($this->officer, ['compliance'], 'Compliance review assignment.');
    $this->person = User::factory()->withTwoFactor()->create(['name' => 'Aline Uwase', 'party_id' => Party::factory()]);
});

/** The person's own Investor KYC, sent to Compliance and waiting. */
function kycSent(User $user): InvestorVerification
{
    $context = $user->context_revision;
    $png = "\x89PNG\r\n\x1a\nsynthetic identity image";
    $revision = 0;
    $step = function (array $result) use (&$revision): void {
        expect($result['status'])->toBe('completed');
        $revision++;
    };
    $step(app(SaveInvestorVerification::class)->handle($user->id, $context, $revision, 'personal', ['date_of_birth' => '1/5/1990'], (string) Str::uuid()));
    $step(app(UploadInvestorVerificationDocument::class)->handle($user->id, $context, $revision, 'id_front', 'front.png', $png, (string) Str::uuid()));
    $step(app(SaveInvestorVerification::class)->handle($user->id, $context, $revision, 'document', ['id_type' => 'passport', 'id_number' => 'PC 445566'], (string) Str::uuid()));
    $step(app(UploadInvestorVerificationDocument::class)->handle($user->id, $context, $revision, 'selfie', 'selfie.png', $png, (string) Str::uuid()));
    $step(app(SubmitInvestorVerification::class)->handle($user->id, $context, $revision, (string) Str::uuid()));

    return InvestorVerification::query()->where('party_id', $user->party_id)->sole();
}

function approveKyc(TestCase $test, User $officer, InvestorVerification $case): void
{
    $test->actingAs($officer)->post(route('staff.investor-verifications.approve', $case), ['request_id' => (string) Str::uuid(),
        'expected_revision' => $case->revision, 'reason' => 'Passport and selfie match.'])->assertSessionHasNoErrors();
}

/** @return array<string, mixed> */
function roleEnvelope(User $user, ?int $revision = null): array
{
    return ['request_id' => (string) Str::uuid(), 'identity_context_revision' => $user->refresh()->context_revision,
        'expected_revision' => $revision ?? (int) (RoleApplication::query()->where('party_id', $user->party_id)->value('revision') ?? 0)];
}

/**
 * @param  array<string, string>  $overrides
 * @return array<string, mixed>
 */
function companyAnswers(array $overrides = []): array
{
    return ['legal_name' => 'Kivu Harvest Ltd', 'rdb_code' => '108 123 456', 'tin' => '108123456', 'company_type' => 'private_limited',
        'incorporation_date' => '2019-04-02', 'industry' => 'Agriculture', 'district' => 'Rubavu', 'registered_address' => 'KN 4 Ave, Gisenyi',
        'phone' => '+250 788 000 111', ...$overrides];
}

/** @return array<string, mixed> */
function peopleAnswers(): array
{
    return ['representative' => ['title' => 'Managing Director', 'phone' => '+250 788 000 222', 'authorised' => true],
        'directors' => [['full_name' => 'Aline Uwase', 'id_type' => 'national_id', 'id_number' => '1199080012345678', 'nationality' => 'Rwandan']],
        'owners' => [['full_name' => 'Aline Uwase', 'id_type' => 'national_id', 'id_number' => '1199080012345678', 'nationality' => 'Rwandan',
            'ownership_pct' => '60', 'pep' => false], ['full_name' => 'Jean Habimana', 'id_type' => 'passport', 'id_number' => 'pc 99887', 'nationality' => 'Kenyan',
                'ownership_pct' => '40', 'pep' => true]]];
}

function rolePdf(string $name): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, "%PDF-1.4\nsynthetic {$name}");
}

/** A complete business application, sent. */
function businessSent(TestCase $test, User $user): RoleApplication
{
    $test->actingAs($user)->post(route('role-application.save'), [...roleEnvelope($user), 'role' => 'business', 'step' => 'company', 'answers' => companyAnswers()])
        ->assertSessionHasNoErrors();
    $test->post(route('role-application.save'), [...roleEnvelope($user), 'role' => 'business', 'step' => 'people', 'answers' => peopleAnswers()])->assertSessionHasNoErrors();
    foreach (['rdb_certificate', 'tin_certificate', 'articles', 'shareholder_register', 'id:1199080012345678', 'id:PC99887'] as $slot) {
        $test->post(route('role-application.upload'), [...roleEnvelope($user), 'slot' => $slot, 'file' => rolePdf(str_replace(':', '-', $slot).'.pdf')])->assertSessionHasNoErrors();
    }
    $test->post(route('role-application.save'), [...roleEnvelope($user), 'role' => 'business', 'step' => 'documents', 'answers' => []])->assertSessionHasNoErrors();
    $test->post(route('role-application.submit'), [...roleEnvelope($user), 'declared' => true])->assertRedirect(route('dashboard'))->assertSessionHasNoErrors();

    return RoleApplication::query()->where('party_id', $user->party_id)->sole();
}

/**
 * @param  list<array{key: string, note?: string}>  $items
 * @return TestResponse<Response>
 */
function decideApplication(TestCase $test, User $officer, RoleApplication $application, string $decision, string $note = '', array $items = []): TestResponse
{
    return $test->actingAs($officer)->post(route('staff.role-applications.decide', $application->id), ['request_id' => (string) Str::uuid(),
        'expected_revision' => $application->refresh()->revision, 'decision' => $decision, 'note' => $note, 'items' => $items]);
}

test('the role page waits for the person\'s own KYC to be sent, then offers both roles', function (): void {
    $this->actingAs($this->person)->get(route('role-application.show'))->assertRedirect(route('investor.verification'));
    $this->post(route('role-application.save'), [...roleEnvelope($this->person, 0), 'role' => 'business', 'step' => 'company', 'answers' => companyAnswers()])
        ->assertSessionHasErrors(['form' => 'Send your own identity check (KYC) first. You can continue here straight after.']);

    kycSent($this->person);
    $this->get(route('role-application.show'))->assertOk()->assertInertia(fn (Assert $page): Assert => $page->component('role/apply')
        ->where('role', null)->where('kyc', 'submitted')->where('application.status', 'none')->where('blockers', [])
        ->where('catalog.districts', fn (Collection $districts): bool => $districts->contains('Rubavu')));
    $this->get(route('role-application.show', ['for' => 'auditor']))->assertInertia(fn (Assert $page): Assert => $page->where('role', 'auditor'));
    $this->get(route('dashboard'))->assertInertia(fn (Assert $page): Assert => $page
        ->where('role_application', ['kyc' => 'submitted', 'status' => 'none', 'role' => null]));
});

test('a business application is filled in step by step, checked, and sent before the KYC is approved', function (): void {
    kycSent($this->person);
    $this->actingAs($this->person);

    // Steps are taken in order and each is validated before it is kept.
    $this->post(route('role-application.save'), [...roleEnvelope($this->person), 'role' => 'business', 'step' => 'people', 'answers' => peopleAnswers()])
        ->assertSessionHasErrors(['form' => 'Finish the earlier steps first.']);
    $this->post(route('role-application.save'), [...roleEnvelope($this->person), 'role' => 'business', 'step' => 'company',
        'answers' => companyAnswers(['rdb_code' => '12', 'district' => 'Atlantis', 'incorporation_date' => '2030-01-01'])])
        ->assertSessionHasErrors(['company.rdb_code', 'company.district', 'company.incorporation_date']);
    expect(RoleApplication::query()->count())->toBe(0);

    $application = businessSent($this, $this->person);
    expect($application->status)->toBe('submitted')->and($application->role)->toBe('business')
        ->and($application->state['answers']['company']['rdb_code'] ?? null)->toBe('108123456')
        ->and($application->state['answers']['owners'][1]['id_number'] ?? null)->toBe('PC99887')
        ->and(array_keys($application->state['documents']))->toContain('id:PC99887')
        ->and(RoleApplicationDocument::query()->count())->toBe(6);

    // The applicant sees it under review and can view their own upload, but can no longer change it.
    $this->get(route('role-application.show'))->assertInertia(fn (Assert $page): Assert => $page->where('role', 'business')
        ->where('application.status', 'submitted')->where('application.uploads.articles.filename', 'articles.pdf'));
    expect($this->get(route('role-application.document', 'articles'))->assertOk()->headers->get('Content-Security-Policy'))
        ->toBe("default-src 'none'; sandbox");
    $this->post(route('role-application.save'), [...roleEnvelope($this->person), 'role' => 'business', 'step' => 'company', 'answers' => companyAnswers()])
        ->assertSessionHasErrors(['form' => 'Your application is with our Compliance team, so it can no longer be changed.']);
    $this->get(route('dashboard'))->assertInertia(fn (Assert $page): Assert => $page
        ->where('role_application', ['kyc' => 'submitted', 'status' => 'submitted', 'role' => 'business']));
});

test('compliance reviews business KYB on the Businesses page and approves it only once the KYC is approved', function (): void {
    $kyc = kycSent($this->person);
    $application = businessSent($this, $this->person);

    $this->actingAs($this->officer)->get(route('staff.businesses.index', ['application' => $application->id]))->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page->component('admin/parties')
            ->where('applications.role', 'business')->where('applications.counts.submitted', 1)
            ->where('applications.entries.0.company', 'Kivu Harvest Ltd')->where('applications.entries.0.kyc', 'submitted')
            ->where('application.id', $application->id)->where('application.kyc', 'submitted')->where('application.problems', [])
            ->where('application.allowed_actions', ['approve', 'more', 'reject'])->has('application.documents', 6));
    $document = RoleApplicationDocument::query()->where('slot', 'rdb_certificate')->sole();
    $this->get(route('staff.role-applications.document', [$application->id, $document->id]))->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');
    // The Auditors page does not carry business applications.
    $this->get(route('staff.auditors.index', ['application' => $application->id]))->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page->where('applications.role', 'auditor')->where('application', null));

    decideApplication($this, $this->officer, $application, 'approve')
        ->assertSessionHasErrors(['form' => "Approve the applicant's own KYC first (Users page). Then approve this application."]);

    approveKyc($this, $this->officer, $kyc);
    expect(RoleMembership::query()->where('party_id', $this->person->party_id)->sole()->only(['role', 'status']))->toBe(['role' => 'investor', 'status' => 'active']);

    decideApplication($this, $this->officer, $application, 'approve')->assertSessionHasNoErrors()
        ->assertRedirect(route('staff.businesses.index', ['application' => $application->id, 'kyb' => 'approved']));

    $application->refresh();
    $roles = RoleMembership::query()->where('party_id', $this->person->party_id)->pluck('status', 'role')->all();
    expect($application->status)->toBe('approved')->and(BusinessProfile::query()->whereKey($application->business_id)->exists())->toBeTrue()
        ->and($roles)->toEqual(['investor' => 'revoked', 'business' => 'active'])
        ->and(IdentityAuditEvent::query()->where('action', 'membership.role_switch')->sole()->actor_user_id)->toBe($this->officer->id);

    // Exactly one role now: the launcher no longer offers a switch and the page sends them home.
    $this->actingAs($this->person->refresh())->get(route('role-application.show'))->assertRedirect(route('dashboard'));
    // A retried approval replays without onboarding twice.
    expect(BusinessProfile::query()->count())->toBe(1);
});

test('compliance sends an application back naming the items to change, and the applicant fixes and resends it', function (): void {
    Notification::fake();
    kycSent($this->person);
    $application = businessSent($this, $this->person);

    // Compliance ticks the answers and documents to change (24 Nov 2026); only the case's own items may be named.
    decideApplication($this, $this->officer, $application, 'more', 'Please fix these.')->assertSessionHasErrors('items');
    decideApplication($this, $this->officer, $application, 'more', '', [['key' => 'licence.licence_number']])->assertSessionHasErrors('items');
    decideApplication($this, $this->officer, $application, 'more', '', [['key' => 'documents.articles', 'note' => str_repeat('x', 301)]])->assertSessionHasErrors('items.0.note');
    decideApplication($this, $this->officer, $application, 'more', 'The articles are unsigned. Upload the signed copy.',
        [['key' => 'documents.articles', 'note' => 'Signed copy, please.'], ['key' => 'company.tin']])->assertSessionHasNoErrors();
    expect($application->refresh()->status)->toBe('changes_requested')->and($application->state['note'])->toBe('The articles are unsigned. Upload the signed copy.')
        ->and($application->state['revision']['items'] ?? null)->toBe([
            ['key' => 'documents.articles', 'label' => 'Articles of Association', 'note' => 'Signed copy, please.'],
            ['key' => 'company.tin', 'label' => 'TIN', 'note' => ''],
        ]);
    Notification::assertSentTo($this->person, OnboardingUpdate::class, fn (OnboardingUpdate $mail): bool => $mail->event === 'role_returned'
        && $mail->items === [['label' => 'Articles of Association', 'note' => 'Signed copy, please.'], ['label' => 'TIN', 'note' => '']]);
    $notice = PartyNotice::query()->where('kind', 'role.application_returned')->sole();
    expect($notice->facts['items'])->toEqual([['label' => 'Articles of Association', 'note' => 'Signed copy, please.'], ['label' => 'TIN', 'note' => '']]);

    $this->actingAs($this->person)->get(route('role-application.show'))->assertInertia(fn (Assert $page): Assert => $page
        ->where('application.status', 'changes_requested')->where('application.note', 'The articles are unsigned. Upload the signed copy.')
        ->where('application.requested_changes.items.0.key', 'documents.articles')->where('application.requested_changes.items.1.label', 'TIN'));
    $this->get(route('dashboard'))->assertInertia(fn (Assert $page): Assert => $page->where('role_application.status', 'changes_requested')
        ->where('hub.actions.0.kind', 'role_changes')->where('hub.actions.0.urgent', true)->where('hub.actions.0.items.0.label', 'Articles of Association')
        ->where('hub.steps.3.state', 'attention')->where('hub.recent.0.kind', 'role.application_returned'));
    $this->post(route('role-application.upload'), [...roleEnvelope($this->person), 'slot' => 'articles', 'file' => rolePdf('articles-signed.pdf')])->assertSessionHasNoErrors();
    $this->post(route('role-application.submit'), [...roleEnvelope($this->person), 'declared' => false])->assertSessionHasErrors('declared');
    $this->post(route('role-application.submit'), [...roleEnvelope($this->person), 'declared' => true])->assertSessionHasNoErrors();
    expect($application->refresh()->status)->toBe('submitted')->and($application->state['note'])->toBeNull()
        // The send-back keeps what each item held, so Compliance sees the articles changed and the TIN did not.
        ->and($application->state['revision']['before']['answers']['company']['tin'] ?? null)->toBe($application->state['answers']['company']['tin'] ?? '')
        ->and($application->state['revision']['before']['documents']['articles'] ?? null)->not->toBe($application->state['documents']['articles'] ?? null);

    decideApplication($this, $this->officer, $application, 'reject', 'This company is already represented on Rozine.')->assertSessionHasNoErrors();
    expect($application->refresh()->status)->toBe('rejected')
        ->and(RoleMembership::query()->where('party_id', $this->person->party_id)->where('role', 'business')->exists())->toBeFalse();
});

test('an auditor sends their licence and coverage, and approval accredits them and ends their Investor role', function (): void {
    $kyc = kycSent($this->person);
    $this->actingAs($this->person);

    // The licence is kept only with its certificate, and the certificate's upload starts the application.
    $this->post(route('role-application.save'), [...roleEnvelope($this->person, 0), 'role' => 'auditor', 'step' => 'licence',
        'answers' => ['membership_number' => 'ICPAR/M/0042', 'licence_number' => 'PC-2026-118', 'expires_on' => '2027-12-31']])
        ->assertSessionHasErrors('documents.practising_certificate');
    $this->post(route('role-application.upload'), [...roleEnvelope($this->person, 0), 'slot' => 'articles', 'file' => rolePdf('articles.pdf')])
        ->assertSessionHasErrors(['form' => 'Finish the earlier steps first.']);
    expect(RoleApplication::query()->count())->toBe(0);
    $this->post(route('role-application.upload'), [...roleEnvelope($this->person, 0), 'slot' => 'practising_certificate', 'file' => rolePdf('icpar.pdf')])->assertSessionHasNoErrors();
    $application = RoleApplication::query()->where('party_id', $this->person->party_id)->sole();
    expect($application->status)->toBe('draft')->and($application->role)->toBe('auditor');
    $this->post(route('role-application.save'), [...roleEnvelope($this->person), 'role' => 'auditor', 'step' => 'licence',
        'answers' => ['membership_number' => 'ICPAR/M/0042', 'licence_number' => 'PC-2026-118', 'expires_on' => '2027-12-31', 'firm' => '']])->assertSessionHasNoErrors();
    $this->post(route('role-application.save'), [...roleEnvelope($this->person), 'role' => 'auditor', 'step' => 'coverage',
        'answers' => ['districts' => ['Gasabo', 'Kicukiro'], 'no_conflicts' => true, 'will_declare' => true, 'ethics' => true]])->assertSessionHasNoErrors();

    // Without two-factor authentication the application is not sent.
    $this->person->forceFill(['two_factor_secret' => null, 'two_factor_confirmed_at' => null])->save();
    $this->post(route('role-application.submit'), [...roleEnvelope($this->person), 'declared' => true])
        ->assertSessionHasErrors(['form' => 'Turn on two-factor authentication in your security settings before you send your Auditor application.']);
    $this->person->forceFill(['two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'), 'two_factor_confirmed_at' => now()])->save();
    $this->post(route('role-application.submit'), [...roleEnvelope($this->person), 'declared' => true])->assertSessionHasNoErrors();
    $application->refresh();

    $this->actingAs($this->officer)->get(route('staff.auditors.index', ['application' => $application->id]))->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page->where('applications.entries.0.id', $application->id)
            ->where('application.state.answers.licence.licence_number', 'PC-2026-118')->where('application.account.two_factor', true));

    approveKyc($this, $this->officer, $kyc);
    decideApplication($this, $this->officer, $application, 'approve')->assertSessionHasNoErrors()
        ->assertRedirect(route('staff.auditors.index', ['application' => $application->id, 'kyb' => 'approved']));

    $roles = RoleMembership::query()->where('party_id', $this->person->party_id)->pluck('status', 'role')->all();
    $profile = AuditorProfile::query()->where('party_id', $this->person->party_id)->sole();
    expect($application->refresh()->status)->toBe('approved')->and($roles)->toEqual(['investor' => 'revoked', 'auditor' => 'active'])
        ->and($profile->state['standing'])->toMatchArray(['status' => 'active', 'licence' => 'PC-2026-118', 'expires_on' => '2027-12-31']);
});

test('a staff member who does not verify businesses sees no applications and cannot decide one', function (): void {
    kycSent($this->person);
    $application = businessSent($this, $this->person);
    $analyst = User::factory()->withTwoFactor()->create();
    StaffFixture::provision($analyst, ['analyst'], 'Reporting.');

    $this->actingAs($analyst)->get(route('staff.businesses.index', ['application' => $application->id]))->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page->where('applications', null)->where('application', null));
    decideApplication($this, $analyst, $application, 'approve')->assertSessionHasErrors(['form' => 'Your staff role cannot decide this application.']);
    expect($application->refresh()->status)->toBe('submitted');
});

test('everyone\'s own KYC is reviewed and approved from the account\'s drawer on the Users page', function (): void {
    $case = kycSent($this->person);

    $this->actingAs($this->officer)->get(route('staff.users.index', ['user' => $this->person->id]))->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page->component('admin/users')->where('kyc_review.id', $case->id)
            ->where('kyc_review.status', 'submitted')->has('kyc_review.actions.approve'));

    $this->post(route('staff.investor-verifications.approve', $case), ['request_id' => (string) Str::uuid(), 'expected_revision' => $case->revision,
        'reason' => 'Passport and selfie match.', 'return_to' => 'users', 'return_user' => $this->person->id])
        ->assertSessionHasNoErrors()->assertRedirect(route('staff.users.index', ['user' => $this->person->id]));
    expect($case->refresh()->status)->toBe('approved');
});

test('the dashboard hub follows the person through their KYC and application, and its pulse moves with every change', function (): void {
    Notification::fake();
    $pulse = fn (): string => $this->actingAs($this->person)->getJson(route('dashboard.version'))->assertOk()
        ->assertHeader('Cache-Control', 'no-store, private')->json('version');
    $hub = fn (): array => $this->actingAs($this->person)->get(route('dashboard'))->assertOk()->viewData('page')['props']['hub'];

    $start = $pulse();
    expect($pulse())->toBe($start)
        // The page carries the fingerprint of what it rendered, so it can tell when the facts moved on.
        ->and($this->actingAs($this->person)->get(route('dashboard'))->viewData('page')['props']['pulse'])->toBe($start);
    $before = $hub();
    expect($before['name'])->toBe('Aline')
        ->and(array_column($before['steps'], 'state', 'key'))->toBe(['account' => 'done', 'email' => 'done', 'kyc' => 'current', 'open' => 'upcoming'])
        ->and(array_column($before['actions'], 'kind'))->toBe(['kyc_start'])->and($before['next'])->toBe(['kyc_send'])
        ->and(array_column($before['done'], 'key'))->toBe(['account', 'email', 'two_factor'])->and($before['recent'])->toBe([]);

    // Sending the KYC is emailed and moves the pulse.
    $kyc = kycSent($this->person);
    Notification::assertSentTo($this->person, OnboardingUpdate::class, fn (OnboardingUpdate $mail): bool => $mail->event === 'kyc_received');
    $sent = $pulse();
    expect($sent)->not->toBe($start)
        ->and($this->actingAs($this->person)->get(route('dashboard'))->viewData('page')['props']['pulse'])->toBe($sent);
    $hub = $hub();
    expect($hub['steps'][2]['state'])->toBe('review')->and($hub['next'])->toBe(['kyc_review'])->and($hub['actions'])->toBe([])
        ->and($hub['recent'][0]['kind'])->toBe('verification.submitted');

    // Compliance sends it back naming the items; the hub raises an urgent action with them.
    $this->actingAs($this->officer)->post(route('staff.investor-verifications.reject', $kyc), ['request_id' => (string) Str::uuid(),
        'expected_revision' => $kyc->refresh()->revision, 'reason' => 'The selfie is too dark.', 'items' => [['key' => 'selfie', 'note' => 'Use daylight.'], ['key' => 'id_back']]])
        ->assertSessionHasErrors(['form' => 'One of the ticked items is not on this case. Review it again.']);
    $this->post(route('staff.investor-verifications.reject', $kyc), ['request_id' => (string) Str::uuid(),
        'expected_revision' => $kyc->refresh()->revision, 'reason' => 'The selfie is too dark.', 'items' => [['key' => 'selfie', 'note' => 'Use daylight.']]])
        ->assertSessionHasNoErrors();
    Notification::assertSentTo($this->person, VerificationDecided::class, fn (VerificationDecided $mail): bool => $mail->items === [['label' => 'Selfie', 'note' => 'Use daylight.']]);
    expect($pulse())->not->toBe($sent);
    $returned = $this->actingAs($this->person)->get(route('dashboard'))->viewData('page')['props']['hub'];
    expect($returned['actions'][0])->toMatchArray(['kind' => 'kyc_changes', 'urgent' => true, 'note' => 'The selfie is too dark.',
        'items' => [['key' => 'selfie', 'label' => 'Selfie', 'note' => 'Use daylight.']]])
        ->and($returned['steps'][2]['state'])->toBe('attention')->and($returned['next'])->toBe(['kyc_resend']);
    $this->get(route('investor.verification'))->assertInertia(fn (Assert $page): Assert => $page
        ->where('requested_changes.items.0.key', 'selfie')->where('requested_changes.reason', 'The selfie is too dark.'));

    // Editing it makes it a draft again, but the hub keeps the request until it is sent.
    $png = "\x89PNG\r\n\x1a\nbrighter selfie";
    expect(app(UploadInvestorVerificationDocument::class)->handle($this->person->id, $this->person->context_revision, $kyc->refresh()->revision, 'selfie', 'selfie.png', $png,
        (string) Str::uuid())['status'])->toBe('completed');
    expect($kyc->refresh()->status)->toBe('draft');
    $draft = $this->actingAs($this->person)->get(route('dashboard'))->viewData('page')['props']['hub'];
    expect($draft['actions'][0]['kind'])->toBe('kyc_changes');
    expect(app(SubmitInvestorVerification::class)->handle($this->person->id, $this->person->context_revision, $kyc->refresh()->revision, (string) Str::uuid())['status'])
        ->toBe('completed');

    // The business application is sent, then approved after the KYC; the hub says the account is ready.
    $application = businessSent($this, $this->person);
    $pending = $this->actingAs($this->person)->get(route('dashboard'))->viewData('page')['props']['hub'];
    expect($pending['steps'][3])->toMatchArray(['key' => 'role', 'state' => 'review', 'role' => 'business'])
        ->and($pending['next'])->toBe(['kyc_review', 'role_review', 'role_switch'])->and($pending['company'])->toBe('Kivu Harvest Ltd');
    approveKyc($this, $this->officer, $kyc->refresh());
    decideApplication($this, $this->officer, $application, 'approve')->assertSessionHasNoErrors();
    Notification::assertSentTo($this->person, OnboardingUpdate::class, fn (OnboardingUpdate $mail): bool => $mail->event === 'role_approved' && $mail->company === 'Kivu Harvest Ltd');
    $ready = $this->actingAs($this->person)->get(route('dashboard'))->viewData('page')['props']['hub'];
    expect(array_column($ready['steps'], 'state'))->toBe(['done', 'done', 'done', 'done', 'done'])
        ->and($ready['next'])->toBe(['business_ready'])
        ->and(array_column($ready['done'], 'key'))->toBe(['account', 'email', 'two_factor', 'kyc_sent', 'kyc_approved', 'role_sent', 'role_approved']);
});

test('a rejected application and an unsent draft each get their own next step on the hub', function (): void {
    Notification::fake();
    $kyc = kycSent($this->person);
    $application = businessSent($this, $this->person);
    decideApplication($this, $this->officer, $application, 'reject', 'This company is already represented on Rozine.')->assertSessionHasNoErrors();
    Notification::assertSentTo($this->person, OnboardingUpdate::class, fn (OnboardingUpdate $mail): bool => $mail->event === 'role_rejected');
    $hub = $this->actingAs($this->person)->get(route('dashboard'))->viewData('page')['props']['hub'];
    expect($hub['actions'][0])->toMatchArray(['kind' => 'role_rejected', 'urgent' => false, 'note' => 'This company is already represented on Rozine.'])
        ->and($hub['steps'][3]['state'])->toBe('rejected')->and($hub['next'])->toBe(['kyc_review', 'role_reapply', 'role_switch']);

    // Starting again leaves a draft to finish.
    $this->post(route('role-application.save'), [...roleEnvelope($this->person), 'role' => 'business', 'step' => 'company', 'answers' => companyAnswers()])->assertSessionHasNoErrors();
    expect($application->refresh()->status)->toBe('rejected');
    approveKyc($this, $this->officer, $kyc);
    $after = $this->actingAs($this->person)->get(route('dashboard'))->viewData('page')['props']['hub'];
    expect($after['steps'][2]['state'])->toBe('done');
});

test('the pulse needs a signed-in, verified account and says nothing about an account without a Party', function (): void {
    $this->getJson(route('dashboard.version'))->assertUnauthorized();
    $staff = User::factory()->create(['party_id' => null]);
    $this->actingAs($staff)->getJson(route('dashboard.version'))->assertOk()->assertJsonStructure(['version']);
    $this->get(route('dashboard'))->assertOk()->assertInertia(fn (Assert $page): Assert => $page->where('hub', null));
});

test('phone numbers must be Rwandan numbers, so an 11-digit local number is refused (QA 24 Nov 2026)', function (): void {
    kycSent($this->person);
    $this->actingAs($this->person)->post(route('role-application.save'), [...roleEnvelope($this->person), 'role' => 'business', 'step' => 'company',
        'answers' => companyAnswers(['phone' => '07897267365'])])->assertSessionHasErrors(['company.phone']);
    $this->post(route('role-application.save'), [...roleEnvelope($this->person), 'role' => 'business', 'step' => 'company',
        'answers' => companyAnswers(['phone' => '0788 000 111'])])->assertSessionHasNoErrors();

    $people = peopleAnswers();
    $people['representative']['phone'] = '07897267365';
    $this->post(route('role-application.save'), [...roleEnvelope($this->person), 'role' => 'business', 'step' => 'people', 'answers' => $people])
        ->assertSessionHasErrors(['representative.phone']);
    $people['representative']['phone'] = '250788000222';
    $this->post(route('role-application.save'), [...roleEnvelope($this->person), 'role' => 'business', 'step' => 'people', 'answers' => $people])
        ->assertSessionHasNoErrors();
});
