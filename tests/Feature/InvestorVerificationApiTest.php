<?php

declare(strict_types=1);

use App\Application\Identity\ConfigureStaffAccess;
use App\Application\Operations\Contracts\OperationJournal;
use App\Domain\Operations\CommandRejection;
use App\Domain\Operations\OperationResult;
use App\Models\InvestorVerification;
use App\Models\InvestorVerificationDocument;
use App\Models\Party;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\post;
use function Pest\Laravel\postJson;

beforeEach(function (): void {
    $this->travelTo(now()->setDate(2026, 10, 6)->startOfDay());
    $this->person = User::factory()->create(['party_id' => Party::factory()]);
    $this->officer = User::factory()->withTwoFactor()->create();
    app(ConfigureStaffAccess::class)->handle($this->officer->id, true, 'Compliance review assignment.', (string) Str::uuid(), ['compliance']);
});

/**
 * @param  array<string, mixed>  $fields
 * @return array<string, mixed>
 */
function apiKycCommand(User $user, array $fields): array
{
    return ['request_id' => (string) Str::uuid(), 'identity_context_revision' => $user->context_revision,
        'expected_revision' => (int) InvestorVerification::query()->where('party_id', $user->party_id)->value('revision'), ...$fields];
}

/** Submits a complete individual case over the API with a participant command token. */
function apiKycSubmitted(User $user): InvestorVerification
{
    Sanctum::actingAs($user, ['investor:read', 'investor:command']);
    $png = fn (string $name): UploadedFile => UploadedFile::fake()->createWithContent($name, "\x89PNG\r\n\x1a\nsynthetic identity image");
    postJson(route('api.v1.investor.verification.save'), apiKycCommand($user, ['step' => 'personal', 'date_of_birth' => '1/5/1990']))->assertOk();
    foreach (['id_front' => 'front.png', 'id_back' => 'back.png'] as $slot => $name) {
        post(route('api.v1.investor.verification.upload'), apiKycCommand($user, ['slot' => $slot, 'file' => $png($name)]), ['Accept' => 'application/json'])->assertOk();
    }
    postJson(route('api.v1.investor.verification.save'), apiKycCommand($user, ['step' => 'document', 'id_type' => 'national_id', 'id_number' => '1199080012345678']))->assertOk();
    post(route('api.v1.investor.verification.upload'), apiKycCommand($user, ['slot' => 'selfie', 'file' => $png('selfie.png')]), ['Accept' => 'application/json'])->assertOk();
    postJson(route('api.v1.investor.verification.submit'), apiKycCommand($user, []))
        ->assertOk()->assertJsonPath('code', 'VERIFICATION_SUBMITTED')->assertJsonPath('data.status', 'submitted')->assertJsonPath('revision', 6);

    return InvestorVerification::query()->where('party_id', $user->party_id)->sole();
}

test('a participant token reads its own submission with API command links', function (): void {
    Sanctum::actingAs($this->person, ['investor:read']);
    $this->getJson(route('api.v1.investor.verification'))->assertOk()
        ->assertJsonPath('data.verified', false)->assertJsonPath('data.status', 'draft')->assertJsonPath('data.links.back', null)
        ->assertJsonPath('data.actions.save.url', '/api/v1/investor/verification/steps')
        ->assertJsonPath('data.actions.upload.url', '/api/v1/investor/verification/documents')
        ->assertJsonPath('data.actions.submit.url', '/api/v1/investor/verification/submit');

    Sanctum::actingAs($this->person, ['investor:command']);
    $this->getJson(route('api.v1.investor.verification'))->assertForbidden();
    Sanctum::actingAs($this->person, ['investor:read']);
    $this->postJson(route('api.v1.investor.verification.save'), apiKycCommand($this->person, ['step' => 'personal', 'date_of_birth' => '1/5/1990']))
        ->assertForbidden();
    Sanctum::actingAs(User::factory()->unverified()->create(['party_id' => Party::factory()]), ['investor:read']);
    $this->getJson(route('api.v1.investor.verification'))->assertForbidden();
});

test('participant commands answer with operation receipts, refusals included', function (): void {
    $case = apiKycSubmitted($this->person);
    expect($case->status)->toBe('submitted');

    $stale = apiKycCommand($this->person, ['step' => 'personal', 'date_of_birth' => '1/5/1990', 'expected_revision' => 2]);
    $this->postJson(route('api.v1.investor.verification.save'), $stale)->assertStatus(409)
        ->assertJsonPath('status', 'rejected')->assertJsonPath('code', 'VERSION_CONFLICT')->assertJsonPath('revision', 6);
    $this->postJson(route('api.v1.investor.verification.save'), [...$stale, 'date_of_birth' => '2/5/1990'])->assertStatus(409)
        ->assertJsonPath('code', 'IDEMPOTENCY_CONFLICT')->assertJsonPath('operation_id', null)->assertJsonPath('data', null)
        ->assertJsonPath('policy_version', 'engineering-2026-10-06.1');
});

test('KYC receipts report the KYC policy for successes, recorded refusals, replays and pre-journal refusals', function (): void {
    Sanctum::actingAs($this->person, ['investor:read', 'investor:command']);
    $save = apiKycCommand($this->person, ['step' => 'personal', 'date_of_birth' => '1/5/1990']);
    $first = $this->postJson(route('api.v1.investor.verification.save'), $save)->assertOk()
        ->assertJsonPath('code', 'VERIFICATION_SAVED')->assertJsonPath('policy_version', 'engineering-2026-10-06.1');
    $this->postJson(route('api.v1.investor.verification.save'), $save)->assertOk()
        ->assertJsonPath('operation_id', $first->json('operation_id'))->assertJsonPath('policy_version', 'engineering-2026-10-06.1');

    $stale = [...$save, 'request_id' => (string) Str::uuid(), 'expected_revision' => 0];
    $refused = $this->postJson(route('api.v1.investor.verification.save'), $stale)->assertStatus(409)
        ->assertJsonPath('code', 'VERSION_CONFLICT')->assertJsonPath('policy_version', 'engineering-2026-10-06.1');
    $this->postJson(route('api.v1.investor.verification.save'), $stale)->assertStatus(409)
        ->assertJsonPath('operation_id', $refused->json('operation_id'))->assertJsonPath('policy_version', 'engineering-2026-10-06.1');
    $this->postJson(route('api.v1.investor.verification.save'), [...$stale, 'date_of_birth' => '2/5/1990'])->assertStatus(409)
        ->assertJsonPath('code', 'IDEMPOTENCY_CONFLICT')->assertJsonPath('operation_id', null)->assertJsonPath('policy_version', 'engineering-2026-10-06.1');

    $case = InvestorVerification::query()->sole();
    $case->forceFill(['status' => 'submitted', 'submitted_at' => now()])->save();
    Sanctum::actingAs($this->officer, ['staff:investors:read', 'staff:investors:verify']);
    $decision = ['request_id' => (string) Str::uuid(), 'expected_revision' => 5, 'reason' => 'Matches.'];
    $staffRefused = $this->postJson(route('api.v1.staff.investor-verifications.approve', $case), $decision)->assertStatus(409)
        ->assertJsonPath('code', 'VERSION_CONFLICT')->assertJsonPath('policy_version', 'engineering-2026-10-06.1');
    $this->postJson(route('api.v1.staff.investor-verifications.approve', $case), $decision)->assertStatus(409)
        ->assertJsonPath('operation_id', $staffRefused->json('operation_id'))->assertJsonPath('policy_version', 'engineering-2026-10-06.1');
});

test('a refusal recorded without a policy keeps the journal default, so other commands are unchanged', function (): void {
    $journal = app(OperationJournal::class);
    $refuse = fn (CommandRejection $rejection): array => $journal->execute('staff:'.$this->officer->id, $this->officer->id, 'test.refusal', (string) Str::uuid(),
        'test', 'target', [], static function (): void {}, static fn (): OperationResult => throw $rejection);

    expect($refuse(new CommandRejection('SOMETHING_REFUSED'))['policy_version'])->toBe('engineering-2026-09-23.4')
        ->and($refuse((new CommandRejection('SOMETHING_REFUSED'))->underPolicy('engineering-2026-10-06.1'))['policy_version'])->toBe('engineering-2026-10-06.1');
});

test('Compliance reads the queue and private documents over the API with its own abilities', function (): void {
    $case = apiKycSubmitted($this->person);
    $front = InvestorVerificationDocument::query()->where('investor_verification_id', $case->id)->where('slot', 'front')->sole();

    Sanctum::actingAs($this->officer, ['staff:investors:read']);
    $this->getJson(route('api.v1.staff.investor-verifications.index', ['verification' => $case->id]))->assertOk()
        ->assertJsonPath('data.contract_version', 'staff-investor-verifications-v1')->assertJsonPath('data.entries.0.id', $case->id)
        ->assertJsonPath('data.entries.0.link.url', '/api/v1/staff/investor-verifications?tab=submitted&verification='.$case->id)
        ->assertJsonPath('data.review.actions.approve.url', '/api/v1/staff/investor-verifications/'.$case->id.'/approve')
        ->assertJsonPath('data.review.documents.0.link.url', '/api/v1/staff/investor-verifications/'.$case->id.'/documents/'.$front->id)
        ->assertJsonPath('data.review.documents.0.view.url', '/api/v1/staff/investor-verifications/'.$case->id.'/documents/'.$front->id.'?disposition=inline');
    expect($this->get(route('api.v1.staff.investor-verifications.document', [$case->id, $front->id]))->assertOk()->getContent())
        ->toBe("\x89PNG\r\n\x1a\nsynthetic identity image");

    Sanctum::actingAs($this->officer, ['investor:read']);
    $this->getJson(route('api.v1.staff.investor-verifications.index'))->assertForbidden();
    $this->get(route('api.v1.staff.investor-verifications.document', [$case->id, $front->id]))->assertForbidden();
});

test('Compliance approves or rejects over the API only with the verify ability', function (): void {
    $case = apiKycSubmitted($this->person);
    $decision = fn (string $reason): array => ['request_id' => (string) Str::uuid(), 'expected_revision' => 6, 'reason' => $reason];

    Sanctum::actingAs($this->officer, ['staff:investors:read']);
    $this->postJson(route('api.v1.staff.investor-verifications.approve', $case), $decision('Matches.'))->assertForbidden();

    Sanctum::actingAs($this->officer, ['staff:investors:read', 'staff:investors:verify']);
    $this->postJson(route('api.v1.staff.investor-verifications.reject', $case), [...$decision(''), 'reason' => ''])
        ->assertUnprocessable()->assertJsonValidationErrors(['reason' => 'Give the reason for this decision.']);
    $this->postJson(route('api.v1.staff.investor-verifications.reject', $case), $decision('The ID photo is blurred.'))->assertOk()
        ->assertJsonPath('code', 'VERIFICATION_REJECTED')->assertJsonPath('data.status', 'rejected')->assertJsonPath('revision', 7);
    $this->postJson(route('api.v1.staff.investor-verifications.approve', $case), $decision('Matches.'))->assertStatus(409)
        ->assertJsonPath('code', 'VERSION_CONFLICT')->assertJsonPath('revision', 7);

    Sanctum::actingAs($this->person, ['investor:read']);
    $this->getJson(route('api.v1.investor.verification'))->assertJsonPath('data.status', 'rejected')
        ->assertJsonPath('data.decision_reason', 'The ID photo is blurred.');
});

test('an approval verifies the person, whose API read then shows no approval note', function (): void {
    $case = apiKycSubmitted($this->person);

    Sanctum::actingAs($this->officer, ['staff:investors:read', 'staff:investors:verify']);
    $this->postJson(route('api.v1.staff.investor-verifications.approve', $case),
        ['request_id' => (string) Str::uuid(), 'expected_revision' => 6, 'reason' => 'Photo, number and selfie match.'])->assertOk()
        ->assertJsonPath('code', 'INVESTOR_VERIFIED')->assertJsonPath('data.status', 'approved')
        ->assertJsonPath('data.membership.role', 'investor')->assertJsonPath('data.membership.status', 'active');

    Sanctum::actingAs($this->person->refresh(), ['investor:read']);
    $this->getJson(route('api.v1.investor.verification'))->assertOk()
        ->assertJsonPath('data.verified', true)->assertJsonPath('data.status', 'approved')->assertJsonPath('data.decision_reason', null);
});
